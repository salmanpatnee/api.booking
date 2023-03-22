<?php

namespace App\Http\Controllers;

use App\Models\AdjustmentEntry;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdjustmentEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'date' => 'required|date',
            'product_id' => 'required|exists:products,id',
            'available_quantity' => 'required|numeric',
            'physical_count' => 'nullable|numeric|different:available_quantity',

            'quantity_threshold' => 'nullable|numeric',
            'default_purchase_price' => 'required|numeric',
            'default_selling_price' => 'required|numeric',

            'uom_of_boxes' => 'required|numeric',
        ]);

        $attributes['adjusted_quantity'] = $attributes['physical_count'] - $attributes['available_quantity'];

        $inventoryService = new InventoryService();

        DB::beginTransaction();

        $product = Product::find($attributes['product_id']);

        $data = [];
        $message = "Product prices updated successfully";

        if ($attributes['physical_count']) {

            $adjustmentEntry = AdjustmentEntry::create($attributes);


            if ($adjustmentEntry->adjusted_quantity > 0) {
                /* if adjusted is positive add quantity in last entry (physical > stock) */
                $inventoryService->updateInventoryOnPositiveAdjustment($adjustmentEntry->id, $adjustmentEntry->date, $adjustmentEntry->product_id, $adjustmentEntry->adjusted_quantity);
            } else {
                /* if adjusted is negative reduce quantity from first n available entries */
                $inventoryService->updateInventoryOnNegativeAdjustment($adjustmentEntry->id, $adjustmentEntry->date, $adjustmentEntry->product_id, $adjustmentEntry->adjusted_quantity);
            }

            $inventoryService->updateProductQuantityOnAdjustment($product, $adjustmentEntry->adjusted_quantity);

            $data['adjustment_entry'] = $adjustmentEntry;
            $message = "Adjustment entry created successfully.";
        }


        $product->quantity_threshold = $attributes['quantity_threshold'];
        $product->default_purchase_price = $attributes['default_purchase_price'];
        if ($product->default_selling_price != $attributes['default_selling_price']) {
            $oldSalePrice = $product->default_selling_price;
            $product->default_selling_price = $attributes['default_selling_price'];
            $product->default_selling_price_old = $oldSalePrice;
        }

        $product->uom_of_boxes = $attributes['uom_of_boxes'];

        $product->save();

        DB::commit();

        return response()->json([
            'message'   => $message,
            'data'      => $data,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AdjustmentEntry  $adjustmentEntry
     * @return \Illuminate\Http\Response
     */
    public function show(AdjustmentEntry $adjustmentEntry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AdjustmentEntry  $adjustmentEntry
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AdjustmentEntry $adjustmentEntry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AdjustmentEntry  $adjustmentEntry
     * @return \Illuminate\Http\Response
     */
    public function destroy(AdjustmentEntry $adjustmentEntry)
    {
        //
    }
}
