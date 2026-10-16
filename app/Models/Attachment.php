<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'division_id',
        'attachable_type',
        'attachable_id',
        'file_path',
        'original_name',
        'file_size_bytes',
        'mime_type',
        'file_hash',
        'is_missing',
        'migration_status',
        'missing_reason',
        'legacy_drive_file_id',
        'legacy_drive_url',
        'google_drive_file_id',
        'google_drive_view_url',
        'google_drive_synced_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_missing' => 'boolean',
            'google_drive_synced_at' => 'datetime',
        ];
    }

    public function getDriveViewUrlAttribute(): ?string
    {
        return $this->google_drive_view_url ?: $this->legacy_drive_url;
    }

    public function getDriveViewLinkAttribute(): ?string
    {
        return $this->drive_view_url;
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
