<?php

namespace Database\Seeders;

use App\Models\BankOffer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BankOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Schema::disableForeignKeyConstraints();
        // DB::table('bank_card_bank_offer')->truncate();
        // DB::table('bank_offers')->truncate();
        // Schema::enableForeignKeyConstraints();
        $data = [
            [
                "bank_id" => 1,
                'start_date' => '2022-01-01',
                "end_date" => "2022-12-31",
                "amount_limit" => 2000,
                "orders_limit" => 2,
                "discount_type" => "percentage",
                "discount_amount" => null,
                "discount_percentage" => 10,
                "created_by" => 1,
                "bank_cards" => [1],
            ],
            [
                "bank_id" => 2,
                'start_date' => '2022-01-01',
                "end_date" => "2022-06-30",
                "amount_limit" => 1000,
                "orders_limit" => 10,
                "discount_type" => "percentage",
                "discount_amount" => null,
                "discount_percentage" => 5,
                "created_by" => 1,
                "bank_cards" => [2, 4],
            ],
            [
                "bank_id" => 1,
                'start_date' => '2022-01-01',
                "end_date" => "2022-12-31",
                "amount_limit" => null,
                "orders_limit" => 10,
                "discount_type" => "fixed",
                "discount_amount" => 2000,
                "discount_percentage" => null,
                "created_by" => 1,
                "bank_cards" => [1, 3],
            ],
        ];
        foreach ($data as $bankOffer) {
            DB::beginTransaction();
            $model = BankOffer::create([
                "bank_id" => $bankOffer['bank_id'],
                "start_date" => $bankOffer['start_date'],
                'end_date' => $bankOffer['end_date'],
                "amount_limit" => $bankOffer['amount_limit'],
                'orders_limit' => $bankOffer['orders_limit'],
                "discount_type" => $bankOffer['discount_type'],
                'discount_amount' => $bankOffer['discount_amount'],
                "discount_percentage" => $bankOffer['discount_percentage'],
                'created_by' => $bankOffer['created_by']
            ]);

            $model->bankCards()->attach($bankOffer['bank_cards']);
            DB::commit();
        }
    }
}
