<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate  = request('paginate', 10);
        $term      = request('search', '');
        $sortOrder = request('sortOrder', 'desc');
        $orderBy   = request('orderBy', 'created_at');

        $products = Product::search($term)
        ->select('id', 'name', 'category_id', 'discount_rate_cash', 'discount_rate_card', 'discount_rate_shipment', 'is_locked')
        ->orderBy($orderBy, $sortOrder)
            ->paginate($paginate);
        return response()->json(['data' => $products]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.discount_rate_cash' => 'nullable|numeric',
            'products.*.discount_rate_card' => 'nullable|numeric',
            'products.*.discount_rate_shipment' => 'nullable|numeric',
            'products.*.is_locked' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        foreach ($data['products'] as $item) {
            $product = Product::find($item['id']);
            $product->discount_rate_cash = $item['discount_rate_cash'] ?? null;
            $product->discount_rate_card = $item['discount_rate_card'] ?? null;
            $product->discount_rate_shipment = $item['discount_rate_shipment'] ?? null;
            $product->is_locked = $item['is_locked'] ?? null;
            
            $product->save();
        }

        DB::commit();

        return response()->json([
            'message'   => 'Discounts applied successfully.',
            'data'      => [],
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
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
