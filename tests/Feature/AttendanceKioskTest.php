<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttendanceKioskTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cs;

    protected User $pt;

    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PackageSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->cs = User::where('email', 'cs@fitcore.com')->first();
        $this->pt = User::where('email', 'pt@fitcore.com')->first();

        $this->package = Package::first();
    }

    public function test_guest_cannot_access_kiosk_page_or_scan(): void
    {
        $this->get(route('kiosk'))->assertRedirect(route('login'));
        $this->postJson(route('kiosk.scan'), ['rfid_uid' => 'CARD123'])->assertStatus(401);
    }

    public function test_user_without_scan_attendance_permission_cannot_access_kiosk(): void
    {
        // PT has manage_pt_sessions but not scan_attendance
        $this->actingAs($this->pt)->get(route('kiosk'))->assertStatus(403);
        $this->actingAs($this->pt)->postJson(route('kiosk.scan'), ['rfid_uid' => 'CARD123'])->assertStatus(403);
    }

    public function test_cs_and_admin_can_access_kiosk_interface(): void
    {
        $response = $this->actingAs($this->cs)->get(route('kiosk'));
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Kiosk/Index')
            ->has('recentAttendances')
            ->has('stats')
        );

        $responseAdmin = $this->actingAs($this->admin)->get(route('kiosk'));
        $responseAdmin->assertStatus(200);
    }

    public function test_scan_with_unregistered_rfid_card_returns_422_rejection(): void
    {
        $response = $this->actingAs($this->cs)->postJson(route('kiosk.scan'), [
            'rfid_uid' => 'UNKNOWN_RFID_9999',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_code' => 'UNREGISTERED_CARD',
        ]);
    }

    public function test_scan_with_inactive_member_returns_422_rejection(): void
    {
        $member = Member::factory()->create([
            'status' => 'inactive',
            'rfid_uid' => 'INACTIVE_CARD_123',
        ]);

        MemberSubscription::create([
            'member_id' => $member->id,
            'package_id' => $this->package->id,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
            'total_sessions' => 0,
            'used_sessions' => 0,
            'price_paid' => $this->package->price,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->cs)->postJson(route('kiosk.scan'), [
            'rfid_uid' => 'INACTIVE_CARD_123',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_code' => 'INACTIVE_MEMBER',
        ]);
    }

    public function test_scan_with_expired_subscription_returns_422_rejection(): void
    {
        $member = Member::factory()->create([
            'status' => 'active',
            'rfid_uid' => 'EXPIRED_CARD_456',
        ]);

        // Subscription expired yesterday
        MemberSubscription::create([
            'member_id' => $member->id,
            'package_id' => $this->package->id,
            'start_date' => Carbon::today()->subDays(60),
            'end_date' => Carbon::yesterday(),
            'total_sessions' => 0,
            'used_sessions' => 0,
            'price_paid' => $this->package->price,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->cs)->postJson(route('kiosk.scan'), [
            'rfid_uid' => 'EXPIRED_CARD_456',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_code' => 'EXPIRED_SUBSCRIPTION',
        ]);
    }

    public function test_valid_member_first_tap_today_records_in_status(): void
    {
        $member = Member::factory()->create([
            'status' => 'active',
            'rfid_uid' => 'VALID_CARD_001',
        ]);

        $sub = MemberSubscription::create([
            'member_id' => $member->id,
            'package_id' => $this->package->id,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
            'total_sessions' => 0,
            'used_sessions' => 0,
            'price_paid' => $this->package->price,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->cs)->postJson(route('kiosk.scan'), [
            'rfid_uid' => 'VALID_CARD_001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'attendance' => [
                'member_id' => $member->id,
                'status' => 'IN',
                'rfid_uid' => 'VALID_CARD_001',
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'member_id' => $member->id,
            'rfid_uid' => 'VALID_CARD_001',
            'status' => 'IN',
            'member_subscription_id' => $sub->id,
        ]);
    }

    public function test_valid_member_second_tap_today_toggles_to_out_status(): void
    {
        $member = Member::factory()->create([
            'status' => 'active',
            'rfid_uid' => 'VALID_CARD_002',
        ]);

        $sub = MemberSubscription::create([
            'member_id' => $member->id,
            'package_id' => $this->package->id,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
            'total_sessions' => 0,
            'used_sessions' => 0,
            'price_paid' => $this->package->price,
            'status' => 'active',
        ]);

        // First tap: IN
        Attendance::create([
            'member_id' => $member->id,
            'rfid_uid' => 'VALID_CARD_002',
            'status' => 'IN',
            'member_subscription_id' => $sub->id,
            'scanned_at' => Carbon::now()->subMinutes(45),
        ]);

        // Second tap should toggle to OUT
        $response = $this->actingAs($this->cs)->postJson(route('kiosk.scan'), [
            'rfid_uid' => 'VALID_CARD_002',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'attendance' => [
                'member_id' => $member->id,
                'status' => 'OUT',
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'member_id' => $member->id,
            'status' => 'OUT',
        ]);
    }

    public function test_valid_member_third_tap_today_toggles_back_to_in_status(): void
    {
        $member = Member::factory()->create([
            'status' => 'active',
            'rfid_uid' => 'VALID_CARD_003',
        ]);

        $sub = MemberSubscription::create([
            'member_id' => $member->id,
            'package_id' => $this->package->id,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
            'total_sessions' => 0,
            'used_sessions' => 0,
            'price_paid' => $this->package->price,
            'status' => 'active',
        ]);

        // Prior taps: IN then OUT
        Attendance::create([
            'member_id' => $member->id,
            'rfid_uid' => 'VALID_CARD_003',
            'status' => 'IN',
            'member_subscription_id' => $sub->id,
            'scanned_at' => Carbon::now()->subHours(3),
        ]);

        Attendance::create([
            'member_id' => $member->id,
            'rfid_uid' => 'VALID_CARD_003',
            'status' => 'OUT',
            'member_subscription_id' => $sub->id,
            'scanned_at' => Carbon::now()->subHours(2),
        ]);

        // Third tap should toggle to IN
        $response = $this->actingAs($this->cs)->postJson(route('kiosk.scan'), [
            'rfid_uid' => 'VALID_CARD_003',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'attendance' => [
                'member_id' => $member->id,
                'status' => 'IN',
            ],
        ]);
    }

    public function test_webcam_snapshot_is_saved_to_structured_directory_and_database(): void
    {
        Storage::fake('public');

        $member = Member::factory()->create([
            'status' => 'active',
            'rfid_uid' => 'VALID_CARD_SNAP',
        ]);

        MemberSubscription::create([
            'member_id' => $member->id,
            'package_id' => $this->package->id,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
            'total_sessions' => 0,
            'used_sessions' => 0,
            'price_paid' => $this->package->price,
            'status' => 'active',
        ]);

        // Base64 tiny transparent 1x1 gif / jpeg representation
        $fakeBase64 = 'data:image/jpeg;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        $response = $this->actingAs($this->cs)->postJson(route('kiosk.scan'), [
            'rfid_uid' => 'VALID_CARD_SNAP',
            'snapshot' => $fakeBase64,
        ]);

        $response->assertStatus(200);
        $attendance = Attendance::where('member_id', $member->id)->latest('id')->first();

        $this->assertNotNull($attendance->snapshot_path);
        $this->assertStringStartsWith('attendances/'.Carbon::now()->format('Y/m'), $attendance->snapshot_path);

        Storage::disk('public')->assertExists($attendance->snapshot_path);
    }

    public function test_recent_attendance_logs_endpoint_returns_json_feed(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        Attendance::create([
            'member_id' => $member->id,
            'rfid_uid' => $member->rfid_uid ?? 'RFID-FEED',
            'status' => 'IN',
            'scanned_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->cs)->getJson(route('kiosk.recent'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recentAttendances',
            'stats' => [
                'today_in_count',
                'today_out_count',
                'total_scans_today',
            ],
        ]);
    }

    public function test_receptionist_dashboard_displays_real_attendance_logs(): void
    {
        $member = Member::factory()->create([
            'full_name' => 'Kiosk Test Member',
            'status' => 'active',
        ]);

        Attendance::create([
            'member_id' => $member->id,
            'rfid_uid' => 'CARD-DASHBOARD',
            'status' => 'IN',
            'scanned_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->cs)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('recentAttendances')
            ->where('recentAttendances.0.member_name', 'Kiosk Test Member')
        );
    }
}
