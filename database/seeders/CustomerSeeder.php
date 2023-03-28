<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /* Customer without balance */
        Account::create([
            'name' => 'Walk-in Customer',
            'email' => "walkincustomer@cvstecnologies.com",
            'phone' => 03000742762,
            'trader' => 'Acme',
            'balance' => 0,
            'account_type' => 'customer',
        ]);
    }
}
