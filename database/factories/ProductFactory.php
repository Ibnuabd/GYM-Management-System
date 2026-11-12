<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 10000, 200000);
        $selling = $cost * fake()->randomFloat(2, 1.25, 1.6);

        return [
            'barcode' => fake()->unique()->numerify('899##########'),
            'name' => fake()->randomElement([
                'Optimum Nutrition Whey Gold 2 Lbs',
                'MuscleTech Creatine Monohydrate 300g',
                'BSN Amino X BCAA Green Apple',
                'Hydro Flask Gym Bottle 1L',
                'FitCore Microfiber Workout Towel',
                'Pocari Sweat 500ml',
                'Hydro Coco 330ml',
                'Probar High Protein Bar Choco',
                'L-Carnitine Liquid Shot 50ml',
                'Grip Pad Fitness Gloves',
            ]),
            'category' => fake()->randomElement(['Suplemen', 'Minuman', 'Merchandise', 'Makanan Ringan']),
            'cost_price' => round($cost, -3),
            'selling_price' => round($selling, -3),
            'stock' => fake()->numberBetween(10, 50),
            'min_stock' => 5,
            'image_url' => null,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
