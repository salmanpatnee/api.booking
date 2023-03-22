<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            
            $product = Product::where("ref_id", $row['ref_id'])->first();            

            if ($product) {
                // dd($product, $row);    
                DB::table('opharma_products_inventory')
                    ->where('product_id', $product->ref_id)
                    ->update([
                        'default_purchase_price' => $row['unit_cost'],
                        'default_selling_price' => $row['unit_price'],
                        // 'discount_rate_cash' => $row["discount_on_cash"],
                        // 'discount_rate_card' => $row["discount_on_card"],
                        // "discount_rate_shipment" => $row["discount_on_delivery"],
                        'uom_of_boxes' => $row['uom_of_boxes']
                    ]);

                    $product->discount_rate_cash = $row['discount_on_cash'];
                    $product->discount_rate_card = $row['discount_on_card'];
                    $product->discount_rate_shipment = $row['discount_on_delivery'];
                    $product->uom_of_boxes = $row['uom_of_boxes'];
                    $product->save();
            }

        }
    }

    public function chunkSize(): int
    {
        return 300;
    }
}
