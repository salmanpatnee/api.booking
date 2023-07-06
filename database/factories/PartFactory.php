<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Part>
 */
class PartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'category_id' => 1,
            'name' => fake()->unique()->word(),
            'code' => fake()->unique()->randomNumber(),
            'cost' => fake()->numberBetween(10, 100),
            'price' => fake()->numberBetween(110, 300),
            'quantity' => fake()->numberBetween(10, 100),
        ];
    }
}
