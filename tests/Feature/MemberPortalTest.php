<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\PtSessionLog;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guest_cannot_access_portal_dashboard_and_is_redirected_to_portal_login(): void
    {
        $response = $this->get('/portal');

        $response->assertRedirect('/portal/login');
    }

    public function test_portal_login_page_renders_successfully(): void
    {
        $response = $this->get('/portal/login');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Portal/Login'));
    }

    public function test_member_can_login_with_email_and_password(): void
    {
        $member = Member::factory()->create([
            'email' => 'member.test@fitcore.com',
            'phone' => '081299998888',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $response = $this->post('/portal/login', [
            'login' => 'member.test@fitcore.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/portal');
        $this->assertTrue(Auth::guard('member')->check());
        $this->assertEquals($member->id, Auth::guard('member')->id());
    }

    public function test_member_can_login_with_phone_number_and_password(): void
    {
        $member = Member::factory()->create([
            'email' => 'phone.member@fitcore.com',
            'phone' => '087711223344',
            'password' => Hash::make('gympassword'),
            'status' => 'active',
        ]);

        $response = $this->post('/portal/login', [
            'login' => '087711223344',
            'password' => 'gympassword',
        ]);

        $response->assertRedirect('/portal');
        $this->assertTrue(Auth::guard('member')->check());
        $this->assertEquals($member->id, Auth::guard('member')->id());
    }

    public function test_member_can_login_with_member_code_and_password(): void
    {
        $member = Member::factory()->create([
            'member_code' => 'MBR-9999-001',
            'password' => Hash::make('codePass123'),
            'status' => 'active',
        ]);

        $response = $this->post('/portal/login', [
            'login' => 'MBR-9999-001',
            'password' => 'codePass123',
        ]);

        $response->assertRedirect('/portal');
        $this->assertTrue(Auth::guard('member')->check());
    }

    public function test_member_cannot_login_with_invalid_credentials(): void
    {
        Member::factory()->create([
            'email' => 'aditya@fitcore.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post('/portal/login', [
            'login' => 'aditya@fitcore.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertFalse(Auth::guard('member')->check());
    }

    public function test_suspended_member_cannot_login(): void
    {
        Member::factory()->create([
            'email' => 'suspended@fitcore.com',
            'password' => Hash::make('password'),
            'status' => 'suspended',
        ]);

        $response = $this->post('/portal/login', [
            'login' => 'suspended@fitcore.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertFalse(Auth::guard('member')->check());
    }

    public function test_authenticated_member_can_view_portal_dashboard_with_card_and_pt_progress(): void
    {
        $ptUser = User::role('pt')->first() ?? User::factory()->create();

        $member = Member::factory()->create([
            'member_code' => 'MBR-TEST-001',
            'full_name' => 'Bambang Sudirman',
            'rfid_uid' => 'RF-99-88-77',
            'status' => 'active',
        ]);

        $package = Package::factory()->create([
            'name' => 'Personal Trainer 12 Sesi',
            'type' => 'pt',
            'session_count' => 12,
            'duration_days' => 30,
        ]);

        $subscription = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'pt_user_id' => $ptUser->id,
            'start_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(25),
            'total_sessions' => 12,
            'used_sessions' => 4,
            'status' => 'active',
        ]);

        // Create 2 workout session logs
        PtSessionLog::factory()->create([
            'member_subscription_id' => $subscription->id,
            'member_id' => $member->id,
            'pt_user_id' => $ptUser->id,
            'session_date' => Carbon::now()->subDays(3),
            'duration_minutes' => 60,
            'muscle_focus' => 'Dada & Triceps',
            'workout_notes' => 'Bench Press 4x10 @ 70kg, Incline Fly 3x12',
            'member_feedback' => 'Teknik bench press sangat baik',
        ]);

        PtSessionLog::factory()->create([
            'member_subscription_id' => $subscription->id,
            'member_id' => $member->id,
            'pt_user_id' => $ptUser->id,
            'session_date' => Carbon::now()->subDays(1),
            'duration_minutes' => 60,
            'muscle_focus' => 'Punggung & Biceps',
            'workout_notes' => 'Lat Pulldown 4x12 @ 60kg, Dumbbell Row 3x10',
            'member_feedback' => 'Tarikan kuat dan stabil',
        ]);

        $response = $this->actingAs($member, 'member')->get('/portal');

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($member) {
            $page->component('Portal/Dashboard')
                ->has('member', fn (Assert $m) => $m
                    ->where('id', $member->id)
                    ->where('member_code', 'MBR-TEST-001')
                    ->where('full_name', 'Bambang Sudirman')
                    ->etc()
                )
                ->has('digitalCard', fn (Assert $c) => $c
                    ->where('member_code', 'MBR-TEST-001')
                    ->where('rfid_uid', 'RF-99-88-77')
                    ->where('is_active', true)
                    ->where('countdown_days', 25)
                    ->has('active_packages', 1)
                    ->etc()
                )
                ->has('ptSubscriptions', 1, fn (Assert $pt) => $pt
                    ->where('package_name', 'Personal Trainer 12 Sesi')
                    ->where('total_sessions', 12)
                    ->where('used_sessions', 4)
                    ->where('remaining_sessions', 8)
                    ->where('progress_percent', 33)
                    ->etc()
                )
                ->has('workoutHistory', 2)
                ->has('recentAttendances');
        });
    }

    public function test_authenticated_member_can_access_read_only_json_endpoints(): void
    {
        $member = Member::factory()->create([
            'member_code' => 'MBR-JSON-001',
            'status' => 'active',
        ]);

        $this->actingAs($member, 'member');

        $cardResponse = $this->getJson('/portal/card');
        $cardResponse->assertOk()
            ->assertJsonPath('member_code', 'MBR-JSON-001');

        $ptResponse = $this->getJson('/portal/pt-progress');
        $ptResponse->assertOk();

        $workoutsResponse = $this->getJson('/portal/workouts');
        $workoutsResponse->assertOk();
    }

    public function test_member_has_strict_read_only_access_and_cannot_access_staff_routes(): void
    {
        $member = Member::factory()->create([
            'status' => 'active',
        ]);

        // When authenticated as member, accessing staff dashboard redirects to staff login
        $response = $this->actingAs($member, 'member')->get('/dashboard');
        $response->assertRedirect('/login');

        // Accessing staff members management
        $membersResponse = $this->actingAs($member, 'member')->get('/members');
        $membersResponse->assertRedirect('/login');

        // Accessing POS cashier
        $posResponse = $this->actingAs($member, 'member')->get('/pos');
        $posResponse->assertRedirect('/login');

        // Cannot mutate members or subscriptions
        $postResponse = $this->actingAs($member, 'member')->postJson('/members', [
            'full_name' => 'Hacker Name',
            'phone' => '081234567899',
        ]);
        $this->assertTrue(in_array($postResponse->status(), [401, 403]));
    }

    public function test_member_can_logout_successfully(): void
    {
        $member = Member::factory()->create();

        $response = $this->actingAs($member, 'member')->post('/portal/logout');

        $response->assertRedirect('/portal/login');
        $this->assertFalse(Auth::guard('member')->check());
    }
}
