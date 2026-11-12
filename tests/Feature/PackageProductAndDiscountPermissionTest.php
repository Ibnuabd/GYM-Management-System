<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan:
 * - CS hanya boleh MELIHAT paket & produk; mengubahnya hanya admin/supervisor.
 * - Diskon POS di atas batas (default 10%) wajib disetujui supervisor.
 */
class PackageProductAndDiscountPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $cs;

    protected User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->cs = User::where('email', 'cs@fitcore.com')->first();
        $this->supervisor = User::where('email', 'supervisor@fitcore.com')->first();
    }

    // ---------------------------------------------------------------
    // Paket
    // ---------------------------------------------------------------

    public function test_cs_can_view_packages_but_cannot_change_them(): void
    {
        $package = Package::factory()->create(['price' => 350000]);

        $this->actingAs($this->cs)->get(route('packages.index'))->assertOk();

        $this->actingAs($this->cs)->post(route('packages.store'), $this->packagePayload())->assertForbidden();
        $this->actingAs($this->cs)->put(route('packages.update', $package), $this->packagePayload(['price' => 1]))->assertForbidden();
        $this->actingAs($this->cs)->delete(route('packages.destroy', $package))->assertForbidden();

        $this->assertEquals(350000, (float) $package->fresh()->price, 'Harga paket tidak boleh berubah.');
    }

    public function test_supervisor_can_create_and_update_packages(): void
    {
        $this->actingAs($this->supervisor)->post(route('packages.store'), $this->packagePayload())
            ->assertRedirect(route('packages.index'));

        $package = Package::where('name', 'Paket Uji Izin')->firstOrFail();

        $this->actingAs($this->supervisor)->put(route('packages.update', $package), $this->packagePayload(['price' => 450000]))
            ->assertRedirect(route('packages.index'));

        $this->assertEquals(450000, (float) $package->fresh()->price);
    }

    // ---------------------------------------------------------------
    // Produk
    // ---------------------------------------------------------------

    public function test_cs_can_view_and_search_products_but_cannot_change_them(): void
    {
        $product = Product::factory()->create(['stock' => 20, 'selling_price' => 50000]);

        $this->actingAs($this->cs)->get(route('products.index'))->assertOk();
        $this->actingAs($this->cs)->getJson(route('pos.search', ['q' => $product->name]))->assertOk();

        $this->actingAs($this->cs)->post(route('products.store'), $this->productPayload())->assertForbidden();
        $this->actingAs($this->cs)->put(route('products.update', $product), $this->productPayload(['barcode' => $product->barcode, 'stock' => 999]))->assertForbidden();
        $this->actingAs($this->cs)->delete(route('products.destroy', $product))->assertForbidden();

        $this->assertEquals(20, $product->fresh()->stock, 'Stok tidak boleh diubah oleh kasir.');
    }

    // ---------------------------------------------------------------
    // Diskon POS
    // ---------------------------------------------------------------

    public function test_discount_within_limit_does_not_need_supervisor(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'selling_price' => 100000]);

        // Diskon 10.000 dari subtotal 100.000 = 10% (tepat di batas, masih boleh).
        $response = $this->actingAs($this->cs)->postJson(route('pos.checkout'), $this->checkoutPayload($product, discount: 10000));

        $response->assertCreated();
        $this->assertEquals(9, $product->fresh()->stock);
    }

    public function test_discount_above_limit_without_supervisor_is_rejected_and_stock_is_unchanged(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'selling_price' => 100000]);

        // Diskon 50% tanpa email/PIN supervisor.
        $response = $this->actingAs($this->cs)->postJson(route('pos.checkout'), $this->checkoutPayload($product, discount: 50000));

        $response->assertForbidden();
        $response->assertJson(['success' => false, 'requires_supervisor_approval' => true]);
        $this->assertEquals(10, $product->fresh()->stock, 'Stok harus dikembalikan (rollback).');
        $this->assertEquals(0, PosTransaction::count());
    }

    public function test_discount_above_limit_with_wrong_pin_is_rejected(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'selling_price' => 100000]);

        $response = $this->actingAs($this->cs)->postJson(route('pos.checkout'), $this->checkoutPayload($product, discount: 50000, supervisorPin: '000000'));

        $response->assertStatus(422);
        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertEquals(0, PosTransaction::count());
    }

    public function test_discount_above_limit_with_valid_supervisor_pin_is_accepted(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'selling_price' => 100000]);

        // PIN supervisor seed yang benar adalah 123456.
        $response = $this->actingAs($this->cs)->postJson(route('pos.checkout'), $this->checkoutPayload($product, discount: 50000, supervisorPin: '123456'));

        $response->assertCreated();
        $this->assertEquals(9, $product->fresh()->stock);
        $this->assertDatabaseHas('pos_transactions', ['discount_amount' => 50000, 'total_amount' => 50000]);
    }

    public function test_discount_limit_can_be_changed_in_config(): void
    {
        config(['pos.max_discount_percent_without_approval' => 60]);
        $product = Product::factory()->create(['stock' => 10, 'selling_price' => 100000]);

        // 50% masih di bawah batas baru 60%, jadi tidak perlu supervisor.
        $this->actingAs($this->cs)->postJson(route('pos.checkout'), $this->checkoutPayload($product, discount: 50000))
            ->assertCreated();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function packagePayload(array $overrides = []): array
    {
        return [
            'name' => 'Paket Uji Izin',
            'type' => 'gym',
            'duration_days' => 30,
            'session_count' => 0,
            'price' => 350000,
            'is_active' => true,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(array $overrides = []): array
    {
        return [
            'barcode' => '8990000000001',
            'name' => 'Produk Uji Izin',
            'category' => 'Suplemen',
            'cost_price' => 10000,
            'selling_price' => 15000,
            'stock' => 5,
            'min_stock' => 1,
            'is_active' => true,
            ...$overrides,
        ];
    }

    /**
     * Data checkout 1 produk (qty 1) dengan pembayaran QRIS.
     *
     * @return array<string, mixed>
     */
    private function checkoutPayload(Product $product, float $discount, ?string $supervisorPin = null): array
    {
        return [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'qris',
            'discount_amount' => $discount,
            'supervisor_email' => $supervisorPin !== null ? 'supervisor@fitcore.com' : null,
            'supervisor_pin' => $supervisorPin,
        ];
    }
}
