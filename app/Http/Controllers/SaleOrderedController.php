<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SaleOrderedController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $salesOrdered, Request $request)
    {
        $sale = $salesOrdered;
        if ($sale->status != Sale::SALE_STATUS_ORDERED) {
            return response()->json(["message" => "This sale is already completed.", "errors" => []], 422);
        }

        $sale->load([
            'account' => function ($q) {
                $q->select('id', 'name');
            },
            'saleDetails' => function ($q) {
                // , 'products.name as product_name'
                $q->select('sale_details.id', 'sale_id', 'sale_details.product_id', 'original_price', 'discount_rate', 'sale_details.price', 'sale_details.quantity', 'sale_details.amount')
                    // ->leftJoin('products', 'products.id', '=', 'sales.product_id')
                    ->with(['product' => function ($q) {
                        $q->select('id', 'name', 'quantity as stock', 'default_selling_price', 'default_selling_price_old', 'discount_rate_cash', 'discount_rate_card', 'discount_rate_shipment', 'is_locked');
                    }]);
            }
        ]);

        if ($request->for == 'print') {
            $base64String = "data:image/png;base64, " . base64_encode(QrCode::format('png')->size(100)->generate($sale->id));
            $sale->qr_code = $base64String;
        }

        return response()->json(['data' => $sale]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $sale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sale $sale)
    {
        //
    }
}
