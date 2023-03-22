<?php

namespace Database\Seeders;

use App\Models\Purchase;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseDetailBoxSalePriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $purchases = Purchase::query()
            ->select('id')
            ->where('id', '!=', 1)
            ->orderBy('id', 'ASC')
            ->get();
        foreach ($purchases as $purchase) {
            $purchaseDetails = $purchase->purchaseDetails;
            foreach ($purchaseDetails as $purchaseDetail) {
                $purchaseDetail->box_sale_price = $purchaseDetail->units_in_box * $purchaseDetail->sale_price;
                $purchaseDetail->save();

                $product = $purchaseDetail->product;
                $product->default_box_sale_price = $purchaseDetail->box_sale_price;
                $product->save();
            }
        }
    }
}
