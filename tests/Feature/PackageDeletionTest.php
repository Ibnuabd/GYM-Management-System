<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Memastikan menghapus paket TIDAK menghapus riwayat langganan member.
 */
class PackageDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
    }

    /**
     * Semua status langganan yang ada di aplikasi.
     *
     * @return array<string, array{string}>
     */
    public static function subscriptionStatuses(): array
    {
        return [
            'active' => ['active'],
            'queued (sudah dibayar, belum mulai)' => ['queued'],
            'expired' => ['expired'],
            'exhausted' => ['exhausted'],
            'cancelled' => ['cancelled'],
        ];
    }

    #[DataProvider('subscriptionStatuses')]
    public function test_package_with_any_subscription_is_deactivated_instead_of_deleted(string $status): void
    {
        $package = Package::factory()->create(['is_active' => true]);
        $subscription = MemberSubscription::factory()->create([
            'member_id' => Member::factory()->create()->id,
            'package_id' => $package->id,
            'status' => $status,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('packages.destroy', $package));

        $response->assertRedirect(route('packages.index'));
        $response->assertSessionHas('success');

        // Paket masih ada, hanya nonaktif.
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'is_active' => false]);

        // Riwayat langganan tetap tersimpan.
        $this->assertDatabaseHas('member_subscriptions', ['id' => $subscription->id, 'status' => $status]);
    }

    public function test_package_without_subscriptions_is_deleted_permanently(): void
    {
        $package = Package::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('packages.destroy', $package));

        $response->assertRedirect(route('packages.index'));
        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_database_refuses_to_delete_a_package_that_still_has_subscriptions(): void
    {
        $package = Package::factory()->create();
        MemberSubscription::factory()->create([
            'member_id' => Member::factory()->create()->id,
            'package_id' => $package->id,
            'status' => 'expired',
        ]);

        // Pengaman kedua: walaupun ada kode yang langsung memanggil delete(),
        // foreign key "restrict" di database menolaknya.
        $this->expectException(QueryException::class);

        $package->delete();
    }
}
