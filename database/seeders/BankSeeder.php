<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $banks = ["Meezan Bank", "Faysal Bank"];
        foreach ($banks as $bank) {
            Bank::create([
                'name' => $bank
            ]);
        }
    }
}
