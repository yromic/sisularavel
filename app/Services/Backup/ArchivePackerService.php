<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Phar;
use PharData;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use ZipArchive;

class ArchivePackerService
{
    /**
     * Bundle the source directory into an unencrypted archive file (zip or tar).
     *
     * @return string The absolute path to the generated archive file
     */
    public function pack(string $sourceDir, string $targetArchivePath): string
    {
        $sourceDir = rtrim($sourceDir, '/\\');
        $targetDir = dirname($targetArchivePath);

        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
        }

        if (File::exists($targetArchivePath)) {
            File::delete($targetArchivePath);
        }

        // Strategy 1: Native PHP ZipArchive
        if (class_exists(ZipArchive::class)) {
            return $this->packZip($sourceDir, $targetArchivePath);
        }

        // Strategy 2: Native PHP PharData (.tar)
        if (class_exists(PharData::class)) {
            return $this->packPharTar($sourceDir, $targetArchivePath);
        }

        // Strategy 3: System tar binary
        return $this->packSystemTar($sourceDir, $targetArchivePath);
    }

    /**
     * Unpack an archive file into the target destination directory.
     */
    public function unpack(string $archivePath, string $destinationDir): bool
    {
        if (! File::exists($archivePath)) {
            throw new RuntimeException("Archive file does not exist: {$archivePath}");
        }

        if (! File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true, true);
        }

        if (class_exists(ZipArchive::class) && str_ends_with($archivePath, '.zip')) {
            $zip = new ZipArchive;
            if ($zip->open($archivePath) === true) {
                $zip->extractTo($destinationDir);
                $zip->close();

                return true;
            }
        }

        if (class_exists(PharData::class)) {
            try {
                $phar = new PharData($archivePath);
                $phar->extractTo($destinationDir, null, true);

                return true;
            } catch (\Throwable) {
                // Fallback to system tar
            }
        }

        // Fallback to system tar
        $process = new Process(['tar', '-xf', $archivePath, '-C', $destinationDir]);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new RuntimeException("Failed to unpack archive: {$archivePath}");
    }

    protected function packZip(string $sourceDir, string $targetArchivePath): string
    {
        $zip = new ZipArchive;
        if ($zip->open($targetArchivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create zip archive at: {$targetArchivePath}");
        }

        $finder = new Finder;
        $finder->files()->in($sourceDir)->ignoreDotFiles(false);

        foreach ($finder as $file) {
            $realPath = $file->getRealPath();
            $relativePath = str_replace('\\', '/', substr($realPath, strlen($sourceDir) + 1));
            $zip->addFile($realPath, $relativePath);
        }

        $zip->close();

        return $targetArchivePath;
    }

    protected function packPharTar(string $sourceDir, string $targetArchivePath): string
    {
        // PharData requires tar extension or .tar.gz
        $tarPath = str_ends_with($targetArchivePath, '.tar')
            ? $targetArchivePath
            : $targetArchivePath.'.tar';

        if (File::exists($tarPath)) {
            try {
                Phar::unlinkArchive($tarPath);
            } catch (\Throwable) {
                File::delete($tarPath);
            }
        }

        try {
            $phar = new PharData($tarPath);

            $finder = new Finder;
            $finder->files()->in($sourceDir)->ignoreDotFiles(false);

            foreach ($finder as $file) {
                $realPath = $file->getRealPath();
                $relativePath = str_replace('\\', '/', substr($realPath, strlen($sourceDir) + 1));
                $phar->addFile($realPath, $relativePath);
            }

            unset($phar);

            return $tarPath;
        } catch (\Throwable) {
            // Fallback to system tar
            return $this->packSystemTar($sourceDir, $targetArchivePath);
        }
    }

    protected function packSystemTar(string $sourceDir, string $targetArchivePath): string
    {
        $tarPath = str_ends_with($targetArchivePath, '.tar') ? $targetArchivePath : $targetArchivePath.'.tar';

        $process = new Process([
            'tar',
            '-cf',
            $tarPath,
            '-C',
            $sourceDir,
            '.',
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($tarPath)) {
            throw new RuntimeException('System tar failed: '.$process->getErrorOutput());
        }

        return $tarPath;
    }
}
