<?php

namespace Database\Seeders;

use App\Models\AccountHead;
use App\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $expense_types = [
            'Branch Consumable',
            'Order Delivery',
            'Rickshaw Rent',
            'Foodpanda',
            'Food',
            'Salary',
            'Entertainment',
            'Fuel'
        ];

        foreach ($expense_types as $expense_type) {
            ExpenseType::create([
                'name' => $expense_type
            ]);
        }
    }
}
