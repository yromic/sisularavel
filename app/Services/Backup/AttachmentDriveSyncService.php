<?php

namespace App\Services\Backup;

use App\Models\Attachment;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Services\AttachmentStorageService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AttachmentDriveSyncService
{
    public function __construct(
        protected GoogleDriveStorageService $googleDrive
    ) {}

    /**
     * Synchronize a batch of un-synced attachments to Google Drive.
     */
    public function syncBatch(int $limit = 50): array
    {
        if (! $this->googleDrive->isConfigured()) {
            return [
                'status' => 'skipped',
                'reason' => 'Google Drive OAuth credentials are not configured.',
                'synced' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];
        }

        $query = Attachment::query()
            ->with('attachable')
            ->whereNull('google_drive_view_url')
            ->where('is_missing', false)
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->limit($limit);

        $attachments = $query->get();

        $synced = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($attachments as $attachment) {
            $diskName = $attachment->attachable_type === Piagam::class
                ? 'private_signatures'
                : AttachmentStorageService::DISK;

            if (! Storage::disk($diskName)->exists($attachment->file_path)) {
                $skipped++;
                Log::warning("DriveSync: Local file missing for attachment ID {$attachment->id} ({$attachment->file_path}).");

                continue;
            }

            $localPath = Storage::disk($diskName)->path($attachment->file_path);
            if (! File::exists($localPath)) {
                $skipped++;

                continue;
            }

            try {
                $folderName = $this->determineSubfolder($attachment->attachable_type);
                $folderId = $this->googleDrive->ensureFolder($folderName);

                $remoteFilename = $this->generateHumanReadableFilename($attachment);

                $result = $this->googleDrive->uploadSingleFile(
                    $localPath,
                    $remoteFilename,
                    $folderId,
                    $attachment->mime_type ?: 'application/octet-stream',
                    true
                );

                $attachment->update([
                    'google_drive_file_id' => $result['file_id'],
                    'google_drive_view_url' => $result['view_url'],
                    'google_drive_synced_at' => now(),
                ]);

                $synced++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "Attachment #{$attachment->id}: {$e->getMessage()}";
                Log::error("DriveSync Error for attachment #{$attachment->id}: ".$e->getMessage());
            }
        }

        return [
            'status' => 'completed',
            'processed' => count($attachments),
            'synced' => $synced,
            'failed' => $failed,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Synchronize all pending attachments to Google Drive in progressive chunks.
     */
    public function syncAll(int $chunkSize = 50, int $maxChunks = 20): array
    {
        $totalSynced = 0;
        $totalFailed = 0;
        $totalSkipped = 0;
        $allErrors = [];

        for ($i = 0; $i < $maxChunks; $i++) {
            $batch = $this->syncBatch($chunkSize);

            if ($batch['status'] === 'skipped') {
                return $batch;
            }

            $totalSynced += $batch['synced'];
            $totalFailed += $batch['failed'];
            $totalSkipped += $batch['skipped'];
            $allErrors = array_merge($allErrors, $batch['errors'] ?? []);

            if (($batch['processed'] ?? 0) < $chunkSize) {
                break;
            }
        }

        return [
            'status' => 'completed',
            'synced' => $totalSynced,
            'failed' => $totalFailed,
            'skipped' => $totalSkipped,
            'errors' => $allErrors,
        ];
    }

    /**
     * Get aggregate statistics regarding Google Drive sync status.
     */
    public function getSyncStats(): array
    {
        $totalEligible = Attachment::query()
            ->where('is_missing', false)
            ->whereNotNull('file_path')
            ->count();

        $syncedCount = Attachment::query()
            ->where('is_missing', false)
            ->whereNotNull('google_drive_view_url')
            ->count();

        $pendingCount = max(0, $totalEligible - $syncedCount);
        $percent = $totalEligible > 0 ? round(($syncedCount / $totalEligible) * 100, 1) : 100.0;

        return [
            'total_files' => $totalEligible,
            'synced_files' => $syncedCount,
            'pending_files' => $pendingCount,
            'percentage' => $percent,
            'is_fully_synced' => $pendingCount === 0,
        ];
    }

    /**
     * Determine Google Drive subfolder name based on attachable model type.
     */
    public function determineSubfolder(?string $attachableType): string
    {
        return match ($attachableType) {
            IncomingLetter::class => '01_Surat_Masuk',
            OutgoingLetter::class => '02_Surat_Keluar',
            Piagam::class => '03_Tanda_Tangan_Piagam',
            default => '00_Lampiran_Lainnya',
        };
    }

    /**
     * Generate clear, human-readable file name for Google Drive.
     */
    public function generateHumanReadableFilename(Attachment $attachment): string
    {
        $ext = pathinfo($attachment->original_name ?: $attachment->file_path, PATHINFO_EXTENSION);
        $ext = $ext ? '.'.$ext : '';
        $parent = $attachment->attachable;

        if ($attachment->attachable_type === IncomingLetter::class && $parent instanceof IncomingLetter) {
            $num = Str::slug($parent->letter_number ?: 'tanpa-nomor', '_');
            $sender = Str::slug($parent->origin_agency ?: 'instansi', '_');

            return "SM_{$parent->id}_{$num}_{$sender}{$ext}";
        }

        if ($attachment->attachable_type === OutgoingLetter::class && $parent instanceof OutgoingLetter) {
            $num = Str::slug($parent->letter_number ?: 'tanpa-nomor', '_');
            $subj = Str::limit(Str::slug($parent->subject ?: 'perihal', '_'), 30, '');

            return "SK_{$parent->id}_{$num}_{$subj}{$ext}";
        }

        if ($attachment->attachable_type === Piagam::class && $parent instanceof Piagam) {
            $name = Str::slug($parent->recipient_name ?: ($parent->student_name ?: 'penerima'), '_');

            return "TTD_Resi_{$parent->id}_{$name}{$ext}";
        }

        $base = pathinfo($attachment->original_name ?: basename($attachment->file_path), PATHINFO_FILENAME);

        return Str::slug($base, '_').$ext;
    }
}
