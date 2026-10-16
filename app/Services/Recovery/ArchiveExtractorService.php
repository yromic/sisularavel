<?php

namespace App\Services\Recovery;

use App\Services\Backup\ArchivePackerService;
use App\Services\Backup\BackupEncryptionService;
use App\Services\Backup\BackupManifestService;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class ArchiveExtractorService
{
    public function __construct(
        protected BackupEncryptionService $encryptionService,
        protected ArchivePackerService $packerService,
        protected BackupManifestService $manifestService
    ) {}

    /**
     * Decrypt and extract a SISURAT encrypted archive (.enc) safely into a staging folder.
     *
     * Performs end-to-end cryptographic integrity verification, path traversal checks,
     * manifest validation, and SHA-256 checksum verification.
     *
     * @param  string  $encryptedArchivePath  Absolute path to the .enc backup archive
     * @param  string|null  $destinationStagingDir  Optional custom staging folder
     * @param  string|null  $passphrase  Optional custom encryption passphrase
     * @return array Extraction details, parsed manifest, and resolved component paths
     */
    public function extract(
        string $encryptedArchivePath,
        ?string $destinationStagingDir = null,
        ?string $passphrase = null
    ): array {
        if (! File::exists($encryptedArchivePath)) {
            throw new RuntimeException("Encrypted backup archive not found: {$encryptedArchivePath}");
        }

        $minLen = strlen(BackupEncryptionService::MAGIC_HEADER)
            + BackupEncryptionService::IV_LENGTH
            + BackupEncryptionService::TAG_LENGTH;

        if (File::size($encryptedArchivePath) < $minLen) {
            throw new RuntimeException("Corrupted backup archive: file size is smaller than AEAD header and tag ({$encryptedArchivePath})");
        }

        // 1. Prepare target staging directory
        $stagingDir = $destinationStagingDir ?? $this->resolveDefaultStagingPath();
        if (! File::isDirectory($stagingDir)) {
            File::makeDirectory($stagingDir, 0755, true, true);
        }

        $decryptedTempPath = $stagingDir.DIRECTORY_SEPARATOR.'bundle.decrypted.tmp';

        try {
            // 2. Decrypt archive using AEAD AES-256-GCM
            $this->encryptionService->decryptArchive($encryptedArchivePath, $decryptedTempPath, $passphrase);

            // 3. Unpack decrypted archive
            $this->packerService->unpack($decryptedTempPath, $stagingDir);
        } finally {
            // Erase unencrypted archive container immediately
            if (File::exists($decryptedTempPath)) {
                File::delete($decryptedTempPath);
            }
        }

        // 4. Path traversal & symlink attack protection
        $this->assertNoPathTraversal($stagingDir);

        // 5. Manifest validation
        $manifestPath = $stagingDir.DIRECTORY_SEPARATOR.'manifest.json';
        if (! File::exists($manifestPath)) {
            $this->cleanupStaging($stagingDir);
            throw new RuntimeException('Corrupted archive: manifest.json is missing from extracted bundle.');
        }

        $manifestContent = json_decode(File::get($manifestPath), true);
        if (! is_array($manifestContent) || ($manifestContent['format'] ?? null) !== 'sisurat-backup') {
            $this->cleanupStaging($stagingDir);
            throw new RuntimeException('Invalid manifest: extracted archive does not conform to SISURAT backup specification.');
        }

        // 6. SHA-256 Checksum verification
        $checksumResult = $this->manifestService->verifyChecksums($stagingDir);
        if (! ($checksumResult['verified'] ?? false)) {
            $mismatchSummary = collect($checksumResult['mismatches'] ?? [])
                ->map(fn ($m) => ($m['file'] ?? 'unknown').' ('.($m['status'] ?? 'ERROR').')')
                ->implode(', ');

            $this->cleanupStaging($stagingDir);
            throw new RuntimeException("Checksum verification failed for extracted files: {$mismatchSummary}");
        }

        // 7. Resolve component paths
        $sqlRelative = $manifestContent['database']['dump_file'] ?? 'database/sisurat.sql';
        $sqlDumpPath = $stagingDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $sqlRelative);

        $docRelative = $manifestContent['storage']['documents']['path'] ?? 'storage/app/private/documents';
        $documentsPath = $stagingDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $docRelative);

        $sigRelative = $manifestContent['storage']['signatures']['path'] ?? 'storage/app/private/signatures';
        $signaturesPath = $stagingDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $sigRelative);

        return [
            'staging_dir' => $stagingDir,
            'manifest' => $manifestContent,
            'checksums_verified' => true,
            'sql_dump_path' => File::exists($sqlDumpPath) ? $sqlDumpPath : null,
            'documents_path' => File::isDirectory($documentsPath) ? $documentsPath : null,
            'signatures_path' => File::isDirectory($signaturesPath) ? $signaturesPath : null,
        ];
    }

    /**
     * Inspect all files within staging directory to prevent zip-slip / path traversal.
     */
    protected function assertNoPathTraversal(string $stagingDir): void
    {
        $canonicalStaging = realpath($stagingDir);
        if ($canonicalStaging === false) {
            throw new RuntimeException("Unable to resolve canonical path for staging directory: {$stagingDir}");
        }

        $finder = new Finder;
        $finder->in($stagingDir)->ignoreDotFiles(false);

        foreach ($finder as $item) {
            $itemReal = $item->getRealPath();
            if ($itemReal === false || ! str_starts_with($itemReal, $canonicalStaging)) {
                $this->cleanupStaging($stagingDir);
                throw new RuntimeException("Path traversal attempt detected in archive item: {$item->getPathname()}");
            }

            if ($item->isLink()) {
                $linkTarget = readlink($item->getPathname());
                if ($linkTarget !== false && ! str_starts_with(realpath($linkTarget) ?: '', $canonicalStaging)) {
                    $this->cleanupStaging($stagingDir);
                    throw new RuntimeException("Malicious symlink detected pointing outside staging directory: {$item->getPathname()}");
                }
            }
        }
    }

    /**
     * Clean up staging directory recursively on validation error.
     */
    public function cleanupStaging(string $stagingDir): void
    {
        if (File::isDirectory($stagingDir)) {
            File::deleteDirectory($stagingDir);
        }
    }

    /**
     * Resolve default staging path for disaster recovery extractions.
     */
    protected function resolveDefaultStagingPath(): string
    {
        $base = config('backup.recovery.temp_path', storage_path('app/private/recovery-temp'));

        return rtrim($base, '/\\').DIRECTORY_SEPARATOR.'rec_'.date('Ymd_His').'_'.bin2hex(random_bytes(4));
    }
}
