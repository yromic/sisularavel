<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupOrchestrator;
use Illuminate\Console\Command;
use Throwable;

class CreateSisuratBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sisurat:backup
                            {--type=full : Backup type: full, database, files}
                            {--trigger=manual : Trigger mode: manual, scheduled}
                            {--dry-run : Validate preflight without generating archives}
                            {--key= : Override encryption passphrase for this run}
                            {--upload : Force upload to Google Drive if configured}
                            {--no-upload : Skip remote upload and retain archive locally only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an atomic, encrypted SISURAT backup archive (Fase 02-03 Core & Google Drive)';

    /**
     * Execute the console command.
     */
    public function handle(BackupOrchestrator $orchestrator): int
    {
        $type = $this->option('type') ?: 'full';
        $trigger = $this->option('trigger') ?: 'manual';
        $isDryRun = (bool) $this->option('dry-run');
        $key = $this->option('key') ?: null;
        $forceUpload = (bool) $this->option('upload');
        $noUpload = (bool) $this->option('no-upload');

        $this->newLine();
        $this->line('<fg=cyan;options=bold>================================================================================</>');
        $this->line('<fg=cyan;options=bold>   SISURAT BACKUP ORCHESTRATOR — DISASTER RECOVERY PIPELINE                    </>');
        $this->line('<fg=cyan;options=bold>   Atomic Consistency · Manifest Contract · Authenticated AES-256-GCM           </>');
        $this->line('<fg=cyan;options=bold>================================================================================</>');
        $this->newLine();

        $this->info("Initiating {$type} backup pipeline (Trigger: {$trigger})".($isDryRun ? ' [DRY RUN]' : '').'...');

        try {
            $options = [
                'type' => $type,
                'trigger' => $trigger,
                'dry_run' => $isDryRun,
                'encryption_key' => $key,
            ];

            if ($forceUpload) {
                $options['upload'] = true;
            }
            if ($noUpload) {
                $options['no_upload'] = true;
            }

            $run = $orchestrator->run($options);

            $this->newLine();
            $this->line('<bg=green;fg=black;options=bold>                                                                                </>');
            $this->line('<bg=green;fg=black;options=bold>   SUCCESS: SISURAT BACKUP ARCHIVE CREATED AND ENCRYPTED SUCCESSFULLY!          </>');
            $this->line('<bg=green;fg=black;options=bold>                                                                                </>');
            $this->newLine();

            $sizeStr = $run->archive_size_bytes !== null
                ? round($run->archive_size_bytes / (1024 * 1024), 2).' MB ('.$run->archive_size_bytes.' bytes)'
                : 'N/A';

            $tableRows = [
                ['Backup Run ID', $run->backup_id],
                ['Type', strtoupper($run->type)],
                ['Trigger', ucfirst($run->trigger)],
                ['Status', strtoupper($run->status)],
                ['Started At', $run->started_at->toDateTimeString()],
                ['Completed At', $run->completed_at ? $run->completed_at->toDateTimeString() : 'N/A'],
                ['Duration', $run->completed_at ? $run->started_at->diffInSeconds($run->completed_at).' seconds' : 'N/A'],
                ['Files Snapshotted', $run->files_count ?? 0],
                ['Encrypted Size', $sizeStr],
                ['SHA-256 Checksum', $run->sha256_checksum ?? 'N/A'],
                ['Storage Provider', strtoupper($run->remote_storage_provider)],
                ['Remote File ID', $run->remote_file_id ?? '(Local Only)'],
                ['Local Path', storage_path("app/private/backups/{$run->backup_id}.enc")],
            ];

            $this->table(['Attribute', 'Value'], $tableRows);
            $this->newLine();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->line('<bg=red;fg=white;options=bold>                                                                                </>');
            $this->line('<bg=red;fg=white;options=bold>   FAILURE: BACKUP PIPELINE ENCOUNTERED AN ERROR!                               </>');
            $this->line('<bg=red;fg=white;options=bold>                                                                                </>');
            $this->newLine();

            $this->error('Error Message: '.$e->getMessage());
            $this->newLine();

            return self::FAILURE;
        }
    }
}
