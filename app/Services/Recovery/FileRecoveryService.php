<?php

namespace App\Services\Recovery;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class FileRecoveryService
{
    /**
     * Restore physical documents and digital signature files to their respective private disks.
     *
     * @param  string|null  $sourceDocumentsDir  Path to extracted documents directory
     * @param  string|null  $sourceSignaturesDir  Path to extracted signatures directory
     * @return array Metrics on restored files and bytes
     */
    public function restore(?string $sourceDocumentsDir, ?string $sourceSignaturesDir): array
    {
        $targetDocumentsDir = config('filesystems.disks.private_documents.root', storage_path('app/private/documents'));
        $targetSignaturesDir = config('filesystems.disks.private_signatures.root', storage_path('app/private/signatures'));

        $this->assertSafeDestination($targetDocumentsDir, 'private_documents');
        $this->assertSafeDestination($targetSignaturesDir, 'private_signatures');

        $docMetrics = ['count' => 0, 'bytes' => 0];
        if (! empty($sourceDocumentsDir) && File::isDirectory($sourceDocumentsDir)) {
            $docMetrics = $this->copyTreeSafely($sourceDocumentsDir, $targetDocumentsDir);
        }

        $sigMetrics = ['count' => 0, 'bytes' => 0];
        if (! empty($sourceSignaturesDir) && File::isDirectory($sourceSignaturesDir)) {
            $sigMetrics = $this->copyTreeSafely($sourceSignaturesDir, $targetSignaturesDir);
        }

        return [
            'documents_restored' => $docMetrics['count'],
            'documents_bytes' => $docMetrics['bytes'],
            'signatures_restored' => $sigMetrics['count'],
            'signatures_bytes' => $sigMetrics['bytes'],
            'total_files' => $docMetrics['count'] + $sigMetrics['count'],
            'total_bytes' => $docMetrics['bytes'] + $sigMetrics['bytes'],
            'target_documents_path' => $targetDocumentsDir,
            'target_signatures_path' => $targetSignaturesDir,
        ];
    }

    /**
     * Recursively copy files from source directory into destination directory
     * with anti-traversal and symlink protections.
     */
    protected function copyTreeSafely(string $sourceDir, string $destinationDir): array
    {
        $canonicalSource = realpath($sourceDir);
        if ($canonicalSource === false) {
            throw new RuntimeException("Cannot resolve canonical path for source storage directory: {$sourceDir}");
        }

        if (! File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true, true);
        }

        $finder = new Finder;
        $finder->files()->in($sourceDir)->ignoreDotFiles(false);

        $count = 0;
        $bytes = 0;

        foreach ($finder as $file) {
            $realSourceFile = $file->getRealPath();
            if ($realSourceFile === false || ! str_starts_with($realSourceFile, $canonicalSource)) {
                throw new RuntimeException("Path traversal attempt in storage source: {$file->getPathname()}");
            }

            if ($file->isLink()) {
                throw new RuntimeException("Symlink rejected in storage restore: {$file->getPathname()}");
            }

            $relPath = substr($realSourceFile, strlen($canonicalSource) + 1);
            $targetFilePath = $destinationDir.DIRECTORY_SEPARATOR.$relPath;
            $targetFileDir = dirname($targetFilePath);

            if (! File::isDirectory($targetFileDir)) {
                File::makeDirectory($targetFileDir, 0755, true, true);
            }

            File::copy($realSourceFile, $targetFilePath);
            @chmod($targetFilePath, 0644);

            $fileSize = File::size($targetFilePath);
            $count++;
            $bytes += $fileSize;
        }

        return [
            'count' => $count,
            'bytes' => $bytes,
        ];
    }

    /**
     * Ensure destination path resides strictly within private storage boundaries
     * and never under public root.
     */
    protected function assertSafeDestination(string $destinationDir, string $diskName): void
    {
        $publicPath = public_path();
        $normalizedDest = str_replace('\\', '/', $destinationDir);
        $normalizedPublic = str_replace('\\', '/', $publicPath);

        if (str_starts_with($normalizedDest, $normalizedPublic)) {
            throw new RuntimeException("Security violation: Destination disk '{$diskName}' points inside public root ({$destinationDir})!");
        }
    }
}
