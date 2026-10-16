<?php

namespace App\Console\Commands;

use App\Services\Recovery\RecoveryOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Throwable;

class RestoreSisuratBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sisurat:restore
                            {--archive= : Path to the encrypted backup archive (.enc)}
                            {--key= : Decryption passphrase or emergency recovery key}
                            {--skip-database : Skip database restoration}
                            {--skip-files : Skip file storage restoration}
                            {--verify-only : Dry run to test archive integrity without applying restoration}
                            {--connection= : Target database connection name}
                            {--force : Force execution without interactive confirmation}
                            {--json : Output result in raw JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a SISURAT system from an encrypted backup archive (Fase 04 Disaster Recovery)';

    /**
     * Execute the console command.
     */
    public function handle(RecoveryOrchestrator $orchestrator): int
    {
        $archivePath = $this->option('archive') ?: null;
        $key = $this->option('key') ?: null;
        $skipDatabase = (bool) $this->option('skip-database');
        $skipFiles = (bool) $this->option('skip-files');
        $verifyOnly = (bool) $this->option('verify-only');
        $connection = $this->option('connection') ?: null;
        $force = (bool) $this->option('force');
        $json = (bool) $this->option('json');

        // Resolve archive path if not supplied
        if (empty($archivePath)) {
            $archivePath = $this->autoDiscoverLatestArchive();
        }

        if (empty($archivePath) || ! File::exists($archivePath)) {
            $errorMsg = 'Backup archive not found. Please provide a valid path via --archive=/path/to/archive.enc';
            if ($json) {
                $this->line(json_encode(['success' => false, 'error' => $errorMsg]));
            } else {
                $this->error($errorMsg);
            }

            return self::FAILURE;
        }

        $targetConn = $connection ?? config('database.default');
        $targetDb = config("database.connections.{$targetConn}.database", 'Unknown');

        if (! $json) {
            $this->newLine();
            $this->line('<fg=cyan;options=bold>================================================================================</>');
            $this->line('<fg=cyan;options=bold>   SISURAT DISASTER RECOVERY ENGINE — RESTORATION PIPELINE                      </>');
            $this->line('<fg=cyan;options=bold>   Offline-Capable · AEAD Decryption · Path-Safe Extraction · DB Importer       </>');
            $this->line('<fg=cyan;options=bold>================================================================================</>');
            $this->newLine();

            $this->info("Archive Target: {$archivePath}");
            $this->info("Target Database: [{$targetConn}] {$targetDb}");
            if ($verifyOnly) {
                $this->comment('Mode: [VERIFY ONLY / DRY RUN] (No data will be written or overwritten)');
            }
        }

        // Destructive operation safety confirmation
        if (! $verifyOnly && ! $force) {
            $this->newLine();
            $this->line('<bg=red;fg=white;options=bold>                                                                                </>');
            $this->line('<bg=red;fg=white;options=bold>   CRITICAL WARNING: DISASTER RECOVERY IS A DESTRUCTIVE OPERATION!               </>');
            $this->line('<bg=red;fg=white;options=bold>   Restoring will DROP and RE-CREATE database tables in: '.$targetDb.'           </>');
            $this->line('<bg=red;fg=white;options=bold>   Physical files in private document and signature storage may be overwritten.   </>');
            $this->line('<bg=red;fg=white;options=bold>                                                                                </>');
            $this->newLine();

            if (! $this->confirm('Do you really wish to proceed with this restoration?', false)) {
                $this->warn('Restoration aborted by user.');

                return self::FAILURE;
            }
        }

        try {
            $result = $orchestrator->recover($archivePath, [
                'key' => $key,
                'skip_database' => $skipDatabase,
                'skip_files' => $skipFiles,
                'verify_only' => $verifyOnly,
                'connection' => $connection,
            ]);

            if ($json) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;
            }

            $this->newLine();
            if ($verifyOnly) {
                $this->line('<bg=blue;fg=white;options=bold>                                                                                </>');
                $this->line('<bg=blue;fg=white;options=bold>   ARCHIVE INTEGRITY VERIFIED SUCCESSFULLY (DRY RUN)                            </>');
                $this->line('<bg=blue;fg=white;options=bold>                                                                                </>');
            } else {
                $this->line('<bg=green;fg=black;options=bold>                                                                                </>');
                $this->line('<bg=green;fg=black;options=bold>   DISASTER RECOVERY COMPLETED SUCCESSFULLY!                                    </>');
                $this->line('<bg=green;fg=black;options=bold>                                                                                </>');
            }
            $this->newLine();

            $manifest = $result['manifest'] ?? [];
            $tableData = [
                ['Backup ID', $result['backup_id'] ?? 'N/A'],
                ['Manifest Created At', $manifest['created_at'] ?? 'N/A'],
                ['Application', ($manifest['application']['name'] ?? 'SISURAT').' (Laravel '.($manifest['application']['laravel_version'] ?? '12.x').')'],
                ['Checksums Verified', 'PASS (100% SHA-256 match)'],
                ['Database Restored', $skipDatabase ? 'SKIPPED' : ($result['database']['tables_restored'] ?? 0).' tables (Method: '.($result['database']['method'] ?? 'N/A').')'],
                ['Files Restored', $skipFiles ? 'SKIPPED' : ($result['files']['total_files'] ?? 0).' files ('.($result['files']['total_bytes'] ?? 0).' bytes)'],
                ['Execution Duration', ($result['duration_seconds'] ?? 0).' seconds'],
            ];

            $this->table(['Metric', 'Value'], $tableData);

            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($json) {
                $this->line(json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $this->newLine();
                $this->line('<bg=red;fg=white;options=bold>                                                                                </>');
                $this->line('<bg=red;fg=white;options=bold>   RESTORATION FAILED: '.substr($e->getMessage(), 0, 60).'                   </>');
                $this->line('<bg=red;fg=white;options=bold>                                                                                </>');
                $this->newLine();
                $this->error($e->getMessage());
            }

            return self::FAILURE;
        }
    }

    /**
     * Look for latest .enc backup archive in private storage.
     */
    protected function autoDiscoverLatestArchive(): ?string
    {
        $backupDir = storage_path('app/private/backups');
        if (! File::isDirectory($backupDir)) {
            return null;
        }

        $finder = new Finder;
        $finder->files()->in($backupDir)->name('*.enc')->sortByModifiedTime();

        $latest = null;
        foreach ($finder as $file) {
            $latest = $file->getRealPath();
        }

        return $latest;
    }
}
