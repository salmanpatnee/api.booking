<?php

namespace Database\Seeders;

use App\Models\AccountHead;
use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
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
                'name' => 'Cash',
                'account_head_id' => AccountHead::CASH_ID,
            ],
            [
                'name' => 'Bank',
                'account_head_id' => AccountHead::BANK_ID,
            ]
        ];
        foreach ($data as $key => $entry) {
            $paymentMethod = PaymentMethod::create([
                'name' => $entry['name'],
                'account_head_id' => $entry['account_head_id']
            ]);
        }
    }
}
