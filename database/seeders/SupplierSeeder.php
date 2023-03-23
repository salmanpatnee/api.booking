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
            'email' => null,
            'phone' => null,
            'trader' => '',
            'balance' => 0,
            'account_type' => 'supplier',
        ]);
    }
}
