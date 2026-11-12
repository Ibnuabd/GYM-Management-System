<?php

namespace Database\Factories;

use App\Models\PosTransaction;
use App\Models\PosVoidLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosVoidLog>
 */
class PosVoidLogFactory extends Factory
{
    protected $model = PosVoidLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transaction = PosTransaction::factory()->create(['status' => 'VOID']);

        return [
            'pos_transaction_id' => $transaction->id,
            'invoice_number' => $transaction->invoice_number,
            'cashier_user_id' => $transaction->user_id,
            'supervisor_user_id' => User::factory(),
            'total_amount' => $transaction->total_amount,
            'items_summary' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Sample Product',
                    'quantity' => 1,
                    'unit_price' => 25000,
                ],
            ],
            'void_reason' => fake()->sentence(),
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ];
    }
}
