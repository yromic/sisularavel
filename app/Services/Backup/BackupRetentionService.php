<?php

namespace App\Services\Backup;

use App\Models\BackupRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupRetentionService
{
    public function __construct(
        protected GoogleDriveStorageService $googleDrive
    ) {}

    /**
     * Determine which backup runs should be retained and which should be pruned
     * based on Grandfather-Father-Son (Daily-Weekly-Monthly) Rolling Retention.
     *
     * @return array [ 'to_keep' => Collection, 'to_prune' => Collection, 'reason' => ?string ]
     */
    public function planRetention(): array
    {
        $completedRuns = BackupRun::query()
            ->where('status', 'completed')
            ->orderByDesc('started_at')
            ->get();

        // Golden Rule 1: Never prune anything if there is only 1 or 0 completed backups
        if ($completedRuns->count() <= 1) {
            return [
                'to_keep' => $completedRuns,
                'to_prune' => collect(),
                'reason' => 'Total completed backups is 1 or fewer; Golden Rule protects the only existing archive.',
            ];
        }

        // Golden Rule 2: If the most recent backup attempt failed, suspend pruning
        $latestRun = BackupRun::query()->latest('id')->first();
        if ($latestRun && $latestRun->status === 'failed') {
            return [
                'to_keep' => $completedRuns,
                'to_prune' => collect(),
                'reason' => 'Most recent backup run is in failed state; Golden Rule forbids pruning historical archives during incident state.',
            ];
        }

        $dailyDays = (int) config('backup.retention.keep_daily_days', 7);
        $weeklyWeeks = (int) config('backup.retention.keep_weekly_weeks', 4);
        $monthlyMonths = (int) config('backup.retention.keep_monthly_months', 12);

        $now = now();
        $keepIds = [];

        // 1. Daily Tier: Keep the newest backup of each day within the daily retention window
        for ($d = 0; $d < $dailyDays; $d++) {
            $targetDate = $now->copy()->subDays($d)->format('Y-m-d');
            $match = $completedRuns->first(function ($run) use ($targetDate) {
                return Carbon::parse($run->started_at)->format('Y-m-d') === $targetDate;
            });

            if ($match) {
                $keepIds[$match->id] = 'daily';
            }
        }

        // 2. Weekly Tier: Keep the newest backup of each week within the weekly retention window
        for ($w = 0; $w < $weeklyWeeks; $w++) {
            $targetWeek = $now->copy()->subWeeks($w)->format('o-W'); // ISO-8601 year and week number
            $match = $completedRuns->first(function ($run) use ($targetWeek) {
                return Carbon::parse($run->started_at)->format('o-W') === $targetWeek;
            });

            if ($match && ! isset($keepIds[$match->id])) {
                $keepIds[$match->id] = 'weekly';
            }
        }

        // 3. Monthly Tier: Keep the newest backup of each month within the monthly retention window
        for ($m = 0; $m < $monthlyMonths; $m++) {
            $targetMonth = $now->copy()->subMonths($m)->format('Y-m');
            $match = $completedRuns->first(function ($run) use ($targetMonth) {
                return Carbon::parse($run->started_at)->format('Y-m') === $targetMonth;
            });

            if ($match && ! isset($keepIds[$match->id])) {
                $keepIds[$match->id] = 'monthly';
            }
        }

        // Always keep the absolute latest completed backup as fallback
        $firstCompleted = $completedRuns->first();
        if ($firstCompleted) {
            $keepIds[$firstCompleted->id] = 'latest_fallback';
        }

        $toKeep = $completedRuns->whereIn('id', array_keys($keepIds));
        $toPrune = $completedRuns->whereNotIn('id', array_keys($keepIds));

        return [
            'to_keep' => $toKeep,
            'to_prune' => $toPrune,
            'reason' => null,
        ];
    }

    /**
     * Execute rolling retention pruning on local storage and Google Drive.
     *
     * @param  bool  $dryRun  If true, simulates pruning without deleting files or database records
     * @return array Summary of pruned and kept archives
     */
    public function prune(bool $dryRun = false): array
    {
        $plan = $this->planRetention();
        $toKeep = $plan['to_keep'];
        $toPrune = $plan['to_prune'];

        $deleteRemote = (bool) config('backup.retention.delete_remote_on_prune', true);
        $prunedRuns = [];
        $reclaimedBytes = 0;

        foreach ($toPrune as $run) {
            $localPath = storage_path("app/private/backups/{$run->backup_id}.enc");
            $localSize = File::exists($localPath) ? File::size($localPath) : ($run->archive_size_bytes ?? 0);

            $pruneDetails = [
                'backup_id' => $run->backup_id,
                'started_at' => $run->started_at,
                'size_bytes' => $localSize,
                'remote_file_id' => $run->remote_file_id,
                'remote_deleted' => false,
                'local_deleted' => false,
            ];

            if (! $dryRun) {
                // Delete local file
                if (File::exists($localPath)) {
                    File::delete($localPath);
                    $pruneDetails['local_deleted'] = true;
                }

                // Delete remote Google Drive file
                if ($deleteRemote && $run->remote_storage_provider === 'google_drive' && ! empty($run->remote_file_id)) {
                    try {
                        $pruneDetails['remote_deleted'] = $this->googleDrive->deleteRemoteFile($run->remote_file_id);
                    } catch (\Throwable $e) {
                        Log::warning("Failed to delete remote backup file {$run->remote_file_id}: {$e->getMessage()}");
                    }
                }

                // Delete record from database
                $run->delete();
            }

            $reclaimedBytes += $localSize;
            $prunedRuns[] = $pruneDetails;
        }

        return [
            'success' => true,
            'dry_run' => $dryRun,
            'evaluated_count' => $toKeep->count() + $toPrune->count(),
            'kept_count' => $toKeep->count(),
            'pruned_count' => count($prunedRuns),
            'reclaimed_bytes' => $reclaimedBytes,
            'pruned_runs' => $prunedRuns,
            'reason' => $plan['reason'],
        ];
    }
}
