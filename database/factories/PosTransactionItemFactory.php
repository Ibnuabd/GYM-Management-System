<?php

namespace Database\Factories;

use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosTransactionItem>
 */
class PosTransactionItemFactory extends Factory
{
    protected $model = PosTransactionItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();
        $qty = fake()->numberBetween(1, 3);
        $subtotal = $product->selling_price * $qty;

        return [
            'pos_transaction_id' => PosTransaction::factory(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'cost_price' => $product->cost_price,
            'unit_price' => $product->selling_price,
            'quantity' => $qty,
            'subtotal' => $subtotal,
        ];
    }
}
