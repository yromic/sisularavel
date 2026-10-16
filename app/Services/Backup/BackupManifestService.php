<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class BackupManifestService
{
    /**
     * Generate the standardized manifest.json content adhering to Section 7.2 of docs/backup.md.
     */
    public function buildManifest(
        string $backupId,
        string $type,
        array $dbStats,
        array $docStats,
        array $sigStats,
        ?string $createdAt = null
    ): array {
        return [
            'format' => 'sisurat-backup',
            'format_version' => 1,
            'backup_id' => $backupId,
            'type' => $type,
            'created_at' => $createdAt ?? now()->toIso8601String(),
            'environment' => config('app.env', 'production'),
            'application' => [
                'name' => config('app.name', 'SISURAT'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
            ],
            'database' => [
                'engine' => 'mysql',
                'version' => $dbStats['version'] ?? 'Unknown',
                'dump_file' => 'database/sisurat.sql',
                'dump_size_bytes' => (int) ($dbStats['size_bytes'] ?? 0),
                'sha256' => $dbStats['sha256'] ?? '',
                'tables_count' => (int) ($dbStats['tables_count'] ?? 0),
            ],
            'storage' => [
                'documents' => [
                    'target_disk' => 'private_documents',
                    'path' => 'storage/app/private/documents',
                    'file_count' => (int) ($docStats['file_count'] ?? 0),
                    'total_size_bytes' => (int) ($docStats['total_size_bytes'] ?? 0),
                ],
                'signatures' => [
                    'target_disk' => 'private_signatures',
                    'path' => 'storage/app/private/signatures',
                    'file_count' => (int) ($sigStats['file_count'] ?? 0),
                    'total_size_bytes' => (int) ($sigStats['total_size_bytes'] ?? 0),
                ],
            ],
            'encryption' => [
                'cipher' => config('backup.encryption.cipher', 'aes-256-gcm'),
                'key_id' => config('backup.encryption.key_id', 'sisurat-backup-master-2026'),
                'tag_length_bits' => 128,
            ],
        ];
    }

    /**
     * Write manifest.json to the staging directory.
     */
    public function writeManifest(string $stagingDir, array $manifestData): string
    {
        $manifestPath = rtrim($stagingDir, '/\\').DIRECTORY_SEPARATOR.'manifest.json';
        $json = json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        File::put($manifestPath, $json);

        return $manifestPath;
    }

    /**
     * Compute SHA-256 checksums for all files within the staging directory
     * and write checksums.sha256.
     *
     * Note: checksums.sha256 itself is excluded from the file listing.
     */
    public function generateChecksumsFile(string $stagingDir): string
    {
        $stagingDir = rtrim($stagingDir, '/\\');
        $checksumFile = $stagingDir.DIRECTORY_SEPARATOR.'checksums.sha256';

        $finder = new Finder;
        $finder->files()->in($stagingDir)->ignoreDotFiles(false);

        $checksumLines = [];

        foreach ($finder as $file) {
            $realPath = $file->getRealPath();

            // Skip the checksum file itself if it already exists
            if ($realPath === $checksumFile) {
                continue;
            }

            // Calculate relative path inside the staging dir with unix separators
            $relativePath = str_replace('\\', '/', substr($realPath, strlen($stagingDir) + 1));
            $hash = hash_file('sha256', $realPath);

            $checksumLines[] = "{$hash}  {$relativePath}";
        }

        // Sort lines alphabetically for determinism
        sort($checksumLines);

        $content = implode("\n", $checksumLines)."\n";
        File::put($checksumFile, $content);

        return $checksumFile;
    }

    /**
     * Validate existing checksums.sha256 against actual files in a directory.
     * Returns array of mismatches (empty array if 100% verified).
     */
    public function verifyChecksums(string $stagingDir): array
    {
        $stagingDir = rtrim($stagingDir, '/\\');
        $checksumFile = $stagingDir.DIRECTORY_SEPARATOR.'checksums.sha256';

        if (! File::exists($checksumFile)) {
            return [
                'verified' => false,
                'error' => 'checksums.sha256 not found in archive directory',
                'mismatches' => [],
            ];
        }

        $lines = explode("\n", trim(File::get($checksumFile)));
        $mismatches = [];
        $checkedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Standard format: <hash>  <path> (two spaces)
            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$expectedHash, $relPath] = $parts;
            $targetPath = $stagingDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relPath);

            if (! File::exists($targetPath)) {
                $mismatches[] = [
                    'file' => $relPath,
                    'status' => 'MISSING',
                    'expected' => $expectedHash,
                    'actual' => null,
                ];

                continue;
            }

            $actualHash = hash_file('sha256', $targetPath);
            if (! hash_equals($expectedHash, $actualHash)) {
                $mismatches[] = [
                    'file' => $relPath,
                    'status' => 'HASH_MISMATCH',
                    'expected' => $expectedHash,
                    'actual' => $actualHash,
                ];
            } else {
                $checkedCount++;
            }
        }

        return [
            'verified' => empty($mismatches),
            'checked_count' => $checkedCount,
            'mismatches' => $mismatches,
        ];
    }
}
