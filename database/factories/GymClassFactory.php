<?php

namespace Database\Factories;

use App\Models\GymClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GymClass>
 */
class GymClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'capacity' => $this->faker->numberBetween(5, 30),
            'instructor_id' => User::factory(),
            'start_time' => $this->faker->dateTimeBetween('+1 days', '+30 days'),
            'end_time' => $this->faker->dateTimeBetween('+31 days', '+60 days'),
            'is_active' => true,
        ];
    }
}
