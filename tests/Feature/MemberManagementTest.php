<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cs;

    protected User $pt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PackageSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->cs = User::where('email', 'cs@fitcore.com')->first();
        $this->pt = User::where('email', 'pt@fitcore.com')->first();
    }

    public function test_cs_and_admin_can_view_member_list(): void
    {
        $response = $this->actingAs($this->cs)->get(route('members.index'));
        $response->assertStatus(200);

        $responseAdmin = $this->actingAs($this->admin)->get(route('members.index'));
        $responseAdmin->assertStatus(200);
    }

    public function test_user_without_permission_cannot_access_members(): void
    {
        // PT does not have manage_members permission
        $response = $this->actingAs($this->pt)->get(route('members.index'));
        $response->assertStatus(403);
    }

    public function test_can_register_new_member_with_required_fields_and_photo(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('member_avatar.jpg', 300, 300);

        $payload = [
            'nik' => '3201998877660001',
            'full_name' => 'Budi Santoso',
            'phone' => '081299998888',
            'email' => 'budi.santoso@gmail.com',
            'rfid_uid' => 'AB-12-CD-34',
            'gender' => 'male',
            'birth_date' => '1993-05-15',
            'address' => 'Jl. Sudirman Kav 25, Jakarta Pusat',
            'photo' => $photo,
        ];

        $response = $this->actingAs($this->cs)->post(route('members.store'), $payload);

        $this->assertDatabaseHas('members', [
            'nik' => '3201998877660001',
            'full_name' => 'Budi Santoso',
            'phone' => '081299998888',
            'rfid_uid' => 'AB-12-CD-34',
            'status' => 'active',
        ]);

        $member = Member::where('rfid_uid', 'AB-12-CD-34')->first();
        $this->assertNotNull($member);
        $this->assertStringStartsWith('MBR-', $member->member_code);
        $this->assertNotNull($member->photo_url);

        Storage::disk('public')->assertExists($member->photo_url);

        $response->assertRedirect(route('members.show', $member));
    }

    public function test_member_registration_validates_required_fields(): void
    {
        $response = $this->actingAs($this->cs)->post(route('members.store'), [
            'full_name' => '',
            'phone' => '',
            'gender' => '',
        ]);

        $response->assertSessionHasErrors(['full_name', 'phone', 'gender']);
    }

    public function test_rfid_uid_and_nik_must_be_unique(): void
    {
        Member::create([
            'member_code' => 'MBR-202610-9999',
            'nik' => '3201111122223333',
            'full_name' => 'Existing Member',
            'phone' => '08111111111',
            'rfid_uid' => 'UNIQUE-RFID-1',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->cs)->post(route('members.store'), [
            'nik' => '3201111122223333',
            'full_name' => 'Duplicate Member',
            'phone' => '08222222222',
            'rfid_uid' => 'UNIQUE-RFID-1',
            'gender' => 'male',
        ]);

        $response->assertSessionHasErrors(['nik', 'rfid_uid']);
    }

    public function test_rfid_check_endpoint_reports_uniqueness(): void
    {
        Member::create([
            'member_code' => 'MBR-202610-8888',
            'full_name' => 'Cardholder One',
            'phone' => '081233334444',
            'rfid_uid' => 'CARD-100',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // Existing RFID
        $resExisting = $this->actingAs($this->cs)->get(route('members.check-rfid', ['rfid' => 'CARD-100']));
        $resExisting->assertJson([
            'unique' => false,
        ]);

        // Brand new RFID
        $resNew = $this->actingAs($this->cs)->get(route('members.check-rfid', ['rfid' => 'CARD-999']));
        $resNew->assertJson([
            'unique' => true,
        ]);
    }

    public function test_registering_member_with_initial_package_calculates_dates(): void
    {
        $gymPackage = Package::where('type', 'gym')->first();

        $payload = [
            'full_name' => 'Indra Wijaya',
            'phone' => '081377778888',
            'rfid_uid' => 'IN-DR-01-99',
            'gender' => 'male',
            'package_id' => $gymPackage->id,
            'start_date' => Carbon::today()->toDateString(),
        ];

        $response = $this->actingAs($this->cs)->post(route('members.store'), $payload);

        $member = Member::where('rfid_uid', 'IN-DR-01-99')->first();
        $this->assertNotNull($member);

        $subscription = $member->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertEquals($gymPackage->id, $subscription->package_id);
        $this->assertEquals(Carbon::today()->toDateString(), $subscription->start_date->toDateString());

        $expectedEnd = Carbon::today()->addDays($gymPackage->duration_days - 1)->toDateString();
        $this->assertEquals($expectedEnd, $subscription->end_date->toDateString());
        $this->assertEquals('active', $subscription->status);
    }

    public function test_subscription_assignment_with_pt_allocates_sessions_and_initializes_zero_used(): void
    {
        $member = Member::create([
            'member_code' => Member::generateMemberCode(),
            'full_name' => 'Sinta Nur',
            'phone' => '081288889999',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $ptPackage = Package::where('type', 'pt')->first();
        $this->assertNotNull($ptPackage);
        $this->assertGreaterThan(0, $ptPackage->session_count);

        $response = $this->actingAs($this->cs)->post(route('subscriptions.store', $member), [
            'package_id' => $ptPackage->id,
            'pt_user_id' => $this->pt->id,
            'start_date' => Carbon::today()->toDateString(),
        ]);

        $response->assertRedirect(route('members.show', $member));

        $subscription = MemberSubscription::where('member_id', $member->id)
            ->where('package_id', $ptPackage->id)
            ->first();

        $this->assertNotNull($subscription);
        $this->assertEquals($ptPackage->session_count, $subscription->total_sessions);
        $this->assertEquals(0, $subscription->used_sessions);
        $this->assertEquals($this->pt->id, $subscription->pt_user_id);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($ptPackage->session_count, $subscription->remaining_sessions);
    }

    public function test_multi_subscription_support_with_queued_package_duration_calculation(): void
    {
        $member = Member::create([
            'member_code' => Member::generateMemberCode(),
            'full_name' => 'Doni Kusuma',
            'phone' => '081900001111',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $gym1Mo = Package::where('name', 'Regular Gym (1 Bulan)')->first();
        $gym3Mo = Package::where('name', 'Regular Gym (3 Bulan)')->first();

        // 1. Assign first active subscription starting today
        $this->actingAs($this->cs)->post(route('subscriptions.store', $member), [
            'package_id' => $gym1Mo->id,
            'start_date' => Carbon::today()->toDateString(),
        ]);

        $activeSub = $member->subscriptions()->where('status', 'active')->first();
        $this->assertNotNull($activeSub);

        // 2. Assign second package with queue_after_active = true
        $this->actingAs($this->cs)->post(route('subscriptions.store', $member), [
            'package_id' => $gym3Mo->id,
            'queue_after_active' => true,
        ]);

        $queuedSub = $member->subscriptions()->where('status', 'queued')->first();
        $this->assertNotNull($queuedSub);

        // Queued sub should start exactly 1 day after active sub ends
        $expectedQueueStart = Carbon::parse($activeSub->end_date)->addDay()->toDateString();
        $this->assertEquals($expectedQueueStart, $queuedSub->start_date->toDateString());

        $expectedQueueEnd = Carbon::parse($queuedSub->start_date)->addDays($gym3Mo->duration_days - 1)->toDateString();
        $this->assertEquals($expectedQueueEnd, $queuedSub->end_date->toDateString());
    }

    public function test_quick_search_endpoint_returns_instant_matches_by_name_phone_and_rfid(): void
    {
        $member = Member::create([
            'member_code' => 'MBR-9999',
            'full_name' => 'Citra Kirana',
            'phone' => '081299112233',
            'rfid_uid' => 'CK-99-88-77',
            'gender' => 'female',
            'status' => 'active',
        ]);

        // Search by name
        $resName = $this->actingAs($this->cs)->get(route('members.quick-search', ['q' => 'Citra']));
        $resName->assertJsonFragment(['full_name' => 'Citra Kirana']);

        // Search by phone
        $resPhone = $this->actingAs($this->cs)->get(route('members.quick-search', ['q' => '991122']));
        $resPhone->assertJsonFragment(['full_name' => 'Citra Kirana']);

        // Search by RFID card UID
        $resRfid = $this->actingAs($this->cs)->get(route('members.quick-search', ['q' => 'CK-99']));
        $resRfid->assertJsonFragment(['full_name' => 'Citra Kirana']);
    }

    public function test_member_profile_shows_computed_validity_days_and_pt_balances(): void
    {
        $member = Member::create([
            'member_code' => Member::generateMemberCode(),
            'full_name' => 'Taufik Hidayat',
            'phone' => '081822334455',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $comboPackage = Package::where('type', 'combo')->first();

        // Assign combo subscription
        $this->actingAs($this->cs)->post(route('subscriptions.store', $member), [
            'package_id' => $comboPackage->id,
            'pt_user_id' => $this->pt->id,
            'start_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($this->cs)->get(route('members.show', $member));
        $response->assertStatus(200);

        $refreshed = $member->fresh();
        $this->assertTrue($refreshed->has_active_subscription);
        $this->assertGreaterThan(0, $refreshed->remaining_validity_days);
        $this->assertEquals($comboPackage->session_count, $refreshed->pt_session_balance);
    }
}
