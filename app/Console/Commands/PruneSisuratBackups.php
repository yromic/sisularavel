<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRetentionService;
use Illuminate\Console\Command;

class PruneSisuratBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sisurat:backup:prune
                            {--dry-run : Simulate pruning without deleting files or records}
                            {--json : Output result in raw JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old SISURAT backup archives based on rolling retention policy (Fase 05)';

    /**
     * Execute the console command.
     */
    public function handle(BackupRetentionService $retentionService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $json = (bool) $this->option('json');

        $result = $retentionService->prune($dryRun);

        if ($json) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>================================================================================</>');
        $this->line('<fg=cyan;options=bold>   SISURAT BACKUP RETENTION ENGINE — ROLLING ROTATION                           </>');
        $this->line('<fg=cyan;options=bold>   Grandfather-Father-Son Policy · Golden Rule Integrity · Local & Cloud        </>');
        $this->line('<fg=cyan;options=bold>================================================================================</>');
        $this->newLine();

        if ($dryRun) {
            $this->comment('Mode: [DRY RUN / SIMULATION] (No files or records deleted)');
        }

        if (! empty($result['reason'])) {
            $this->info("Notice: {$result['reason']}");
        }

        $mbReclaimed = round($result['reclaimed_bytes'] / (1024 * 1024), 2);
        $summaryTable = [
            ['Evaluated Archives', $result['evaluated_count']],
            ['Kept Archives', $result['kept_count']],
            ['Pruned Archives', $result['pruned_count'].($dryRun ? ' (Simulated)' : '')],
            ['Reclaimed Space', "{$mbReclaimed} MB ({$result['reclaimed_bytes']} bytes)"],
        ];

        $this->table(['Metric', 'Count / Value'], $summaryTable);

        if (! empty($result['pruned_runs'])) {
            $this->newLine();
            $this->info('Pruned Archive Details:');
            $prunedRows = collect($result['pruned_runs'])->map(fn ($item) => [
                $item['backup_id'],
                $item['started_at'],
                round(($item['size_bytes'] ?? 0) / 1024, 2).' KB',
                $item['local_deleted'] ? 'DELETED' : ($dryRun ? 'WOULD_DELETE' : 'NOT_FOUND'),
                $item['remote_deleted'] ? 'DELETED' : ($dryRun ? 'WOULD_DELETE' : 'N/A'),
            ])->all();

            $this->table(['Backup ID', 'Date Created', 'Size', 'Local Disk', 'Google Drive'], $prunedRows);
        }

        return self::SUCCESS;
    }
}
