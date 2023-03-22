<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Softin',
                'category_id' => 1,
                'brand_id' => 1,
                'quantity' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Panadol',
                'category_id' => 2,
                'brand_id' => 2,
                'quantity' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Metodine',
                'category_id' => 1,
                'brand_id' => 3,
                'quantity' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        // foreach ($data as $product) {
            foreach (range(1, 300) as $i) {

         
            $defaultPurchasePrice = fake()->numberBetween(5, 100);
            $defaultSalePrice = $defaultPurchasePrice + (($defaultPurchasePrice * 14) / 100);
            $quantityThreshold = fake()->numberBetween(10, 50);
            Product::create([
                'name' => 'product ' . $i,
                'category_id' => 2,
                // 'brand_id' => $product['brand_id'],
                'quantity' => fake()->numberBetween(2, 5),
                'barcode' => fake()->unique()->ean8,
                // 'sku' => fake()->unique()->randomNumber(),
                'quantity_sold' => 0,
                'quantity_threshold' => $quantityThreshold,
                'default_purchase_price' => $defaultPurchasePrice,
                'default_selling_price' => $defaultSalePrice,
                'discount_rate_cash' => 10,
                'discount_rate_card' => 7,
                'discount_rate_shipment' => 5,
                'created_by' => 1,
            ]);
        }
        // }
    }
}
