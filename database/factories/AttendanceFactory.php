<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $member = Member::factory()->create();
        $subscription = MemberSubscription::factory()->create(['member_id' => $member->id]);

        return [
            'member_id' => $member->id,
            'rfid_uid' => $member->rfid_uid,
            'status' => 'IN',
            'snapshot_path' => null,
            'member_subscription_id' => $subscription->id,
            'scanned_at' => Carbon::now(),
        ];
    }

    public function in(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'IN',
        ]);
    }

    public function out(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'OUT',
        ]);
    }
}
