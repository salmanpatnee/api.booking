<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Account::create([
            'name' => 'Dummy Supplier',
            'trade_name' => null,
            'phone' => null,
            'email' => null,
            'address' => null,
            'balance' => 0,
            'account_type' => 'supplier',
        ]);
    }
}
