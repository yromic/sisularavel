<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Piagam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'division_id',
        'student_name',
        'school_id',
        'competition_category_id',
        'competition_year',
        'pickup_category_id',
        'recipient_name',
        'recipient_position',
        'recipient_unit',
        'created_by_user_id',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
