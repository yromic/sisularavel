<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentStorageService
{
    public const DISK = 'private_documents';

    public function storeForModel(UploadedFile $file, Model $attachable, Division $division, User $actor): Attachment
    {
        $path = $this->storeFile($file, $division);
        $hash = hash_file('sha256', Storage::disk(self::DISK)->path($path));

        return Attachment::query()->create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'attachable_type' => $attachable::class,
            'attachable_id' => $attachable->getKey(),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size_bytes' => $file->getSize() ?: 0,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_hash' => $hash,
            'is_missing' => false,
            'migration_status' => 'migrated',
            'missing_reason' => null,
            'created_by_user_id' => $actor->id,
        ]);
    }

    public function remediate(Attachment $attachment, UploadedFile $file, Division $division, User $actor): Attachment
    {
        $path = $this->storeFile($file, $division);
        $hash = hash_file('sha256', Storage::disk(self::DISK)->path($path));

        if ($attachment->file_path !== null) {
            Storage::disk(self::DISK)->delete($attachment->file_path);
        }

        $attachment->forceFill([
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size_bytes' => $file->getSize() ?: 0,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_hash' => $hash,
            'is_missing' => false,
            'migration_status' => 'migrated',
            'missing_reason' => null,
            'created_by_user_id' => $actor->id,
        ])->save();

        return $attachment;
    }

    public function deleteStored(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function storeFile(UploadedFile $file, Division $division): string
    {
        $datePath = now()->format('Y/m');
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $path = 'documents/'.$division->code.'/'.$datePath.'/'.Str::uuid().'.'.$extension;

        Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }
}
