<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'barcode' => '899100110001',
                'name' => 'Pocari Sweat 500ml',
                'category' => 'Minuman',
                'cost_price' => 6000,
                'selling_price' => 9000,
                'stock' => 45,
                'min_stock' => 10,
                'description' => 'Minuman isotonik pengganti ion tubuh yang hilang.',
                'is_active' => true,
            ],
            [
                'barcode' => '899100110002',
                'name' => 'Hydro Coco 330ml',
                'category' => 'Minuman',
                'cost_price' => 7500,
                'selling_price' => 11000,
                'stock' => 30,
                'min_stock' => 8,
                'description' => 'Air kelapa murni tanpa bahan pengawet.',
                'is_active' => true,
            ],
            [
                'barcode' => '899100110003',
                'name' => 'Pristine 8+ 600ml',
                'category' => 'Minuman',
                'cost_price' => 4000,
                'selling_price' => 6000,
                'stock' => 60,
                'min_stock' => 15,
                'description' => 'Air mineral alkali pH 8+ kemasan botol.',
                'is_active' => true,
            ],
            [
                'barcode' => '899200220001',
                'name' => 'ON Gold Standard Whey (1 Sachet)',
                'category' => 'Suplemen',
                'cost_price' => 30000,
                'selling_price' => 45000,
                'stock' => 35,
                'min_stock' => 10,
                'description' => 'Whey protein isolate sachet rasa Double Rich Chocolate.',
                'is_active' => true,
            ],
            [
                'barcode' => '899200220002',
                'name' => 'MuscleTech Platinum Creatine 300g',
                'category' => 'Suplemen',
                'cost_price' => 220000,
                'selling_price' => 320000,
                'stock' => 12,
                'min_stock' => 3,
                'description' => 'Creatine monohydrate murni untuk kekuatan dan massa otot.',
                'is_active' => true,
            ],
            [
                'barcode' => '899200220003',
                'name' => 'BSN Amino X BCAA Green Apple',
                'category' => 'Suplemen',
                'cost_price' => 310000,
                'selling_price' => 450000,
                'stock' => 8,
                'min_stock' => 2,
                'description' => 'Suplemen pemulihan otot instan 10g asam amino mikro.',
                'is_active' => true,
            ],
            [
                'barcode' => '899200220004',
                'name' => 'Cellucor C4 Original Pre-Workout 30 Serv',
                'category' => 'Suplemen',
                'cost_price' => 290000,
                'selling_price' => 410000,
                'stock' => 10,
                'min_stock' => 3,
                'description' => 'Booster energi dan fokus sebelum latihan intensitas tinggi.',
                'is_active' => true,
            ],
            [
                'barcode' => '899300330001',
                'name' => 'FitCore Shaker Bottle 700ml',
                'category' => 'Merchandise',
                'cost_price' => 45000,
                'selling_price' => 75000,
                'stock' => 25,
                'min_stock' => 5,
                'description' => 'Botol shaker BPA-free anti bocor dengan mixer ball kawat.',
                'is_active' => true,
            ],
            [
                'barcode' => '899300330002',
                'name' => 'FitCore Microfiber Towel',
                'category' => 'Merchandise',
                'cost_price' => 25000,
                'selling_price' => 40000,
                'stock' => 20,
                'min_stock' => 5,
                'description' => 'Handuk olahraga cepat kering menyerap keringat.',
                'is_active' => true,
            ],
            [
                'barcode' => '899300330003',
                'name' => 'Leather Lifting Straps Pro',
                'category' => 'Merchandise',
                'cost_price' => 60000,
                'selling_price' => 95000,
                'stock' => 15,
                'min_stock' => 4,
                'description' => 'Strap angkat beban kulit asli untuk deadlift dan pull-ups.',
                'is_active' => true,
            ],
            [
                'barcode' => '899400440001',
                'name' => 'Probar Choco High Protein Bar 50g',
                'category' => 'Makanan Ringan',
                'cost_price' => 15000,
                'selling_price' => 24000,
                'stock' => 40,
                'min_stock' => 10,
                'description' => 'Camilan sehat 20g protein rendah gula.',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['barcode' => $product['barcode']],
                $product
            );
        }
    }
}
