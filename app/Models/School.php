<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['npsn', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function piagams(): HasMany
    {
        return $this->hasMany(Piagam::class);
    }
}
