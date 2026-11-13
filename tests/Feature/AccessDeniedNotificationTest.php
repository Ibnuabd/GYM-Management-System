<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Saat user membuka halaman tanpa izin dari DALAM aplikasi (klik menu/link Inertia),
 * user dikembalikan ke halaman sebelumnya dengan pop-up notifikasi, bukan halaman error.
 */
class AccessDeniedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->pt = User::where('email', 'pt@fitcore.com')->first();
    }

    public function test_inertia_visit_without_permission_returns_to_previous_page_with_error_popup(): void
    {
        $response = $this->actingAs($this->pt)
            ->from(route('dashboard'))
            ->withHeaders($this->inertiaHeaders())
            ->get(route('reports'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    }

    public function test_direct_url_and_json_requests_still_get_403(): void
    {
        // Mengetik URL langsung di browser (bukan request Inertia) tetap 403.
        $this->actingAs($this->pt)->get(route('reports'))->assertForbidden();

        // Request JSON (fetch) tetap 403.
        $this->actingAs($this->pt)->getJson(route('reports'))->assertForbidden();
    }

    public function test_user_management_menu_shows_info_popup_instead_of_json(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('dashboard'))
            ->withHeaders($this->inertiaHeaders())
            ->get(route('users'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('info');
        $this->assertStringNotContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function test_info_flash_is_shared_with_pages(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['info' => 'Pesan informasi uji'])
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('flash.info', 'Pesan informasi uji'));
    }

    /**
     * Header yang dikirim browser saat berpindah halaman lewat Inertia (klik menu/link).
     *
     * @return array<string, string>
     */
    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(new Request),
        ];
    }
}
