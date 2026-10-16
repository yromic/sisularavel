<?php

namespace App\Services\Backup;

use App\Models\BackupRun;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Throwable;

class BackupOrchestrator
{
    public function __construct(
        protected BackupPreflightService $preflight,
        protected BackupConsistencyService $consistency,
        protected BackupManifestService $manifest,
        protected BackupEncryptionService $encryption,
        protected DatabaseDumperService $dumper,
        protected ArchivePackerService $packer,
        protected GoogleDriveStorageService $googleDrive
    ) {}

    /**
     * Run the complete local backup pipeline.
     *
     * @param  array  $options  Supported options: type, trigger, requested_by, encryption_key, dry_run
     *
     * @throws Throwable
     */
    public function run(array $options = []): BackupRun
    {
        $type = $options['type'] ?? 'full';
        $trigger = $options['trigger'] ?? 'manual';
        $requestedBy = $options['requested_by'] ?? null;
        $passphrase = $options['encryption_key'] ?? null;
        $isDryRun = (bool) ($options['dry_run'] ?? false);
        $shouldUpload = (bool) ($options['upload'] ?? (config('backup.remote.enabled', false) && $this->googleDrive->isConfigured()));
        if (isset($options['no_upload']) && $options['no_upload']) {
            $shouldUpload = false;
        }

        // 1. Acquire Atomic Exclusive Lock
        $this->consistency->acquireLock(3600);

        $backupId = $this->generateBackupId();

        // 2. Initialize BackupRun record in database
        $run = BackupRun::query()->create([
            'backup_id' => $backupId,
            'type' => $type,
            'trigger' => $trigger,
            'requested_by' => $requestedBy,
            'status' => 'pending',
            'started_at' => now(),
            'remote_storage_provider' => 'local',
        ]);

        $stagingBase = config('backup.temp_path', storage_path('app/private/backup-temp'));
        $stagingDir = $stagingBase.DIRECTORY_SEPARATOR.$backupId;
        $rawBundlePath = $stagingBase.DIRECTORY_SEPARATOR.$backupId.'.bundle';
        $encryptedDestinationDir = storage_path('app/private/backups');
        $encryptedFilePath = $encryptedDestinationDir.DIRECTORY_SEPARATOR.$backupId.'.enc';

        try {
            // 3. Preflight Checks
            $audit = $this->preflight->runAudit();
            if (! $audit['is_ready']) {
                $failedChecks = array_filter($audit['checks'], fn ($c) => $c['status'] === 'FAIL');
                $failMsg = implode('; ', array_map(fn ($c) => $c['name'].': '.$c['message'], $failedChecks));
                throw new RuntimeException("Preflight checks failed: {$failMsg}");
            }

            // 4. Preparing Stage
            $run->transitionTo('preparing');

            if (File::isDirectory($stagingDir)) {
                File::deleteDirectory($stagingDir);
            }
            File::makeDirectory($stagingDir, 0755, true, true);

            if ($isDryRun) {
                // Dry run terminates cleanly after preflight & staging preparation
                $crossRef = $this->consistency->verifyCrossReference();
                $run->transitionTo('completed', [
                    'completed_at' => now(),
                    'error_message' => 'DRY_RUN_SUCCESS: Preflight passed, cross-reference verified.',
                ]);
                File::deleteDirectory($stagingDir);
                $this->consistency->releaseLock();

                return $run;
            }

            // 5. Activate Brief Consistency Window
            $this->consistency->enterConsistencyWindow(180);

            // 6. Archiving Stage
            $run->transitionTo('archiving');

            $dbStats = [
                'dump_file' => 'N/A',
                'size_bytes' => 0,
                'sha256' => '',
                'tables_count' => 0,
                'version' => 'Unknown',
            ];

            // 6A. Database Dump
            if ($type === 'full' || $type === 'database') {
                $sqlDest = $stagingDir.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'sisurat.sql';
                $dbStats = $this->dumper->dump($sqlDest);
            }

            // 6B. Private Storage Files Mirroring
            $docStats = ['file_count' => 0, 'total_size_bytes' => 0];
            $sigStats = ['file_count' => 0, 'total_size_bytes' => 0];

            if ($type === 'full' || $type === 'files') {
                $docStats = $this->mirrorDiskToStaging(
                    'private_documents',
                    $stagingDir.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'documents'
                );

                $sigStats = $this->mirrorDiskToStaging(
                    'private_signatures',
                    $stagingDir.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'signatures'
                );
            }

            // 6C. Exit Consistency Window Immediately After Files & DB are snapshotted
            $this->consistency->exitConsistencyWindow();

            // 6D. Verify Cross-Reference
            $crossRef = $this->consistency->verifyCrossReference();

            // 6E. Generate manifest.json
            $manifestData = $this->manifest->buildManifest(
                $backupId,
                $type,
                $dbStats,
                $docStats,
                $sigStats,
                $run->started_at->toIso8601String()
            );
            $this->manifest->writeManifest($stagingDir, $manifestData);

            // 6F. Generate checksums.sha256
            $this->manifest->generateChecksumsFile($stagingDir);

            // 6G. Count total internal files
            $finder = new Finder;
            $finder->files()->in($stagingDir);
            $totalFilesCount = $finder->count();

            // 6H. Bundle into unencrypted archive
            $actualBundlePath = $this->packer->pack($stagingDir, $rawBundlePath);

            // 7. Encrypting Stage
            $run->transitionTo('encrypting');

            $encMeta = $this->encryption->encryptArchive($actualBundlePath, $encryptedFilePath, $passphrase);

            $remoteProvider = 'local';
            $remoteFileId = null;

            // 8. Remote Off-Site Storage Stage (Google Drive Integration)
            if ($shouldUpload) {
                $run->transitionTo('uploading');
                $uploadResult = $this->googleDrive->uploadArchive($encryptedFilePath);

                // 9. Remote Verification Stage
                $run->transitionTo('verifying');
                $remoteFileId = $uploadResult['file_id'] ?? null;
                $verification = $this->googleDrive->verifyRemoteFile($remoteFileId, $encMeta['size_bytes']);

                if (! $verification['verified']) {
                    $err = $verification['error'] ?? 'Remote size does not match expected archive size.';
                    throw new RuntimeException("Remote Google Drive archive verification failed: {$err}");
                }

                $remoteProvider = 'google_drive';
            }

            // 10. Completed Stage
            $run->transitionTo('completed', [
                'completed_at' => now(),
                'archive_size_bytes' => $encMeta['size_bytes'],
                'files_count' => $totalFilesCount,
                'sha256_checksum' => $encMeta['sha256'],
                'remote_storage_provider' => $remoteProvider,
                'remote_file_id' => $remoteFileId,
            ]);

            // 9. Cleanup unencrypted temporary files
            if (File::isDirectory($stagingDir)) {
                File::deleteDirectory($stagingDir);
            }
            if (File::exists($actualBundlePath)) {
                File::delete($actualBundlePath);
            }

            // Release lock
            $this->consistency->releaseLock();

            return $run;
        } catch (Throwable $e) {
            $this->consistency->exitConsistencyWindow();

            // Clean staging artifacts on failure
            if (File::isDirectory($stagingDir)) {
                @File::deleteDirectory($stagingDir);
            }
            if (isset($actualBundlePath) && File::exists($actualBundlePath)) {
                @File::delete($actualBundlePath);
            }

            $run->markFailed('BACKUP_PIPELINE_ERROR', $e->getMessage());

            $this->consistency->releaseLock();

            throw $e;
        }
    }

    /**
     * Mirror all files from a storage disk into the staging directory.
     */
    protected function mirrorDiskToStaging(string $diskName, string $targetDir): array
    {
        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
        }

        $fileCount = 0;
        $totalBytes = 0;

        try {
            $files = Storage::disk($diskName)->allFiles();
            foreach ($files as $relPath) {
                $sourceFullPath = Storage::disk($diskName)->path($relPath);
                $destFullPath = $targetDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relPath);

                $destSubDir = dirname($destFullPath);
                if (! File::isDirectory($destSubDir)) {
                    File::makeDirectory($destSubDir, 0755, true, true);
                }

                if (File::exists($sourceFullPath)) {
                    File::copy($sourceFullPath, $destFullPath);
                    $fileCount++;
                    $totalBytes += File::size($destFullPath);
                }
            }
        } catch (Throwable) {
            // Disk may be empty or uninitialized
        }

        return [
            'file_count' => $fileCount,
            'total_size_bytes' => $totalBytes,
        ];
    }

    /**
     * Generate unique backup ID: bkp-YYYYMMDD-HHmmss
     */
    public function generateBackupId(): string
    {
        $baseId = 'bkp-'.now()->format('Ymd-His');
        $id = $baseId;
        $counter = 1;

        $stagingBase = config('backup.temp_path', storage_path('app/private/backup-temp'));
        $backupsDir = storage_path('app/private/backups');

        while (
            BackupRun::query()->where('backup_id', $id)->exists()
            || File::exists($stagingBase.DIRECTORY_SEPARATOR.$id)
            || File::exists($stagingBase.DIRECTORY_SEPARATOR.$id.'.bundle.tar')
            || File::exists($backupsDir.DIRECTORY_SEPARATOR.$id.'.enc')
        ) {
            $id = $baseId.'-'.str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $id;
    }
}
