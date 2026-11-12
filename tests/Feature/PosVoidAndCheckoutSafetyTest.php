<?php

namespace Tests\Feature;

use App\Models\PosTransaction;
use App\Models\PosVoidLog;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use Tests\TestCase;

/**
 * Memastikan:
 * - void POS hanya mengembalikan stok SATU kali walaupun ada request bersamaan,
 * - error sistem tidak membocorkan detail teknis ke browser,
 * - produk nonaktif tidak bisa dijual.
 */
class PosVoidAndCheckoutSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected User $cs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->cs = User::where('email', 'cs@fitcore.com')->first();
    }

    public function test_stock_is_not_restored_twice_when_another_request_voided_the_transaction_first(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $transaction = $this->createCompletedTransactionWith($product, quantity: 3);

        // Simulasi 2 request bersamaan:
        // tepat setelah Laravel memuat transaksi dari URL (status masih COMPLETED),
        // "request lain" sudah lebih dulu mengubah statusnya menjadi VOID di database.
        $alreadySimulated = false;
        PosTransaction::retrieved(function (PosTransaction $retrieved) use (&$alreadySimulated) {
            if ($alreadySimulated) {
                return;
            }
            $alreadySimulated = true;
            DB::table('pos_transactions')->where('id', $retrieved->id)->update(['status' => 'VOID']);
        });

        $response = $this->actingAs($this->cs)->postJson(route('pos.void', $transaction), $this->voidPayload());

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Transaksi ini sudah dibatalkan (VOID) sebelumnya.',
        ]);
        $this->assertEquals(10, $product->fresh()->stock, 'Stok tidak boleh dikembalikan lagi.');
        $this->assertDatabaseMissing('pos_void_logs', ['pos_transaction_id' => $transaction->id]);
    }

    public function test_void_system_error_returns_generic_message_and_rolls_back(): void
    {
        Exceptions::fake();

        $product = Product::factory()->create(['stock' => 10]);
        $transaction = $this->createCompletedTransactionWith($product, quantity: 2);

        // Paksa error di tengah proses void (saat menyimpan log audit).
        PosVoidLog::creating(fn () => throw new RuntimeException('SQLSTATE[42S02] detail rahasia server'));

        $response = $this->actingAs($this->cs)->postJson(route('pos.void', $transaction), $this->voidPayload());

        $response->assertStatus(500);
        $this->assertStringNotContainsString('SQLSTATE', $response->json('message'));
        Exceptions::assertReported(RuntimeException::class);

        // Semua perubahan dibatalkan (rollback): stok dan status tetap seperti semula.
        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertEquals('COMPLETED', $transaction->fresh()->status);
    }

    public function test_checkout_system_error_returns_generic_message(): void
    {
        Exceptions::fake();

        $product = Product::factory()->create(['stock' => 10]);

        PosTransaction::creating(fn () => throw new RuntimeException('SQLSTATE[23000] detail rahasia server'));

        $response = $this->actingAs($this->cs)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'qris',
        ]);

        $response->assertStatus(500);
        $this->assertStringNotContainsString('SQLSTATE', $response->json('message'));
        Exceptions::assertReported(RuntimeException::class);
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_inactive_product_cannot_be_sold(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'is_active' => false]);

        $response = $this->actingAs($this->cs)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'qris',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('tidak aktif', $response->json('message'));
        $this->assertEquals(10, $product->fresh()->stock);
    }

    /**
     * Buat transaksi COMPLETED berisi 1 produk.
     * Stok produk TIDAK dikurangi di sini, supaya test mudah mengecek apakah stok bertambah.
     */
    private function createCompletedTransactionWith(Product $product, int $quantity): PosTransaction
    {
        $subtotal = $product->selling_price * $quantity;

        $transaction = PosTransaction::create([
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => $this->cs->id,
            'subtotal' => $subtotal,
            'total_amount' => $subtotal,
            'payment_method' => 'cash',
            'status' => 'COMPLETED',
        ]);

        $transaction->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'cost_price' => $product->cost_price,
            'unit_price' => $product->selling_price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ]);

        return $transaction;
    }

    /**
     * Data form void dengan kredensial supervisor seed yang benar (PIN 123456).
     *
     * @return array<string, string>
     */
    private function voidPayload(): array
    {
        return [
            'supervisor_email' => 'supervisor@fitcore.com',
            'supervisor_pin' => '123456',
            'void_reason' => 'Customer batalkan pembelian',
        ];
    }
}
