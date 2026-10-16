<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupPreflightService;
use Illuminate\Console\Command;

class AuditSisuratBackupEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sisurat:backup:audit
                            {--json : Output raw audit findings as JSON}
                            {--strict : Treat non-critical warnings as failures}
                            {--summary-only : Output only the brief status summary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute a read-only technical audit of the hosting environment for SISURAT backup readiness (Fase 01)';

    /**
     * Execute the console command.
     */
    public function handle(BackupPreflightService $preflight): int
    {
        $audit = $preflight->runAudit();

        // 1. JSON output flag
        if ($this->option('json')) {
            $this->output->writeln(json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $audit['is_ready'] ? self::SUCCESS : self::FAILURE;
        }

        // 2. Summary-only output flag
        if ($this->option('summary-only')) {
            if ($audit['is_ready']) {
                $this->info("READY: {$audit['summary']['passed']}/{$audit['summary']['total_checks']} checks passed ({$audit['summary']['warnings']} warnings).");

                return self::SUCCESS;
            }

            $this->error("NOT READY: {$audit['summary']['failed']} critical failure(s) detected.");

            return self::FAILURE;
        }

        // 3. Human-readable institutional console report
        $this->renderHeader();
        $this->renderSystemOverview($audit['system_info']);
        $this->renderStorageInventory($audit['storage_stats']);
        $this->renderAuditTable($audit['checks']);
        $this->renderVerdict($audit);

        $isStrict = $this->option('strict');
        if ($isStrict && $audit['has_warnings']) {
            $this->error('Strict mode active: Audit failed due to warnings.');

            return self::FAILURE;
        }

        return $audit['is_ready'] ? self::SUCCESS : self::FAILURE;
    }

    protected function renderHeader(): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>================================================================================</>');
        $this->line('<fg=cyan;options=bold>   SISURAT BACKUP & DISASTER RECOVERY — READ-ONLY TECHNICAL AUDIT (FASE 01)    </>');
        $this->line('<fg=cyan;options=bold>   Governed by docs/backup.md · Laravel 12 · PHP ^8.2 · Shared-Hosting Ready   </>');
        $this->line('<fg=cyan;options=bold>================================================================================</>');
        $this->newLine();
    }

    protected function renderSystemOverview(array $info): void
    {
        $this->line('<fg=white;options=bold>1. System & Runtime Environment</>');
        $this->table(
            ['Environment Variable', 'Observed Value'],
            [
                ['Operating System', $info['os']],
                ['PHP Version', $info['php_version']],
                ['PHP SAPI', $info['sapi']],
                ['Database Engine', $info['db_version']],
                ['Configured Memory Limit', $info['memory_limit']],
                ['Max Execution Time (CLI)', $info['max_execution_time'] === 0 ? 'Unlimited (0s)' : $info['max_execution_time'].'s'],
            ]
        );
        $this->newLine();
    }

    protected function renderStorageInventory(array $stats): void
    {
        $this->line('<fg=white;options=bold>2. Storage & Relational Inventory</>');

        $totalGb = round($stats['disk_total_bytes'] / (1024 * 1024 * 1024), 2);
        $usedGb = round($stats['disk_used_bytes'] / (1024 * 1024 * 1024), 2);
        $freeGb = round($stats['disk_free_bytes'] / (1024 * 1024 * 1024), 2);

        $docMb = round($stats['documents_size_bytes'] / (1024 * 1024), 2);
        $sigKb = round($stats['signatures_size_bytes'] / 1024, 2);
        $dbMb = round($stats['database_size_bytes'] / (1024 * 1024), 2);

        $this->table(
            ['Metric', 'Current Value', 'Capacity Assessment'],
            [
                ['SSD Storage Total', "{$totalGb} GB", 'Server disk boundary'],
                ['SSD Storage Used', "{$usedGb} GB ({$stats['disk_used_percent']}%)", $stats['disk_used_percent'] < 70 ? 'Optimal (<= 70%)' : ($stats['disk_used_percent'] < 90 ? 'Warning (>= 75%)' : 'Critical (>= 90%)')],
                ['SSD Storage Free', "{$freeGb} GB", 'Available for temp snapshots & archives'],
                ['Private Documents', "{$stats['documents_count']} files ({$docMb} MB)", 'storage/app/private/documents'],
                ['Private Signatures', "{$stats['signatures_count']} files ({$sigKb} KB)", 'storage/app/private/signatures'],
                ['Database Size', "{$stats['database_tables_count']} tables ({$dbMb} MB)", 'MySQL/MariaDB active schema'],
            ]
        );
        $this->newLine();
    }

    protected function renderAuditTable(array $checks): void
    {
        $this->line('<fg=white;options=bold>3. Detailed Prerequisite Audit Results</>');

        $rows = [];
        foreach ($checks as $c) {
            $badge = match ($c['status']) {
                'PASS' => '<fg=green;options=bold>[ PASS ]</>',
                'WARN' => '<fg=yellow;options=bold>[ WARN ]</>',
                'FAIL' => '<fg=red;options=bold>[ FAIL ]</>',
                default => "[ {$c['status']} ]",
            };

            $rows[] = [
                $badge,
                $c['category'],
                $c['name'],
                $c['message'],
            ];
        }

        $this->table(['Status', 'Category', 'Criterion', 'Audit Findings & Notes'], $rows);
        $this->newLine();
    }

    protected function renderVerdict(array $audit): void
    {
        $summary = $audit['summary'];

        $this->line('<fg=white;options=bold>4. Final Readiness Verdict</>');
        $this->line("Total Checks: {$summary['total_checks']}  |  Passed: {$summary['passed']}  |  Warnings: {$summary['warnings']}  |  Failed: {$summary['failed']}");
        $this->newLine();

        if ($audit['is_ready']) {
            $this->line('<bg=green;fg=black;options=bold>                                                                                </>');
            $this->line('<bg=green;fg=black;options=bold>  SUCCESS: SERVER ENVIRONMENT MEETS ALL FASE 01 TECHNICAL AUDIT PREREQUISITES!   </>');
            $this->line('<bg=green;fg=black;options=bold>  System is fully prepared to proceed with Fase 02 (Local Backup Core).        </>');
            $this->line('<bg=green;fg=black;options=bold>                                                                                </>');

            if ($audit['has_warnings']) {
                $this->newLine();
                $this->warn('NOTE: Non-critical warnings observed above. They will not block backup operations, but review recommendations before production cutover.');
            }
        } else {
            $this->line('<bg=red;fg=white;options=bold>                                                                                </>');
            $this->line('<bg=red;fg=white;options=bold>  CRITICAL: SERVER ENVIRONMENT FAILED ONE OR MORE MANDATORY AUDIT CHECKS!       </>');
            $this->line('<bg=red;fg=white;options=bold>  Resolve critical items marked [ FAIL ] before activating backup routines.     </>');
            $this->line('<bg=red;fg=white;options=bold>                                                                                </>');
        }
        $this->newLine();
    }
}
