<?php

namespace App\Console\Commands;

use App\Models\BackupRun;
use App\Services\Backup\GoogleDriveStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifySisuratBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sisurat:backup:verify
                            {--backup-id= : The specific backup run ID to verify (defaults to latest completed)}
                            {--remote : Verify remote offsite copy on Google Drive as well}
                            {--json : Output result in raw JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the physical integrity and remote availability of a SISURAT backup archive';

    /**
     * Execute the console command.
     */
    public function handle(GoogleDriveStorageService $googleDrive): int
    {
        $backupId = $this->option('backup-id');

        $query = BackupRun::query();
        if (! empty($backupId)) {
            $run = $query->where('backup_id', $backupId)->first();
        } else {
            $run = $query->where('status', 'completed')->latest('completed_at')->first();
        }

        if (! $run) {
            $this->error('No completed backup run found'.($backupId ? " with ID '{$backupId}'" : '').'.');

            return self::FAILURE;
        }

        $localPath = storage_path("app/private/backups/{$run->backup_id}.enc");
        $localExists = File::exists($localPath);
        $localSize = $localExists ? File::size($localPath) : 0;
        $localSha256 = $localExists ? hash_file('sha256', $localPath) : null;

        $localChecksumValid = $localExists && ($localSha256 === $run->sha256_checksum);
        $localSizeValid = $localExists && ($localSize === $run->archive_size_bytes);

        $remoteCheck = null;
        $remoteValid = true;

        if ($run->remote_storage_provider === 'google_drive' && ! empty($run->remote_file_id)) {
            $remoteCheck = $googleDrive->verifyRemoteFile($run->remote_file_id, (int) $run->archive_size_bytes);
            $remoteValid = (bool) ($remoteCheck['verified'] ?? false);
        }

        $isVerified = $localChecksumValid && $localSizeValid && $remoteValid;

        $result = [
            'backup_id' => $run->backup_id,
            'is_verified' => $isVerified,
            'status' => $run->status,
            'local' => [
                'path' => $localPath,
                'exists' => $localExists,
                'expected_size' => $run->archive_size_bytes,
                'actual_size' => $localSize,
                'size_matches' => $localSizeValid,
                'expected_sha256' => $run->sha256_checksum,
                'actual_sha256' => $localSha256,
                'checksum_matches' => $localChecksumValid,
            ],
            'remote' => $remoteCheck ?? [
                'provider' => $run->remote_storage_provider,
                'file_id' => $run->remote_file_id,
                'status' => 'Not uploaded off-site (Local only)',
            ],
        ];

        if ($this->option('json')) {
            $this->output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $isVerified ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>================================================================================</>');
        $this->line('<fg=cyan;options=bold>   SISURAT BACKUP INTEGRITY & REMOTE VERIFICATION                               </>');
        $this->line('<fg=cyan;options=bold>================================================================================</>');
        $this->newLine();

        $this->table(
            ['Verification Check', 'Result', 'Details'],
            [
                ['Backup ID', $run->backup_id, "Recorded at {$run->created_at}"],
                ['Local Archive File', $localExists ? '<fg=green>EXISTS</>' : '<fg=red>MISSING</>', $localPath],
                ['Local File Size', $localSizeValid ? '<fg=green>MATCHES</>' : '<fg=red>MISMATCH</>', "{$localSize} bytes (Expected: {$run->archive_size_bytes})"],
                ['Local SHA-256 Checksum', $localChecksumValid ? '<fg=green>VALID</>' : '<fg=red>INVALID</>', $localSha256 ?? 'N/A'],
                [
                    'Remote Google Drive',
                    $remoteCheck ? ($remoteValid ? '<fg=green>VERIFIED</>' : '<fg=red>FAILED</>') : '<fg=yellow>LOCAL ONLY</>',
                    $remoteCheck ? "File ID: {$run->remote_file_id} (Size matches: ".($remoteCheck['size_matches'] ? 'YES' : 'NO').')' : 'No offsite remote ID recorded',
                ],
            ]
        );
        $this->newLine();

        if ($isVerified) {
            $this->info('VERIFICATION SUCCESSFUL: The backup archive is intact and matches all integrity requirements.');

            return self::SUCCESS;
        }

        $this->error('VERIFICATION FAILED: One or more integrity checks failed.');

        return self::FAILURE;
    }
}
