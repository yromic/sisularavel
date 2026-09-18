<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<School> */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'npsn' => fake()->unique()->numerify('########'),
            'name' => 'Sekolah '.fake()->city(),
            'is_active' => true,
        ];
    }
}
