<?php

namespace Database\Factories;

use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosTransaction>
 */
class PosTransactionFactory extends Factory
{
    protected $model = PosTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20000, 300000);
        $total = $subtotal;
        $cashTendered = ceil($total / 50000) * 50000;
        $change = $cashTendered - $total;

        return [
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => User::factory(),
            'member_id' => null,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $total,
            'payment_method' => 'cash',
            'cash_tendered' => $cashTendered,
            'change_due' => $change,
            'status' => 'COMPLETED',
            'notes' => null,
        ];
    }
}
