<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageOptimizationService
{
    public const MAX_INPUT_DIMENSION = 4096;

    public const TARGET_MAX_DIMENSION = 1280;

    public const TARGET_JPEG_QUALITY = 75;

    public const SIZE_THRESHOLD_BYTES = 204800; // 200 KB

    /**
     * Optimasi citra arsip secara kondisional dan aman.
     *
     * @param  UploadedFile|string  $file
     *
     * @throws ValidationException
     */
    public function optimize(mixed $file, int $maxDim = self::TARGET_MAX_DIMENSION, int $quality = self::TARGET_JPEG_QUALITY): OptimizedImageResult
    {
        $realPath = $file instanceof UploadedFile ? $file->getRealPath() : (string) $file;

        if (! file_exists($realPath) || ! is_readable($realPath)) {
            return $this->originalResult($file, $realPath);
        }

        // Cek ekstensi GD bawaan PHP
        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            return $this->originalResult($file, $realPath);
        }

        // Dapatkan informasi MIME dan dimensi citra
        $info = @getimagesize($realPath);

        // Jika bukan file gambar valid, lewati tanpa kompresi
        if ($info === false || empty($info['mime'])) {
            return $this->originalResult($file, $realPath);
        }

        $mime = strtolower((string) $info['mime']);
        $supportedTypes = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
        ];

        if (defined('IMAGETYPE_WEBP')) {
            $supportedTypes[IMAGETYPE_WEBP] = 'image/webp';
        }

        if (! isset($supportedTypes[$info[2]])) {
            return $this->originalResult($file, $realPath);
        }

        $width = (int) $info[0];
        $height = (int) $info[1];

        // 1. Anti-OOM: Cegah Decompression Bomb (Pixel Flood)
        if ($width > self::MAX_INPUT_DIMENSION || $height > self::MAX_INPUT_DIMENSION) {
            throw ValidationException::withMessages([
                'attachment' => "Dimensi gambar ({$width}x{$height} px) melebihi batas maksimum ".self::MAX_INPUT_DIMENSION.' piksel.',
                'photos' => "Dimensi lembar foto ({$width}x{$height} px) melebihi batas maksimum ".self::MAX_INPUT_DIMENSION.' piksel.',
            ]);
        }

        // 2. Baca orientasi EXIF untuk file JPEG
        $orientation = 1;
        if (function_exists('exif_read_data') && ($info[2] === IMAGETYPE_JPEG)) {
            $exif = @exif_read_data($realPath);
            if (! empty($exif['Orientation'])) {
                $orientation = (int) $exif['Orientation'];
            }
        }

        $fileSize = filesize($realPath) ?: 0;
        $needsResize = ($width > $maxDim || $height > $maxDim);
        $needsCompression = ($fileSize > self::SIZE_THRESHOLD_BYTES);
        $needsFormatConversion = ($mime !== 'image/jpeg');
        $needsRotation = in_array($orientation, [3, 6, 8], true);

        // 3. Gerbang Kompresi Kondisional (Cegah Double Lossy Generation Loss)
        if (! $needsResize && ! $needsCompression && ! $needsFormatConversion && ! $needsRotation) {
            return $this->originalResult($file, $realPath);
        }

        // 4. Muat citra ke memori GD
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($realPath),
            IMAGETYPE_PNG => @imagecreatefrompng($realPath),
            defined('IMAGETYPE_WEBP') && $info[2] === IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($realPath) : null,
            default => null,
        };

        if (! $src) {
            return $this->originalResult($file, $realPath);
        }

        // 5. Normalisasi Rotasi EXIF jika miring/terbalik
        if ($needsRotation) {
            $rotated = match ($orientation) {
                3 => @imagerotate($src, 180, 0),
                6 => @imagerotate($src, -90, 0),
                8 => @imagerotate($src, 90, 0),
                default => $src,
            };

            if ($rotated && $rotated !== $src) {
                imagedestroy($src);
                $src = $rotated;
            }
        }

        $curW = imagesx($src);
        $curH = imagesy($src);

        // 6. Hitung rasio aspek preservasi
        $ratio = min($maxDim / $curW, $maxDim / $curH, 1.0);
        $newW = (int) max(1, round($curW * $ratio));
        $newH = (int) max(1, round($curH * $ratio));

        $dst = imagecreatetruecolor($newW, $newH);

        // Isi latar belakang dengan putih bersih (#FFFFFF) untuk menangani transparansi PNG
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $curW, $curH);

        // 7. Simpan hasil optimasi ke folder temporary khusus
        $tempDir = storage_path('app/private/temp_photos');
        File::ensureDirectoryExists($tempDir);

        $tempFileName = Str::uuid()->toString().'.jpg';
        $tempPath = $tempDir.DIRECTORY_SEPARATOR.$tempFileName;

        imagejpeg($dst, $tempPath, $quality);

        // Bebaskan memori GD
        imagedestroy($dst);
        imagedestroy($src);

        $optimizedSize = file_exists($tempPath) ? (filesize($tempPath) ?: 0) : 0;

        return new OptimizedImageResult(
            path: $tempPath,
            mimeType: 'image/jpeg',
            extension: 'jpg',
            sizeBytes: $optimizedSize,
            isOptimized: true,
            isTemporary: true
        );
    }

    private function originalResult(mixed $file, string $realPath): OptimizedImageResult
    {
        $mime = $file instanceof UploadedFile
            ? ($file->getMimeType() ?: 'application/octet-stream')
            : (function_exists('mime_content_type') && file_exists($realPath) ? (mime_content_type($realPath) ?: 'application/octet-stream') : 'application/octet-stream');

        $ext = $file instanceof UploadedFile
            ? ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin')
            : (pathinfo($realPath, PATHINFO_EXTENSION) ?: 'bin');

        $size = file_exists($realPath) ? (filesize($realPath) ?: 0) : 0;

        return new OptimizedImageResult(
            path: $realPath,
            mimeType: $mime,
            extension: strtolower($ext),
            sizeBytes: $size,
            isOptimized: false,
            isTemporary: false
        );
    }
}
