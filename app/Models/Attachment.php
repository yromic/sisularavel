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
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return ['is_missing' => 'boolean'];
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
