<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductInventoryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductStockDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Product $product)
    {
        // return $product;
        $productStockDetails = ProductInventoryEntry::select("location_id", DB::raw("SUM(available_quantity) as total_available_quantity"))
            ->where('product_id', $product->id)
            ->whereNotNull('available_quantity')
            ->where('available_quantity', '>', 0)
            ->groupBy('location_id')
            ->with(
                [
                    "location" => function ($q) {
                        $q->select("id", "name");
                    }
                ]
            )
            ->get();
        $product->stock_details = $productStockDetails;
        return response()->json(['data' => $product]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Product $product, Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
