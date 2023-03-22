<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportOldUomController extends Controller
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
            
            ->chunk(200, function ($products) {
                foreach ($products as $product) {
                    
                    $lastPurchaseDetail = DB::connection('mysql2')->table("product_purchase_details")
                        
                        ->where('product_id', $product->ref_id)
                        ->orderBy('id', 'desc')                        
                        ->first();

                    
                    if(!empty($lastPurchaseDetail->uom)) {
                        $product->uom_of_boxes = $lastPurchaseDetail->uom;
                        $product->save();
                    }
                }
            });
    }
}
