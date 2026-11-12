<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\PtSessionLog;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalTrainerSessionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cs;

    protected User $pt1;

    protected User $pt2;

    protected Package $ptPackage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PackageSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->cs = User::where('email', 'cs@fitcore.com')->first();
        $this->pt1 = User::where('email', 'pt@fitcore.com')->first();

        // Create a second PT for isolation testing
        $this->pt2 = User::factory()->create([
            'name' => 'Coach Ayu (PT 2)',
            'email' => 'pt2@fitcore.com',
        ]);
        $this->pt2->assignRole('pt');

        $this->ptPackage = Package::where('type', 'pt')->first() ?? Package::factory()->create([
            'name' => 'PT 12 Sesi',
            'type' => 'pt',
            'session_count' => 12,
            'duration_days' => 60,
            'price' => 2400000,
        ]);
    }

    public function test_guest_cannot_access_pt_dashboard(): void
    {
        $response = $this->get(route('pt.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_pt_dashboard(): void
    {
        // CS role does not have manage_pt_sessions permission
        $response = $this->actingAs($this->cs)->get(route('pt.dashboard'));
        $response->assertStatus(403);
    }

    public function test_pt_and_admin_can_access_pt_dashboard(): void
    {
        $responsePt = $this->actingAs($this->pt1)->get(route('pt.dashboard'));
        $responsePt->assertStatus(200);
        $responsePt->assertInertia(fn ($page) => $page
            ->component('Pt/Dashboard')
            ->has('subscriptions')
            ->has('historicalLogs')
            ->has('metrics')
        );

        $responseAdmin = $this->actingAs($this->admin)->get(route('pt.dashboard'));
        $responseAdmin->assertStatus(200);
    }

    public function test_pt_dashboard_view_is_restricted_to_members_assigned_to_authenticated_trainer(): void
    {
        $memberForPt1 = Member::factory()->create(['full_name' => 'Member Khusus Coach Dimas']);
        $subPt1 = MemberSubscription::factory()->create([
            'member_id' => $memberForPt1->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 10,
            'used_sessions' => 2,
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(25),
        ]);

        $memberForPt2 = Member::factory()->create(['full_name' => 'Member Khusus Coach Ayu']);
        $subPt2 = MemberSubscription::factory()->create([
            'member_id' => $memberForPt2->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt2->id,
            'total_sessions' => 8,
            'used_sessions' => 1,
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(2),
            'end_date' => Carbon::today()->addDays(28),
        ]);

        // When PT1 logs in: only sees subPt1, not subPt2
        $response = $this->actingAs($this->pt1)->get(route('pt.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($subPt1, $subPt2) {
            $page->component('Pt/Dashboard');
            $subscriptionIds = collect($page->toArray()['props']['subscriptions'])->pluck('id')->all();

            $this->assertContains($subPt1->id, $subscriptionIds);
            $this->assertNotContains($subPt2->id, $subscriptionIds);
        });
    }

    public function test_pt_can_search_assigned_members_by_phone_number(): void
    {
        $matchingSubscription = MemberSubscription::factory()->create([
            'member_id' => Member::factory()->create(['full_name' => 'Rina Cocok', 'phone' => '081277778888'])->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 10,
            'status' => 'active',
        ]);

        $otherSubscription = MemberSubscription::factory()->create([
            'member_id' => Member::factory()->create(['full_name' => 'Budi Lain', 'phone' => '085600001111'])->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 10,
            'status' => 'active',
        ]);

        // Member milik PT lain dengan nomor HP yang sama: tetap TIDAK boleh muncul.
        $otherTrainerSubscription = MemberSubscription::factory()->create([
            'member_id' => Member::factory()->create(['phone' => '081277778888'])->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt2->id,
            'total_sessions' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->pt1)->get(route('pt.dashboard', ['search' => '7777']));

        $response->assertOk();
        $response->assertInertia(function ($page) use ($matchingSubscription, $otherSubscription, $otherTrainerSubscription) {
            $subscriptions = collect($page->toArray()['props']['subscriptions']);

            $this->assertEquals([$matchingSubscription->id], $subscriptions->pluck('id')->all());
            $this->assertNotContains($otherSubscription->id, $subscriptions->pluck('id'));
            $this->assertNotContains($otherTrainerSubscription->id, $subscriptions->pluck('id'));
            $this->assertEquals('081277778888', $subscriptions->first()['phone']);
        });
    }

    public function test_deduct_button_is_disabled_when_quota_is_zero_or_subscription_is_expired(): void
    {
        $baseAttributes = [
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 10,
        ];

        $usableSubscription = MemberSubscription::factory()->create([
            ...$baseAttributes,
            'member_id' => Member::factory()->create()->id,
            'used_sessions' => 3,
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(20),
        ]);

        $exhaustedSubscription = MemberSubscription::factory()->create([
            ...$baseAttributes,
            'member_id' => Member::factory()->create()->id,
            'used_sessions' => 10,
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(20),
        ]);

        $expiredSubscription = MemberSubscription::factory()->create([
            ...$baseAttributes,
            'member_id' => Member::factory()->create()->id,
            'used_sessions' => 2,
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(40),
            'end_date' => Carbon::yesterday(),
        ]);

        $response = $this->actingAs($this->pt1)->get(route('pt.dashboard', ['status' => 'all']));

        $response->assertInertia(function ($page) use ($usableSubscription, $exhaustedSubscription, $expiredSubscription) {
            $canDeductById = collect($page->toArray()['props']['subscriptions'])->pluck('can_deduct', 'id');

            $this->assertTrue($canDeductById[$usableSubscription->id]);
            $this->assertFalse($canDeductById[$exhaustedSubscription->id]);
            $this->assertFalse($canDeductById[$expiredSubscription->id]);
        });
    }

    public function test_pt_can_atomically_deduct_session_and_record_activity_log(): void
    {
        $member = Member::factory()->create(['full_name' => 'Budi Pratama']);
        $sub = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 12,
            'used_sessions' => 3,
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today()->addDays(50),
        ]);

        $payload = [
            'member_subscription_id' => $sub->id,
            'duration_minutes' => 60,
            'muscle_focus' => 'Dada & Triceps (Chest & Triceps)',
            'workout_notes' => "• Barbell Bench Press: 4x10 (70kg)\n• Incline Dumbbell Press: 3x12 (22kg)\n• Cable Fly: 3x15 (15kg)",
            'member_feedback' => 'Postur stabil dan progres repetisi meningkat dengan baik.',
            'session_date' => Carbon::now()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->pt1)->post(route('pt.sessions.store'), $payload);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        // Verify atomic session increment in DB
        $sub->refresh();
        $this->assertEquals(4, $sub->used_sessions);
        $this->assertEquals(8, $sub->remaining_sessions);
        $this->assertEquals('active', $sub->status);

        // Verify PtSessionLog record was created
        $this->assertDatabaseHas('pt_session_logs', [
            'member_subscription_id' => $sub->id,
            'member_id' => $member->id,
            'pt_user_id' => $this->pt1->id,
            'duration_minutes' => 60,
            'muscle_focus' => 'Dada & Triceps (Chest & Triceps)',
            'member_feedback' => 'Postur stabil dan progres repetisi meningkat dengan baik.',
        ]);
    }

    public function test_subscription_status_becomes_exhausted_when_last_session_is_deducted(): void
    {
        $member = Member::factory()->create();
        $sub = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 5,
            'used_sessions' => 4, // 1 session remaining
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(15),
            'end_date' => Carbon::today()->addDays(15),
        ]);

        $payload = [
            'member_subscription_id' => $sub->id,
            'duration_minutes' => 60,
            'muscle_focus' => 'Leg Day Final Session',
            'workout_notes' => 'Squat 5x5, Romanian Deadlift 4x10',
        ];

        $response = $this->actingAs($this->pt1)->post(route('pt.sessions.store'), $payload);
        $response->assertSessionHas('success');

        $sub->refresh();
        $this->assertEquals(5, $sub->used_sessions);
        $this->assertEquals(0, $sub->remaining_sessions);
        $this->assertEquals('exhausted', $sub->status);
    }

    public function test_cannot_deduct_session_when_quota_is_already_exhausted(): void
    {
        $member = Member::factory()->create();
        $sub = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 5,
            'used_sessions' => 5, // 0 remaining
            'status' => 'exhausted',
            'start_date' => Carbon::today()->subDays(20),
            'end_date' => Carbon::today()->addDays(10),
        ]);

        $payload = [
            'member_subscription_id' => $sub->id,
            'duration_minutes' => 60,
            'muscle_focus' => 'Extra Workout Attempt',
            'workout_notes' => 'Attempting session when quota is 0',
        ];

        $response = $this->actingAs($this->pt1)->post(route('pt.sessions.store'), $payload);

        $response->assertSessionHasErrors('member_subscription_id');

        $sub->refresh();
        $this->assertEquals(5, $sub->used_sessions);
    }

    public function test_cannot_deduct_session_when_subscription_is_expired(): void
    {
        $member = Member::factory()->create();
        $sub = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 10,
            'used_sessions' => 2,
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(60),
            'end_date' => Carbon::today()->subDays(5), // expired 5 days ago
        ]);

        $payload = [
            'member_subscription_id' => $sub->id,
            'duration_minutes' => 60,
            'muscle_focus' => 'Expired Session Attempt',
            'workout_notes' => 'Attempting session on expired package',
        ];

        $response = $this->actingAs($this->pt1)->post(route('pt.sessions.store'), $payload);

        $response->assertSessionHasErrors('member_subscription_id');

        $sub->refresh();
        $this->assertEquals(2, $sub->used_sessions);
    }

    public function test_trainer_cannot_deduct_session_for_another_trainers_member(): void
    {
        $member = Member::factory()->create();
        $subPt2 = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt2->id, // Assigned to PT2
            'total_sessions' => 10,
            'used_sessions' => 0,
            'status' => 'active',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
        ]);

        // PT1 attempts to deduct PT2's client
        $payload = [
            'member_subscription_id' => $subPt2->id,
            'duration_minutes' => 60,
            'muscle_focus' => 'Unauthorized Session Attempt',
            'workout_notes' => 'Attempting session unauthorized',
        ];

        $response = $this->actingAs($this->pt1)->post(route('pt.sessions.store'), $payload);

        $response->assertStatus(403);

        $subPt2->refresh();
        $this->assertEquals(0, $subPt2->used_sessions);
    }

    public function test_activity_log_requires_mandatory_fields(): void
    {
        $member = Member::factory()->create();
        $sub = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 10,
            'used_sessions' => 0,
            'status' => 'active',
        ]);

        // Missing muscle_focus and workout_notes
        $payload = [
            'member_subscription_id' => $sub->id,
            'duration_minutes' => 60,
        ];

        $response = $this->actingAs($this->pt1)->post(route('pt.sessions.store'), $payload);

        $response->assertSessionHasErrors(['muscle_focus', 'workout_notes']);

        $sub->refresh();
        $this->assertEquals(0, $sub->used_sessions);
    }

    public function test_member_history_api_returns_chronological_session_logs(): void
    {
        $member = Member::factory()->create(['full_name' => 'Clarissa Stephanie']);
        $sub = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $this->ptPackage->id,
            'pt_user_id' => $this->pt1->id,
            'total_sessions' => 10,
            'used_sessions' => 2,
            'status' => 'active',
        ]);

        PtSessionLog::factory()->create([
            'member_subscription_id' => $sub->id,
            'member_id' => $member->id,
            'pt_user_id' => $this->pt1->id,
            'session_date' => Carbon::now()->subDays(4),
            'muscle_focus' => 'Back & Biceps',
            'workout_notes' => 'Lat Pulldown 4x12',
        ]);

        PtSessionLog::factory()->create([
            'member_subscription_id' => $sub->id,
            'member_id' => $member->id,
            'pt_user_id' => $this->pt1->id,
            'session_date' => Carbon::now()->subDays(1),
            'muscle_focus' => 'Leg Day Squats',
            'workout_notes' => 'Barbell Squat 4x10',
        ]);

        $response = $this->actingAs($this->pt1)->get(route('pt.members.history', $member->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'member' => ['id', 'name', 'code'],
            'logs' => [
                '*' => ['id', 'trainer_name', 'package_name', 'session_date_formatted', 'duration_minutes', 'muscle_focus', 'workout_notes'],
            ],
        ]);

        $data = $response->json();
        $this->assertCount(2, $data['logs']);
        $this->assertEquals('Leg Day Squats', $data['logs'][0]['muscle_focus']); // latest first
    }
}
