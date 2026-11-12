<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PtSessionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PtSessionLog>
 */
class PtSessionLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $foci = [
            'Chest & Triceps Hypertrophy',
            'Back & Biceps Pull Conditioning',
            'Leg Day & Squat Progression',
            'Shoulders & Core Stability',
            'Full Body HIIT & Endurance',
        ];

        return [
            'member_subscription_id' => MemberSubscription::factory(),
            'member_id' => Member::factory(),
            'pt_user_id' => User::factory(),
            'session_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'duration_minutes' => fake()->randomElement([45, 60, 75, 90]),
            'muscle_focus' => fake()->randomElement($foci),
            'workout_notes' => fake()->paragraph(),
            'member_feedback' => fake()->sentence(),
        ];
    }
}
