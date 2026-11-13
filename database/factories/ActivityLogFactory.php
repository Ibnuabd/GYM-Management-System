<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'user_role' => 'cs',
            'action' => 'member.updated',
            'description' => 'Mengubah data member '.fake()->name(),
            'properties' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Mozilla/5.0',
        ];
    }

    /**
     * Log login gagal (tanpa user).
     */
    public function failedLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'user_name' => null,
            'user_role' => null,
            'action' => 'auth.login_failed',
            'description' => 'Login gagal untuk email '.fake()->safeEmail(),
        ]);
    }
}
