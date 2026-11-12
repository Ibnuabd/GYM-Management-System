<?php

namespace Database\Seeders;

use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PosTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cashier = User::where('email', 'cs@fitcore.com')->first() ?? User::first();
        if (! $cashier) {
            return;
        }

        $pocari = Product::where('barcode', '899100110001')->first();
        $probar = Product::where('barcode', '899400440001')->first();

        if (! $pocari || ! $probar) {
            return;
        }

        // Transaction 1
        $subtotal1 = ($pocari->selling_price * 2) + $probar->selling_price;
        $trx1 = PosTransaction::create([
            'invoice_number' => 'TRX-'.Carbon::now()->format('Ymd').'-0001',
            'user_id' => $cashier->id,
            'member_id' => null,
            'subtotal' => $subtotal1,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $subtotal1,
            'payment_method' => 'cash',
            'cash_tendered' => 50000,
            'change_due' => 50000 - $subtotal1,
            'status' => 'COMPLETED',
            'notes' => 'Pembelian ritel walk-in',
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        PosTransactionItem::create([
            'pos_transaction_id' => $trx1->id,
            'product_id' => $pocari->id,
            'product_name' => $pocari->name,
            'cost_price' => $pocari->cost_price,
            'unit_price' => $pocari->selling_price,
            'quantity' => 2,
            'subtotal' => $pocari->selling_price * 2,
        ]);

        PosTransactionItem::create([
            'pos_transaction_id' => $trx1->id,
            'product_id' => $probar->id,
            'product_name' => $probar->name,
            'cost_price' => $probar->cost_price,
            'unit_price' => $probar->selling_price,
            'quantity' => 1,
            'subtotal' => $probar->selling_price,
        ]);
    }
}
