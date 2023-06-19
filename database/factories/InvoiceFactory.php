<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $vat = rand(0, 20);
        $total = rand(10, 100);
        $netTotal = ($total * $vat / 100) + $total;

        return [
            'invoice_no' => fake()->unique()->text(10), 
            'client_name' => fake()->name(), 
            'client_email' => fake()->safeEmail(), 
            'description' => fake()->text(50), 
            'vat' => $vat, 
            'total' => $total, 
            'net_total' => $netTotal, 
            'date' => now() 
        ];
    }
}
