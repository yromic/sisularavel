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
        'piagam_description',
        'school_id',
        'school_origin',
        'work_unit_id',
        'npsn',
        'competition_category_id',
        'competition_category_name',
        'competition_year',
        'pickup_category_id',
        'pickup_category_name',
        'recipient_name',
        'recipient_position',
        'recipient_unit',
        'created_by_user_id',
    ];

    public function getSchoolDisplayNameAttribute(): string
    {
        return $this->school?->name ?? $this->school_origin ?? $this->recipient_unit ?? '-';
    }

    public function getPiagamDisplayNameAttribute(): string
    {
        return $this->piagam_description ?: $this->student_name;
    }

    public function getPickupDisplayNameAttribute(): string
    {
        return $this->pickup_category_name ?: ($this->pickupCategory?->name ?: 'Diambil Langsung');
    }

    public function getCompetitionDisplayNameAttribute(): string
    {
        return $this->competition_category_name ?: ($this->competitionCategory?->name ?: '-');
    }

    public function getUnitDisplayNameAttribute(): string
    {
        return $this->recipient_unit ?: ($this->workUnit?->name ?: ($this->school_origin ?: ($this->school?->name ?: '-')));
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function competitionCategory(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class);
    }

    public function pickupCategory(): BelongsTo
    {
        return $this->belongsTo(PickupCategory::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->withTrashed();
    }
}
