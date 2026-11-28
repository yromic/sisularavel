<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentStorageService
{
    public const DISK = 'private_documents';

    public function __construct(
        private ?ImageOptimizationService $imageOptimizer = null
    ) {
        $this->imageOptimizer = $this->imageOptimizer ?? new ImageOptimizationService;
    }

    public function storeForModel(UploadedFile $file, Model $attachable, Division $division, User $actor): Attachment
    {
        $optimized = null;
        try {
            $path = $this->storeFile($file, $division, $optimized);
            $storedFullPath = Storage::disk(self::DISK)->path($path);
            $storedSize = Storage::disk(self::DISK)->exists($path)
                ? Storage::disk(self::DISK)->size($path)
                : ($optimized ? $optimized->sizeBytes : ($file->getSize() ?: 0));
            $hash = hash_file('sha256', $storedFullPath);
            $mimeType = $optimized ? $optimized->mimeType : ($file->getMimeType() ?: 'application/octet-stream');

            return Attachment::query()->create([
                'uuid' => (string) Str::uuid(),
                'division_id' => $division->id,
                'attachable_type' => $attachable::class,
                'attachable_id' => $attachable->getKey(),
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size_bytes' => $storedSize,
                'mime_type' => $mimeType,
                'file_hash' => $hash,
                'is_missing' => false,
                'migration_status' => 'migrated',
                'missing_reason' => null,
                'created_by_user_id' => $actor->id,
            ]);
        } finally {
            $optimized?->cleanup();
        }
    }

    /**
     * @param  array<UploadedFile>  $photos
     */
    public function storeMergedPhotosAsPdf(array $photos, Model $attachable, Division $division, User $actor, string $letterNumber): Attachment
    {
        $tempDir = storage_path('app/private/temp_photos');
        File::ensureDirectoryExists($tempDir);

        $optimizedResults = [];
        $photoPaths = [];

        try {
            foreach ($photos as $photo) {
                $optimized = $this->imageOptimizer->optimize($photo);
                $optimizedResults[] = $optimized;
                $photoPaths[] = str_replace('\\', '/', realpath($optimized->path) ?: $optimized->path);
            }

            $cleanNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', $letterNumber);
            $fileName = 'Surat_'.$cleanNumber.'_'.count($photos).'_lembar.pdf';

            $pdf = Pdf::loadView('letters.pdf_photos', [
                'photos' => $photoPaths,
                'title' => 'Lampiran Foto Surat '.$letterNumber,
            ])->setPaper('a4', 'portrait');

            $pdf->getDomPDF()->getOptions()->setChroot([
                base_path(),
                storage_path(),
            ]);

            $pdfContent = $pdf->output();

            $datePath = now()->format('Y/m');
            $path = 'documents/'.$division->code.'/'.$datePath.'/'.Str::uuid().'.pdf';

            Storage::disk(self::DISK)->put($path, $pdfContent);
            $hash = hash('sha256', $pdfContent);

            return Attachment::query()->create([
                'uuid' => (string) Str::uuid(),
                'division_id' => $division->id,
                'attachable_type' => $attachable::class,
                'attachable_id' => $attachable->getKey(),
                'file_path' => $path,
                'original_name' => $fileName,
                'file_size_bytes' => strlen($pdfContent),
                'mime_type' => 'application/pdf',
                'file_hash' => $hash,
                'is_missing' => false,
                'migration_status' => 'migrated',
                'missing_reason' => null,
                'created_by_user_id' => $actor->id,
            ]);
        } finally {
            foreach ($optimizedResults as $opt) {
                $opt->cleanup();
            }
        }
    }

    public function remediate(Attachment $attachment, UploadedFile $file, Division $division, User $actor): Attachment
    {
        $optimized = null;
        try {
            $path = $this->storeFile($file, $division, $optimized);
            $storedFullPath = Storage::disk(self::DISK)->path($path);
            $storedSize = Storage::disk(self::DISK)->exists($path)
                ? Storage::disk(self::DISK)->size($path)
                : ($optimized ? $optimized->sizeBytes : ($file->getSize() ?: 0));
            $hash = hash_file('sha256', $storedFullPath);
            $mimeType = $optimized ? $optimized->mimeType : ($file->getMimeType() ?: 'application/octet-stream');

            if ($attachment->file_path !== null) {
                Storage::disk(self::DISK)->delete($attachment->file_path);
            }

            $attachment->forceFill([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size_bytes' => $storedSize,
                'mime_type' => $mimeType,
                'file_hash' => $hash,
                'is_missing' => false,
                'migration_status' => 'migrated',
                'missing_reason' => null,
                'created_by_user_id' => $actor->id,
            ])->save();

            return $attachment;
        } finally {
            $optimized?->cleanup();
        }
    }

    public function deleteStored(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function storeFile(UploadedFile $file, Division $division, ?OptimizedImageResult &$optimizedOut = null): string
    {
        $optimized = $this->imageOptimizer->optimize($file);
        $optimizedOut = $optimized;

        $datePath = now()->format('Y/m');
        $extension = $optimized->extension;
        $path = 'documents/'.$division->code.'/'.$datePath.'/'.Str::uuid().'.'.$extension;

        Storage::disk(self::DISK)->put($path, file_get_contents($optimized->path));

        return $path;
    }
}
