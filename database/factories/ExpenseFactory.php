<?php

namespace Database\Factories;

use App\Models\AccountHead;
use App\Models\ExpenseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'expense_type_id'   => function () {
                return ExpenseType::factory()->create()->id;
            },
            'payment_method_id' => function () {
                return AccountHead::factory()->create()->id;
            },
            'date'              => now(),
            'description'       => fake()->sentence,
            'amount'            => fake()->numberBetween(150, 350)
        ];
    }
}
