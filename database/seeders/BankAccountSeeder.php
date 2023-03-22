<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $banks = [
            [
                'bank_name' => 'AL FALAH (PHARMA MODEL)',
                'account_name' => 'PHARMA',
                'account_no' => 'PKR123456789',
                'branch' => 'CANT'
            ], 
            [
                'bank_name' => 'HABIB BANK (PHARMA MODEL)',
                'account_name' => 'PHARMA',
                'account_no' => '45151351',
                'branch' => 'CANT'
            ]
        ];

        foreach($banks as $bank){
            BankAccount::create($bank);
        }
        
    }
}
