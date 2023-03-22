<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseDetailUomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::select("id", "uom_of_boxes")->chunk(500, function ($products) {
            foreach ($products as $product) {
                PurchaseDetail::where('product_id', $product->id)->update([
                    'uom_of_boxes' => $product->uom_of_boxes
                ]);
            }
        });
    }
}
