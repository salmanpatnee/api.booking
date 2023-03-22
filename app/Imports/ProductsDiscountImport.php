<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsDiscountImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $product = Product::find($row['id']);
            

            if ($product) {
                if(!empty($row['discount_on_cash'])) {
                    $product->discount_rate_cash = $row['discount_on_cash'];
                }
                if(!empty($row['discount_on_card'])) {
                    $product->discount_rate_card = $row['discount_on_card'];
                }
                if(!empty($row['discount_on_delivery'])) {
                    $product->discount_rate_shipment = $row['discount_on_delivery'];
                }
                $product->save();
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
