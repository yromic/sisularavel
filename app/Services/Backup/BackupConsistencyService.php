<?php

namespace App\Services\Backup;

use App\Models\Attachment;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BackupConsistencyService
{
    public const LOCK_KEY = 'sisurat:backup:exclusive_lock';

    public const IN_PROGRESS_KEY = 'sisurat:backup:consistency_active';

    protected ?Lock $activeLock = null;

    /**
     * Acquire an atomic exclusive lock to prevent concurrent backups.
     *
     * @throws RuntimeException if lock cannot be acquired.
     */
    public function acquireLock(int $ttlSeconds = 3600): Lock
    {
        $lock = Cache::lock(self::LOCK_KEY, $ttlSeconds);

        if (! $lock->get()) {
            throw new RuntimeException('Concurrent backup rejected: An existing backup process holds the active system lock.');
        }

        $this->activeLock = $lock;

        return $lock;
    }

    /**
     * Release the active exclusive atomic lock.
     */
    public function releaseLock(): bool
    {
        $released = false;

        if ($this->activeLock !== null) {
            try {
                $released = $this->activeLock->release();
            } catch (Throwable) {
                // Ignore lock release failures if already expired
            }
            $this->activeLock = null;
        } else {
            // Attempt direct force release on the key if orphaned
            try {
                Cache::lock(self::LOCK_KEY)->forceRelease();
                $released = true;
            } catch (Throwable) {
                // Ignore
            }
        }

        return $released;
    }

    /**
     * Check if a backup lock is currently held.
     */
    public function isLocked(): bool
    {
        $lock = Cache::lock(self::LOCK_KEY, 1);
        $acquired = $lock->get();

        if ($acquired) {
            $lock->release();

            return false;
        }

        return true;
    }

    /**
     * Activate the brief consistency window (holds / warns in-flight uploads).
     */
    public function enterConsistencyWindow(int $ttlSeconds = 120): void
    {
        Cache::put(self::IN_PROGRESS_KEY, true, $ttlSeconds);
    }

    /**
     * Release the consistency window.
     */
    public function exitConsistencyWindow(): void
    {
        Cache::forget(self::IN_PROGRESS_KEY);
    }

    /**
     * Check if the consistency window is active.
     */
    public function isConsistencyWindowActive(): bool
    {
        return (bool) Cache::get(self::IN_PROGRESS_KEY, false);
    }

    /**
     * Validate cross-reference between DB attachments and disk files.
     *
     * Checks for:
     * - Missing files (DB record exists, physical file missing on disk)
     * - Orphan files (physical file on disk, no DB record pointing to it)
     */
    public function verifyCrossReference(): array
    {
        $dbAttachments = Attachment::query()->get();
        $missingFiles = [];
        $matchedCount = 0;
        $knownMissingCount = 0;
        $registeredPaths = [];

        foreach ($dbAttachments as $att) {
            $path = $att->file_path;

            if (empty($path)) {
                if ($att->is_missing) {
                    $knownMissingCount++;
                }

                continue;
            }

            $diskName = $att->disk ?? 'private_documents';
            $registeredPaths[strtolower($diskName.':'.$path)] = true;

            $exists = false;
            try {
                $exists = Storage::disk($diskName)->exists($path);
            } catch (Throwable) {
                $exists = false;
            }

            if ($exists) {
                $matchedCount++;
            } else {
                $missingFiles[] = [
                    'id' => $att->id,
                    'file_path' => $path,
                    'disk' => $diskName,
                    'is_known_missing' => (bool) $att->is_missing,
                    'letter_id' => $att->attachable_id,
                    'letter_type' => class_basename($att->attachable_type ?? ''),
                ];
                if ($att->is_missing) {
                    $knownMissingCount++;
                }
            }
        }

        // Scan disk for orphan files
        $orphanFiles = [];
        try {
            $docFiles = Storage::disk('private_documents')->allFiles();
            foreach ($docFiles as $df) {
                $key = strtolower('private_documents:'.$df);
                if (! isset($registeredPaths[$key])) {
                    $orphanFiles[] = [
                        'disk' => 'private_documents',
                        'file_path' => $df,
                    ];
                }
            }
        } catch (Throwable) {
            // Disk may be empty or uninitialized
        }

        return [
            'total_db_records' => $dbAttachments->count(),
            'matched_count' => $matchedCount,
            'known_missing_count' => $knownMissingCount,
            'missing_files' => $missingFiles,
            'missing_count' => count($missingFiles),
            'orphan_files' => $orphanFiles,
            'orphan_count' => count($orphanFiles),
            'is_fully_consistent' => count($missingFiles) === $knownMissingCount,
        ];
    }
}
