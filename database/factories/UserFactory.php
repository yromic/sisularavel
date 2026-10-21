<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'division_id' => Division::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'operator',
            'is_active' => true,
            'must_reset_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this;
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super_admin',
            'division_id' => null,
        ]);
    }

    public function divisionAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'division_admin',
        ]);
    }

    public function operator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'operator',
        ]);
    }
}
