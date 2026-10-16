<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_id',
        'type',
        'trigger',
        'requested_by',
        'status',
        'started_at',
        'completed_at',
        'archive_size_bytes',
        'files_count',
        'sha256_checksum',
        'remote_storage_provider',
        'remote_file_id',
        'error_code',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'archive_size_bytes' => 'integer',
        'files_count' => 'integer',
    ];

    /**
     * The user who triggered this backup run (null if triggered by scheduler/cron).
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Check if the backup run has completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the backup run has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark state transition.
     */
    public function transitionTo(string $newStatus, array $extra = []): void
    {
        $this->update(array_merge([
            'status' => $newStatus,
        ], $extra));
    }

    /**
     * Mark run as failed with code and message.
     */
    public function markFailed(string $code, string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_code' => $code,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }
}
