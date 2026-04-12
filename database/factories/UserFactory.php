<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = ['super_admin', 'admin', 'employee'];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'passkey' => 'Pass#'.fake()->numerify('####'),
            'passkey_plain' => null,
            'role' => $roles[array_rand($roles)],
            'is_active' => true,
            'created_by' => null,
            'remember_token' => Str::random(10),
        ];
    }
}
