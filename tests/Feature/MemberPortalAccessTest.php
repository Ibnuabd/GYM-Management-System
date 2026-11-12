<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\PtSessionLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Issue #22: portal member bisa dipakai member sungguhan dengan aman.
 * - Staf mengatur/mereset password portal member.
 * - Member yang tidak aktif dikeluarkan dari portal.
 * - API portal hanya mengirim data untuk member.
 * - Logout member tidak ikut mengeluarkan staf di browser yang sama.
 */
class MemberPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $cs;

    protected User $pt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->cs = User::where('email', 'cs@fitcore.com')->first();
        $this->pt = User::where('email', 'pt@fitcore.com')->first();
    }

    // ---------------------------------------------------------------
    // Staf mengatur password portal
    // ---------------------------------------------------------------

    public function test_staff_can_set_portal_password_and_member_can_then_log_in(): void
    {
        $member = Member::factory()->create(['email' => 'baru@fitcore.com', 'password' => null, 'status' => 'active']);

        $response = $this->actingAs($this->cs)->put(route('members.portal-password.update', $member), [
            'password' => 'rahasia-123',
            'password_confirmation' => 'rahasia-123',
        ]);

        $response->assertRedirect(route('members.show', $member));
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('rahasia-123', $member->fresh()->password), 'Password harus tersimpan dalam bentuk hash.');

        // Member sekarang bisa login ke portal.
        Auth::guard('web')->logout();
        $this->post(route('portal.login'), ['login' => 'baru@fitcore.com', 'password' => 'rahasia-123'])
            ->assertRedirect(route('portal.dashboard'));
        $this->assertTrue(Auth::guard('member')->check());
    }

    public function test_member_profile_shows_whether_portal_password_is_set(): void
    {
        $member = Member::factory()->create(['password' => null]);

        $this->actingAs($this->cs)->get(route('members.show', $member))
            ->assertInertia(fn ($page) => $page->where('hasPortalPassword', false));
    }

    public function test_portal_password_must_be_at_least_8_characters_and_confirmed(): void
    {
        $member = Member::factory()->create(['password' => null]);

        $this->actingAs($this->cs)->put(route('members.portal-password.update', $member), [
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->actingAs($this->cs)->put(route('members.portal-password.update', $member), [
            'password' => 'rahasia-123',
            'password_confirmation' => 'beda-password',
        ])->assertSessionHasErrors('password');

        $this->assertNull($member->fresh()->password);
    }

    public function test_staff_without_manage_members_permission_cannot_set_portal_password(): void
    {
        $member = Member::factory()->create(['password' => null]);

        $this->actingAs($this->pt)->put(route('members.portal-password.update', $member), [
            'password' => 'rahasia-123',
            'password_confirmation' => 'rahasia-123',
        ])->assertForbidden();

        $this->assertNull($member->fresh()->password);
    }

    // ---------------------------------------------------------------
    // Member tidak aktif
    // ---------------------------------------------------------------

    public function test_member_suspended_after_login_is_logged_out_on_next_portal_request(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $this->actingAs($member, 'member')->get(route('portal.dashboard'))->assertOk();

        // Staf menangguhkan member SETELAH member login.
        $member->update(['status' => 'suspended']);

        $response = $this->get(route('portal.dashboard'));

        $response->assertRedirect(route('portal.login'));
        $response->assertSessionHasErrors('login');
        $this->assertFalse(Auth::guard('member')->check());
    }

    public function test_suspended_member_cannot_use_portal_json_endpoints(): void
    {
        $member = Member::factory()->create(['status' => 'suspended']);

        $this->actingAs($member, 'member')->getJson(route('portal.pt-progress'))
            ->assertRedirect(route('portal.login'));
    }

    public function test_inactive_member_cannot_log_in(): void
    {
        Member::factory()->create(['email' => 'nonaktif@fitcore.com', 'password' => 'rahasia-123', 'status' => 'inactive']);

        $this->post(route('portal.login'), ['login' => 'nonaktif@fitcore.com', 'password' => 'rahasia-123'])
            ->assertSessionHasErrors('login');

        $this->assertFalse(Auth::guard('member')->check());
    }

    // ---------------------------------------------------------------
    // Data API portal
    // ---------------------------------------------------------------

    public function test_portal_json_endpoints_do_not_expose_internal_data(): void
    {
        $trainer = User::factory()->create(['name' => 'Coach Rahasia', 'email' => 'trainer.rahasia@fitcore.com']);
        $member = Member::factory()->create(['status' => 'active']);
        $subscription = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => Package::factory()->create(['type' => 'pt'])->id,
            'pt_user_id' => $trainer->id,
            'total_sessions' => 10,
            'price_paid' => 2400000,
            'notes' => 'CATATAN INTERNAL STAF',
        ]);
        PtSessionLog::factory()->create([
            'member_subscription_id' => $subscription->id,
            'member_id' => $member->id,
            'pt_user_id' => $trainer->id,
        ]);

        $this->actingAs($member, 'member');

        $ptProgress = $this->getJson(route('portal.pt-progress'))->assertOk();
        $ptProgress->assertJsonPath('0.trainer_name', 'Coach Rahasia');
        $ptProgress->assertJsonMissingPath('0.notes');
        $ptProgress->assertJsonMissingPath('0.price_paid');
        $this->assertStringNotContainsString('trainer.rahasia@fitcore.com', $ptProgress->getContent());
        $this->assertStringNotContainsString('CATATAN INTERNAL STAF', $ptProgress->getContent());

        $workouts = $this->getJson(route('portal.workouts'))->assertOk();
        $workouts->assertJsonPath('0.trainer_name', 'Coach Rahasia');
        $this->assertStringNotContainsString('trainer.rahasia@fitcore.com', $workouts->getContent());
    }

    // ---------------------------------------------------------------
    // Logout member
    // ---------------------------------------------------------------

    public function test_member_logout_does_not_log_out_staff_in_the_same_browser(): void
    {
        $member = Member::factory()->create(['status' => 'active']);

        // Staf dan member sama-sama login di browser yang sama.
        $this->actingAs($this->cs, 'web');
        $this->actingAs($member, 'member');

        $this->post(route('portal.logout'))->assertRedirect(route('portal.login'));

        $this->assertFalse(Auth::guard('member')->check(), 'Member harus keluar.');
        $this->assertAuthenticatedAs($this->cs, 'web');
    }
}
