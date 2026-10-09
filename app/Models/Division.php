<?php

namespace App\Models;

use Database\Factories\DivisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Division extends Model
{
    /** @use HasFactory<DivisionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'status',
        'legacy_drive_folder_id',
        'created_by_user_id',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function incomingLetters(): HasMany
    {
        return $this->hasMany(IncomingLetter::class);
    }

    public function outgoingLetters(): HasMany
    {
        return $this->hasMany(OutgoingLetter::class);
    }

    public function piagams(): HasMany
    {
        return $this->hasMany(Piagam::class);
    }
}
