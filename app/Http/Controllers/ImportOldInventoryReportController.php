<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportOldInventoryReportController extends Controller
{
    public function index()
    {
        $deletedProducts = [1355698764, "CGYYGY", "271522160", "8587591147"];

        $negativeProducts = [6221155046581];

        // $adjustedProducts = [3323802550, "CGHCGH", "HNRVJG5Y", "5756546547565"];

        Product::select(
            "id",
            "name",
            "category_id",
            "brand_id",
            "quantity",
            "default_purchase_price",
            "default_selling_price",
            "ref_id"
        )
            ->with([
                'category' => function ($q) {
                    $q->select('id', 'name');
                },
                'brand' => function ($q) {
                    $q->select('id', 'name');
                },
            ])
            ->whereNotIn('ref_id', $deletedProducts)
            // ->whereNotIn('ref_id', $adjustedProducts)
            ->whereNotIn('ref_id', $negativeProducts)
            // ->where('id', '>', 9108)
            ->chunk(200, function ($products) {
                foreach ($products as $product) {
                    
                    $purchasedQuantity = DB::connection('mysql2')->table("product_purchase_details")
                        ->where('product_id', $product->ref_id)
                        ->sum("quantity");
                    $adjustedQuantity = DB::connection('mysql2')->table("stock_adjustment")
                        ->where('product_id', $product->ref_id)
                        ->sum("quantity");
                    $soldQuantity = DB::connection('mysql2')->table("invoice_details")
                        ->leftJoin('invoice', 'invoice.invoice_id', '=', 'invoice_details.invoice_id')
                        ->whereNull('invoice.deleted_at')
                        ->where('product_id', $product->ref_id)
                        ->sum("quantity");
                    $availableQuantity = (int)$purchasedQuantity + (int)$adjustedQuantity - (int)$soldQuantity;

                    if($availableQuantity < 0) dd($purchasedQuantity, $adjustedQuantity, $soldQuantity, $availableQuantity, $product);

                    // dd($purchasedQuantity, $adjustedQuantity, $soldQuantity, $availableQuantity, $product);
                    DB::table('opharma_products_inventory')
                        ->insert([
                            "product_id" => $product->ref_id,
                            "purchased_quantity" => $purchasedQuantity,
                            "sold_quantity" => $soldQuantity,
                            "adjusted_quantity" => $adjustedQuantity,
                            "available_quantity" => $availableQuantity
                        ]);
                }
            });
    }
}
