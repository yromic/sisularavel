<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BackupRun;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const INDONESIAN_MONTHS = [
        '01' => 'Jan',
        '02' => 'Feb',
        '03' => 'Mar',
        '04' => 'Apr',
        '05' => 'Mei',
        '06' => 'Jun',
        '07' => 'Jul',
        '08' => 'Agu',
        '09' => 'Sep',
        '10' => 'Okt',
        '11' => 'Nov',
        '12' => 'Des',
    ];

    /**
     * Get aggregate counts scoped by division context.
     *
     * @return array{incoming: int, outgoing: int, piagam: int, total: int}
     */
    public function getSummaryCounts(?int $divisionId): array
    {
        $incoming = $this->scoped(IncomingLetter::query(), $divisionId)->count();
        $outgoing = $this->scoped(OutgoingLetter::query(), $divisionId)->count();
        $piagam = $this->scoped(Piagam::query(), $divisionId)->count();

        return [
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'piagam' => $piagam,
            'total' => $incoming + $outgoing + $piagam,
        ];
    }

    /**
     * Get raw monthly aggregate counts dictionary for API and charting.
     *
     * @return array{incoming: array<string, int>, outgoing: array<string, int>}
     */
    public function getMonthlySeries(?int $divisionId, ?int $monthsBack = null): array
    {
        $driver = DB::connection()->getDriverName();
        $expr = $driver === 'sqlite'
            ? "strftime('%Y-%m', letter_date)"
            : "DATE_FORMAT(letter_date, '%Y-%m')";

        $incomingQuery = $this->scoped(IncomingLetter::query(), $divisionId)
            ->whereNotNull('letter_date');
        $outgoingQuery = $this->scoped(OutgoingLetter::query(), $divisionId)
            ->whereNotNull('letter_date');

        if ($monthsBack !== null && $monthsBack > 0) {
            $startDate = now()->subMonths($monthsBack - 1)->startOfMonth()->toDateString();
            $incomingQuery->where('letter_date', '>=', $startDate);
            $outgoingQuery->where('letter_date', '>=', $startDate);
        }

        $incoming = $incomingQuery
            ->selectRaw("{$expr} as ym, count(*) as aggregate_count")
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('aggregate_count', 'ym')
            ->map(fn ($c) => (int) $c)
            ->all();

        $outgoing = $outgoingQuery
            ->selectRaw("{$expr} as ym, count(*) as aggregate_count")
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('aggregate_count', 'ym')
            ->map(fn ($c) => (int) $c)
            ->all();

        return [
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ];
    }

    /**
     * Get 6-month normalized trend with valid inter-month comparative scales.
     *
     * @return array{months: array<int, array<string, mixed>>, max_global: int}
     */
    public function getSixMonthTrend(?int $divisionId): array
    {
        $series = $this->getMonthlySeries($divisionId, 6);
        $monthKeys = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $trendRows = $monthKeys->map(function (string $ym) use ($series) {
            $in = $series['incoming'][$ym] ?? 0;
            $out = $series['outgoing'][$ym] ?? 0;
            [$year, $month] = explode('-', $ym);
            $monthName = self::INDONESIAN_MONTHS[$month] ?? $month;

            return [
                'month' => $ym,
                'label' => "{$monthName} {$year}",
                'incoming' => $in,
                'outgoing' => $out,
                'total' => $in + $out,
            ];
        });

        $maxGlobal = max(1, $trendRows->max('total') ?? 1);

        $normalized = $trendRows->map(function ($row) use ($maxGlobal) {
            $row['max_global'] = $maxGlobal;
            $row['incoming_width'] = round(($row['incoming'] / $maxGlobal) * 100, 2);
            $row['outgoing_width'] = round(($row['outgoing'] / $maxGlobal) * 100, 2);
            return $row;
        })->all();

        return [
            'months' => $normalized,
            'max_global' => $maxGlobal,
        ];
    }

    /**
     * Get cross-division aggregated volumes for Super Admin.
     */
    public function getDivisionMatrix(): EloquentCollection
    {
        return Division::query()
            ->withCount(['incomingLetters', 'outgoingLetters', 'piagams'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get latest incoming and outgoing letters scoped to active division context.
     *
     * @return array{incoming: EloquentCollection, outgoing: EloquentCollection}
     */
    public function getRecentLetters(?int $divisionId, int $limit = 5): array
    {
        return [
            'incoming' => $this->scoped(IncomingLetter::query(), $divisionId)
                ->latest('received_date')
                ->latest('id')
                ->limit($limit)
                ->get(),
            'outgoing' => $this->scoped(OutgoingLetter::query(), $divisionId)
                ->latest('letter_date')
                ->latest('id')
                ->limit($limit)
                ->get(),
        ];
    }

    /**
     * Get recent letters recorded by a specific operator.
     *
     * @return array{incoming: EloquentCollection, outgoing: EloquentCollection}
     */
    public function getOperatorPersonalRecent(int $userId, ?int $divisionId, int $limit = 5): array
    {
        return [
            'incoming' => $this->scoped(IncomingLetter::query(), $divisionId)
                ->where('created_by_user_id', $userId)
                ->latest('id')
                ->limit($limit)
                ->get(),
            'outgoing' => $this->scoped(OutgoingLetter::query(), $divisionId)
                ->where('created_by_user_id', $userId)
                ->latest('id')
                ->limit($limit)
                ->get(),
        ];
    }

    /**
     * Get quality & attention indicators for Division Admin.
     *
     * @return array{missing_attachments_count: int}
     */
    public function getDataAttentionItems(?int $divisionId): array
    {
        $missingCount = Attachment::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->where('is_missing', true)
            ->count();

        return [
            'missing_attachments_count' => $missingCount,
        ];
    }

    /**
     * Get recent audit logs with humanized description formatting.
     */
    public function getRecentActivities(?int $divisionId, int $limit = 8): Collection
    {
        $logs = AuditLog::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->latest('id')
            ->limit($limit)
            ->get();

        return $logs->map(function (AuditLog $log) {
            $log->human_event = $this->humanizeEventCode($log->event_code);
            return $log;
        });
    }

    /**
     * Get system health and backup summary for Super Admin.
     *
     * @return array{last_backup: ?BackupRun, total_completed_backups: int, is_healthy: bool}
     */
    public function getSystemHealthSummary(): array
    {
        $lastBackup = BackupRun::query()->latest('id')->first();
        $completedCount = BackupRun::query()->where('status', 'completed')->count();

        return [
            'last_backup' => $lastBackup,
            'total_completed_backups' => $completedCount,
            'is_healthy' => $lastBackup ? $lastBackup->status === 'completed' : true,
        ];
    }

    /**
     * Translate technical event codes into staff-friendly Indonesian descriptions.
     */
    public function humanizeEventCode(?string $eventCode): string
    {
        return match ($eventCode) {
            'AUTH_LOGIN' => 'Masuk ke sistem',
            'AUTH_LOGOUT' => 'Keluar dari sistem',
            'AUTH_PASSWORD_RESET' => 'Atur ulang kata sandi',
            'INCOMING_LETTER_CREATED' => 'Mencatat surat masuk baru',
            'INCOMING_LETTER_UPDATED' => 'Memperbarui data surat masuk',
            'INCOMING_LETTER_DELETED' => 'Menghapus surat masuk ke sampah',
            'INCOMING_LETTER_RESTORED' => 'Memulihkan arsip surat masuk',
            'OUTGOING_LETTER_CREATED' => 'Mencatat surat keluar baru',
            'OUTGOING_LETTER_UPDATED' => 'Memperbarui data surat keluar',
            'OUTGOING_LETTER_DELETED' => 'Menghapus surat keluar ke sampah',
            'OUTGOING_LETTER_RESTORED' => 'Memulihkan arsip surat keluar',
            'PIAGAM_CREATED' => 'Mencatat piagam penghargaan',
            'PIAGAM_CLAIMED' => 'Serah terima / klaim piagam',
            'BACKUP_CREATED' => 'Pencadangan database sistem',
            'BACKUP_RESTORED' => 'Pemulihan cadangan data',
            'TEST_EVENT' => 'Aktivitas pengujian sistem',
            default => $eventCode ? ucwords(strtolower(str_replace('_', ' ', $eventCode))) : 'Aktivitas Sistem',
        };
    }

    private function scoped($query, ?int $divisionId)
    {
        return $query->when($divisionId !== null, fn ($q) => $q->where('division_id', $divisionId));
    }
}
