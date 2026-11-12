<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = Member::with(['activeSubscriptions.package'])->has('activeSubscriptions')->take(5)->get();

        if ($members->isEmpty()) {
            return;
        }

        foreach ($members as $index => $member) {
            $sub = $member->activeSubscriptions->first();
            $status = $index % 2 === 0 ? 'IN' : 'OUT';
            $time = Carbon::today()->subMinutes(15 * ($index + 1));

            Attendance::create([
                'member_id' => $member->id,
                'rfid_uid' => $member->rfid_uid ?? 'RFID-'.rand(1000, 9999),
                'status' => $status,
                'snapshot_path' => null,
                'member_subscription_id' => $sub?->id,
                'scanned_at' => $time,
            ]);
        }
    }
}
