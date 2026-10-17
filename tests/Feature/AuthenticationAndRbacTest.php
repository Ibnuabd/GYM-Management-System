<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAndRbacTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and demo users
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guest_can_render_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@fitcore.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@fitcore.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $admin = User::where('email', 'admin@fitcore.com')->first();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_admin_has_full_access_to_all_modules(): void
    {
        $admin = User::where('email', 'admin@fitcore.com')->first();

        $this->actingAs($admin)->get('/kiosk')->assertStatus(200);
        $this->actingAs($admin)->get('/pos')->assertStatus(200);
        $this->actingAs($admin)->get('/pt')->assertStatus(200);
        $this->actingAs($admin)->get('/reports')->assertStatus(200);
        $this->actingAs($admin)->get('/users')->assertStatus(200);
    }

    public function test_supervisor_has_operational_and_report_access_but_forbidden_on_users(): void
    {
        $supervisor = User::where('email', 'supervisor@fitcore.com')->first();

        $this->actingAs($supervisor)->get('/kiosk')->assertStatus(200);
        $this->actingAs($supervisor)->get('/pos')->assertStatus(200);
        $this->actingAs($supervisor)->get('/reports')->assertStatus(200);
        
        // Supervisor cannot manage users
        $this->actingAs($supervisor)->get('/users')->assertStatus(403);
    }

    public function test_customer_service_has_frontliner_access_but_forbidden_on_reports_and_users(): void
    {
        $cs = User::where('email', 'cs@fitcore.com')->first();

        $this->actingAs($cs)->get('/kiosk')->assertStatus(200);
        $this->actingAs($cs)->get('/pos')->assertStatus(200);

        // CS cannot view reports or manage users
        $this->actingAs($cs)->get('/reports')->assertStatus(403);
        $this->actingAs($cs)->get('/users')->assertStatus(403);
    }

    public function test_personal_trainer_has_pt_access_but_forbidden_on_pos_and_reports(): void
    {
        $pt = User::where('email', 'pt@fitcore.com')->first();

        $this->actingAs($pt)->get('/pt')->assertStatus(200);

        // PT cannot access POS, reports, or users
        $this->actingAs($pt)->get('/pos')->assertStatus(403);
        $this->actingAs($pt)->get('/reports')->assertStatus(403);
        $this->actingAs($pt)->get('/users')->assertStatus(403);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $admin = User::where('email', 'admin@fitcore.com')->first();

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
