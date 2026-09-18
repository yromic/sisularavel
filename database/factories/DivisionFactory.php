<?php

namespace Database\Factories;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Division> */
class DivisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'code' => strtoupper(fake()->unique()->lexify('DIV???')),
            'name' => fake()->company(),
            'status' => 'active',
        ];
    }
}
