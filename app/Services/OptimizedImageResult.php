<?php

namespace App\Services;

class OptimizedImageResult
{
    public function __construct(
        public readonly string $path,
        public readonly string $mimeType,
        public readonly string $extension,
        public readonly int $sizeBytes,
        public readonly bool $isOptimized,
        public readonly bool $isTemporary
    ) {}

    /**
     * Hapus berkas temporary jika berkas ini dibuat oleh ImageOptimizationService.
     */
    public function cleanup(): void
    {
        if ($this->isTemporary && file_exists($this->path)) {
            @unlink($this->path);
        }
    }
}
