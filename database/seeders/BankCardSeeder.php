<?php

namespace Database\Seeders;

use App\Models\BankCard;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                "bank_id" => 1,
                "name" => "Classic Mastercard Debit Card By Meezan Bank"
            ],
            [
                "bank_id" => 2,
                "name" => "Faysal Islami Priority Mastercard Debit Card By Faysal Bank Limited"
            ],
            [
                "bank_id" => 1,
                "name" => "Platinum Mastercard Debit Card By Meezan Bank"
            ],
            [
                "bank_id" => 2,
                "name" => "Faysal Islami Mastercard Gold Debit Card By Faysal Bank Limited"
            ]
        ];
        foreach ($data as $bankCard) {
            BankCard::create([
                "bank_id" => $bankCard['bank_id'],
                'name' => $bankCard['name'],
            ]);
        }
    }
}
