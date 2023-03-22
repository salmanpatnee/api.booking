<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Http\Request;

class LossReportController extends Controller
{
    public function index()
    {
        $data = [];
        $sales = Sale::query()
            ->whereRaw('(net_amount - shipping_charges) < purchase_amount')
            ->with([
                'saleDetails' => function ($q) {
                },
                'productInventoryEntries' => function ($q) {
                    $q->with([
                        'parentProductInventoryEntry' => function ($q) {
                        }
                    ]);
                },
            ])
            ->get();
        foreach ($sales as $i => $sale) {
            $row[$i]['sale_id'] = $sale->id;
            $productInventoryEntries = $sale->productInventoryEntries;
            foreach ($productInventoryEntries as $j => $productInventoryEntry) {
                $saleDetail = SaleDetail::query()
                    ->where('product_id', $productInventoryEntry['product_id'])
                    ->where('sale_id', $sale->id)
                    ->first();
                $product = $saleDetail->product;
                $row[$i][$j]['product_id'] = $product->id;
                $row[$i][$j]['product_name'] = $product->name;
                $row[$i][$j]['original_price'] = $saleDetail->original_price;
                $row[$i][$j]['discount_rate'] = $saleDetail->discount_rate;
                $row[$i][$j]['sale_price'] = $saleDetail->price;
                $row[$i][$j]['quantity'] = $saleDetail->quantity;


                $parentProductInventoryEntry = $productInventoryEntry->parentProductInventoryEntry;
                $row[$i][$j]['purchase_price'] = $parentProductInventoryEntry->purchased_price;
            }
            $data[$i] = $row[$i];
        }

        return response()->json(['data' => $data]);
    }
}
