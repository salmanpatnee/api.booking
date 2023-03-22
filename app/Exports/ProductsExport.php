<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    private $fileName = 'products.xlsx';

    protected $products;

    // public function __construct(array $products)
    // {
    //     $this->products = $products;
    // }

    public function headings(): array
    {
        return [
            'Id',
            'Name',
            'Category',
            'Brand',
            'Quantity',
            'Unit Cost',
            'Unit Price',
            'Cash Discount Rate',
            'Card Discount Rate',
            'Delivery Discount Rate',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->category->name,
            $product->brand ? $product->brand->name : "",
            $product->quantity,
            $product->default_purchase_price,
            $product->default_selling_price,
            $product->discount_rate_cash,
            $product->discount_rate_card,
            $product->discount_rate_shipment,

            // $product->section->name,
            // $product->name,
            // $product->email,
            // $product->address,
            // $product->phone_number,
            // $student->created_at->toFormattedDateString(),
            // $student->updated_at->toFormattedDateString()
        ];
    }

    public function query()
    {
        return Product::query()->select('id', 'name');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $products = Product::select(
            "id",
            "name",
            "category_id",
            "brand_id",
            "quantity",
            "default_purchase_price",
            "default_selling_price",
            "discount_rate_cash",
            "discount_rate_card",
            "discount_rate_shipment",
        )
            ->with([
                'category' => function ($q) {
                    $q->select('id', 'name');
                },
                'brand' => function ($q) {
                    $q->select('id', 'name');
                },
            ])
            ->get();
        return $products;
        // return Product::all();
    }
}
