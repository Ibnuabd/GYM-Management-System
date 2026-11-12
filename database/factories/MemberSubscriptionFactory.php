<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberSubscription>
 */
class MemberSubscriptionFactory extends Factory
{
    protected $model = MemberSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $package = Package::factory()->create();
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays($package->duration_days - 1);

        return [
            'member_id' => Member::factory(),
            'package_id' => $package->id,
            'pt_user_id' => null,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_sessions' => $package->session_count,
            'used_sessions' => 0,
            'price_paid' => $package->price,
            'status' => 'active',
            'notes' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the subscription is assigned to a specific trainer.
     */
    public function assignedTo(User $trainer): static
    {
        return $this->state(fn () => [
            'pt_user_id' => $trainer->id,
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'start_date' => Carbon::today()->subDays(60)->toDateString(),
            'end_date' => Carbon::today()->subDays(30)->toDateString(),
            'status' => 'expired',
        ]);
    }

    /**
     * Indicate that all sessions have been exhausted.
     */
    public function exhausted(): static
    {
        return $this->state(fn () => [
            'total_sessions' => 10,
            'used_sessions' => 10,
            'status' => 'exhausted',
        ]);
    }
}
