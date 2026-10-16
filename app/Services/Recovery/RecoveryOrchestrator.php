<?php

namespace App\Services\Recovery;

use App\Services\Backup\BackupConsistencyService;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class RecoveryOrchestrator
{
    public function __construct(
        protected ArchiveExtractorService $extractor,
        protected DatabaseRecoveryService $databaseRecovery,
        protected FileRecoveryService $fileRecovery,
        protected BackupConsistencyService $consistencyService
    ) {}

    /**
     * Run the complete Bare-Metal Disaster Recovery workflow from an encrypted archive.
     *
     * @param  string  $encryptedArchivePath  Path to the .enc archive file
     * @param  array  $options  Configuration options: key, skip_database, skip_files, verify_only, connection, staging_dir
     * @return array Complete execution metrics and status report
     */
    public function recover(string $encryptedArchivePath, array $options = []): array
    {
        $startTime = microtime(true);
        $passphrase = $options['key'] ?? null;
        $stagingDir = $options['staging_dir'] ?? null;
        $verifyOnly = (bool) ($options['verify_only'] ?? false);
        $skipDatabase = (bool) ($options['skip_database'] ?? false);
        $skipFiles = (bool) ($options['skip_files'] ?? false);
        $connection = $options['connection'] ?? null;

        // Stage 1: Extraction & Cryptographic / Integrity Validation
        $extraction = $this->extractor->extract($encryptedArchivePath, $stagingDir, $passphrase);
        $manifest = $extraction['manifest'] ?? [];
        $activeStagingDir = $extraction['staging_dir'];

        try {
            // Stage 2: Verify-Only Dry Run Mode
            if ($verifyOnly) {
                $sqlDumpExists = ! empty($extraction['sql_dump_path']) && File::exists($extraction['sql_dump_path']);
                $sqlHashMatch = $sqlDumpExists && hash_file('sha256', $extraction['sql_dump_path']) === ($manifest['database']['sha256'] ?? '');

                return [
                    'success' => true,
                    'verify_only' => true,
                    'backup_id' => $manifest['backup_id'] ?? 'unknown',
                    'manifest' => $manifest,
                    'integrity' => [
                        'checksums_verified' => $extraction['checksums_verified'],
                        'sql_dump_exists' => $sqlDumpExists,
                        'sql_dump_hash_match' => $sqlHashMatch,
                        'documents_directory_exists' => ! empty($extraction['documents_path']) && File::isDirectory($extraction['documents_path']),
                        'signatures_directory_exists' => ! empty($extraction['signatures_path']) && File::isDirectory($extraction['signatures_path']),
                    ],
                    'duration_seconds' => round(microtime(true) - $startTime, 3),
                ];
            }

            // Stage 3: Database Restoration
            $dbResult = null;
            if (! $skipDatabase) {
                if (empty($extraction['sql_dump_path']) || ! File::exists($extraction['sql_dump_path'])) {
                    throw new RuntimeException('Database restoration requested but SQL dump was not found in archive bundle.');
                }

                $dbResult = $this->databaseRecovery->restore($extraction['sql_dump_path'], $connection);
            }

            // Stage 4: File Storage Restoration
            $filesResult = null;
            if (! $skipFiles) {
                $filesResult = $this->fileRecovery->restore(
                    $extraction['documents_path'],
                    $extraction['signatures_path']
                );
            }

            // Stage 5: Post-Restoration Cross-Reference Consistency Audit
            $consistencyResult = null;
            if (! $skipDatabase) {
                try {
                    $consistencyResult = $this->consistencyService->verifyCrossReference();
                } catch (Throwable) {
                    $consistencyResult = ['status' => 'SKIPPED_OR_UNAVAILABLE'];
                }
            }

            return [
                'success' => true,
                'verify_only' => false,
                'backup_id' => $manifest['backup_id'] ?? 'unknown',
                'manifest' => $manifest,
                'database' => $dbResult,
                'files' => $filesResult,
                'consistency' => $consistencyResult,
                'duration_seconds' => round(microtime(true) - $startTime, 3),
            ];
        } finally {
            // Clean up temporary unencrypted staging data
            $this->extractor->cleanupStaging($activeStagingDir);
        }
    }
}
