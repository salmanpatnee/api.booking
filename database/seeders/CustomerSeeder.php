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
            'trade_name' => 'Acme',
            'phone' => '01709542255',
            'email' => "walkincustomer@icrack.co.uk",
            'address' => "113 Duke St, Merseyside, WA10 2JG, United Kingdom",
            'balance' => 0,
            'account_type' => 'customer',
        ]);
    }
}
