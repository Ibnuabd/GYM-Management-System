<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman 404 khusus (Pages/Error.vue) saat halaman tidak ditemukan.
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unknown_url_shows_custom_404_page_for_guest(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 404)
            );
    }

    public function test_unknown_url_shows_custom_404_page_for_logged_in_staff(): void
    {
        $admin = User::where('email', 'admin@fitcore.com')->first();

        $this->actingAs($admin)->get('/menu-tidak-ada/123')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
    }

    public function test_missing_record_shows_custom_404_page(): void
    {
        $cs = User::where('email', 'cs@fitcore.com')->first();

        // Member dengan ID ini tidak ada -> route model binding melempar 404
        $this->actingAs($cs)->get('/members/999999')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
    }

    public function test_inertia_navigation_to_unknown_url_returns_error_page_as_inertia_response(): void
    {
        $admin = User::where('email', 'admin@fitcore.com')->first();

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(new Request),
            ])
            ->get('/tidak-ada')
            ->assertNotFound()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Error')
            ->assertJsonPath('props.status', 404);
    }

    public function test_json_request_to_unknown_url_still_gets_json_404(): void
    {
        $this->getJson('/api-tidak-ada')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
