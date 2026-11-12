<?php

namespace Tests\Feature;

use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\PosVoidLog;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PosRetailCashierTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $supervisor;

    protected User $cs;

    protected User $pt;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ProductSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->supervisor = User::where('email', 'supervisor@fitcore.com')->first();
        $this->cs = User::where('email', 'cs@fitcore.com')->first();
        $this->pt = User::where('email', 'pt@fitcore.com')->first();

        $this->product = Product::first();
    }

    public function test_guest_cannot_access_pos_cashier_or_checkout(): void
    {
        $this->get(route('pos'))->assertRedirect(route('login'));
        $this->postJson(route('pos.checkout'), [])->assertStatus(401);
    }

    public function test_user_without_pos_cashier_permission_cannot_access_pos(): void
    {
        // PT does not have pos_cashier permission
        $this->actingAs($this->pt)->get(route('pos'))->assertStatus(403);
        $this->actingAs($this->pt)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ])->assertStatus(403);
    }

    public function test_cashier_can_access_pos_register_interface(): void
    {
        $response = $this->actingAs($this->cs)->get(route('pos'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pos/Index')
            ->has('products')
            ->has('categories')
            ->has('recentTransactions')
            ->has('stats')
            ->has('cashier')
        );
    }

    public function test_search_endpoint_returns_matching_products(): void
    {
        $response = $this->actingAs($this->cs)->getJson(route('pos.search', ['q' => $this->product->barcode]));

        $response->assertStatus(200);
        $response->assertJsonFragment(['barcode' => $this->product->barcode]);
    }

    public function test_checkout_atomically_deducts_inventory_and_creates_transaction(): void
    {
        $initialStock = $this->product->stock;
        $qtyToBuy = 2;

        $response = $this->actingAs($this->cs)->postJson(route('pos.checkout'), [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => $qtyToBuy,
                ],
            ],
            'payment_method' => 'cash',
            'cash_tendered' => $this->product->selling_price * $qtyToBuy + 10000,
            'discount_amount' => 0,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'transaction' => [
                'status' => 'COMPLETED',
                'user_id' => $this->cs->id,
            ],
        ]);

        // Verify product stock was decremented atomically
        $this->assertEquals($initialStock - $qtyToBuy, $this->product->fresh()->stock);

        // Verify database records
        $this->assertDatabaseHas('pos_transactions', [
            'user_id' => $this->cs->id,
            'status' => 'COMPLETED',
        ]);

        $this->assertDatabaseHas('pos_transaction_items', [
            'product_id' => $this->product->id,
            'quantity' => $qtyToBuy,
        ]);
    }

    public function test_checkout_fails_atomically_when_stock_is_insufficient(): void
    {
        $this->product->update(['stock' => 1]);

        $response = $this->actingAs($this->cs)->postJson(route('pos.checkout'), [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5, // Demands 5, but stock is only 1
                ],
            ],
            'payment_method' => 'cash',
            'cash_tendered' => 500000,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        // Stock must remain unchanged (atomic rollback)
        $this->assertEquals(1, $this->product->fresh()->stock);
    }

    public function test_direct_cashier_deletion_is_forbidden_on_completed_orders(): void
    {
        $transaction = PosTransaction::create([
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => $this->cs->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($this->cs)->deleteJson(route('pos.destroy', $transaction->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('pos_transactions', [
            'id' => $transaction->id,
            'status' => 'COMPLETED',
        ]);
    }

    public function test_void_fails_with_invalid_supervisor_credentials(): void
    {
        $transaction = PosTransaction::create([
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => $this->cs->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($this->cs)->postJson(route('pos.void', $transaction->id), [
            'supervisor_email' => 'supervisor@fitcore.com',
            'supervisor_pin' => 'WRONG_PIN_9999',
            'void_reason' => 'Customer batalkan pembelian',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $this->assertEquals('COMPLETED', $transaction->fresh()->status);
    }

    public function test_void_fails_when_authorizer_lacks_pos_void_permission(): void
    {
        $transaction = PosTransaction::create([
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => $this->cs->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'status' => 'COMPLETED',
        ]);

        // CS frontliner has pos_cashier but lacks pos_void permission
        $response = $this->actingAs($this->cs)->postJson(route('pos.void', $transaction->id), [
            'supervisor_email' => $this->cs->email,
            'supervisor_pin' => 'password',
            'void_reason' => 'Customer batalkan pembelian',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('COMPLETED', $transaction->fresh()->status);
    }

    public function test_authorized_supervisor_void_reverses_stock_and_creates_audit_log(): void
    {
        // 1. Setup product and initial purchase
        $initialStock = 15;
        $this->product->update(['stock' => $initialStock]);

        $qtySold = 4;
        $unitPrice = $this->product->selling_price;
        $total = $unitPrice * $qtySold;

        $transaction = PosTransaction::create([
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => $this->cs->id,
            'subtotal' => $total,
            'total_amount' => $total,
            'payment_method' => 'cash',
            'cash_tendered' => $total,
            'change_due' => 0,
            'status' => 'COMPLETED',
        ]);

        PosTransactionItem::create([
            'pos_transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'cost_price' => $this->product->cost_price,
            'unit_price' => $unitPrice,
            'quantity' => $qtySold,
            'subtotal' => $total,
        ]);

        // Stock was reduced during checkout
        $this->product->decrement('stock', $qtySold);
        $this->assertEquals($initialStock - $qtySold, $this->product->fresh()->stock);

        // 2. Supervisor authorizes Void
        $response = $this->actingAs($this->cs)->postJson(route('pos.void', $transaction->id), [
            'supervisor_email' => $this->supervisor->email,
            'supervisor_pin' => '123456',
            'void_reason' => 'Customer salah memilih varian suplemen dan retur produk.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'transaction' => [
                'status' => 'VOID',
                'voided_by' => [
                    'id' => $this->supervisor->id,
                ],
            ],
        ]);

        // 3. Stock was restored back to 15!
        $this->assertEquals($initialStock, $this->product->fresh()->stock);

        // 4. Transaction status is VOID
        $this->assertEquals('VOID', $transaction->fresh()->status);
        $this->assertEquals($this->supervisor->id, $transaction->fresh()->voided_by);

        // 5. Immutable PosVoidLog was recorded
        $this->assertDatabaseHas('pos_void_logs', [
            'pos_transaction_id' => $transaction->id,
            'supervisor_user_id' => $this->supervisor->id,
            'cashier_user_id' => $this->cs->id,
            'invoice_number' => $transaction->invoice_number,
        ]);
    }

    public function test_cannot_void_already_voided_transaction(): void
    {
        $transaction = PosTransaction::create([
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => $this->cs->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'status' => 'VOID',
            'voided_by' => $this->supervisor->id,
            'void_reason' => 'Sudah pernah dibatalkan',
            'voided_at' => now(),
        ]);

        $response = $this->actingAs($this->cs)->postJson(route('pos.void', $transaction->id), [
            'supervisor_email' => $this->supervisor->email,
            'supervisor_pin' => '123456',
            'void_reason' => 'Mencoba void kedua kali',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_supervisor_can_manage_product_catalog(): void
    {
        // Mengelola katalog produk butuh permission manage_products (admin & supervisor).
        // 1. Create product
        $response = $this->actingAs($this->supervisor)->post(route('products.store'), [
            'barcode' => '8999998887771',
            'name' => 'FitCore Lifting Belt M',
            'category' => 'Merchandise',
            'cost_price' => 150000,
            'selling_price' => 250000,
            'stock' => 10,
            'min_stock' => 2,
            'description' => 'Sabuk angkat beban neoprene',
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['barcode' => '8999998887771']);

        $product = Product::where('barcode', '8999998887771')->first();

        // 2. Update product
        $updateResponse = $this->actingAs($this->supervisor)->put(route('products.update', $product->id), [
            'barcode' => '8999998887771',
            'name' => 'FitCore Lifting Belt L (Updated)',
            'category' => 'Merchandise',
            'cost_price' => 160000,
            'selling_price' => 270000,
            'stock' => 8,
            'min_stock' => 3,
            'is_active' => true,
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $this->assertEquals('FitCore Lifting Belt L (Updated)', $product->fresh()->name);

        // 3. Delete product
        $deleteResponse = $this->actingAs($this->supervisor)->delete(route('products.destroy', $product->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
