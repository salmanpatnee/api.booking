<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $purchae_price = fake()->numberBetween(5, 100);
        $selling_price = $purchae_price + (($purchae_price * 14) / 100);

        return [
            'category_id' => fake()->numberBetween(1, 2),
            'name' => fake()->word(),
            'quantity' => 0,
            'barcode' => fake()->unique()->ean8,
            // 'sku' => fake()->unique()->randomNumber(),
            'uom_of_boxes' => fake()->numberBetween(1, 5),
            'uom_of_strips' => fake()->numberBetween(6, 30),
            'quantity_threshold' => 5,
            'default_purchase_price' => $purchae_price,
            'default_selling_price' => $selling_price,
            'discount_rate_cash' => 10,
            'discount_rate_card' => 7,
            'discount_rate_shipment' => 5,
            'created_by' => 1,
        ];
    }
}
