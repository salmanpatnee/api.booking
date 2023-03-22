<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class StockTransferController extends Controller
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
        $data = $request->validate([
            'date' => 'required|date',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'description' => 'nullalbe',
            'stock_transfer_details' => 'required|array',
            'stock_transfer_details.*.product_id' => 'required|exists:products,id',
            'stock_transfer_details.*.quantity' => 'required|numeric|min:1',
        ]);

        $userId = auth()->user()->id;

        $stockTransferDetailData = [];
        $productsCount = 0;
        $amount = 0;

        $message = null;
        $errors = [];

        foreach ($request->stock_transfer_details as $i => $requestStockTransferDetail) {
            $product = Product::find($requestStockTransferDetail['product_id']);

            /* quantity validation */
            if ($requestStockTransferDetail['quantity'] > $product->quantity) {
                $errors[] = ['stock_transfer_details.' . $i . '.quantity' => ["The selected stock_transfer_details.{$i}.quantity can not be greater than {$product->quantity}"]];
                if (!$message) {
                    $message = "The stock_transfer_details.{$i}.quantity can not be greater than {$product->quantity}.";
                }
            }

            $stockTransferDetailAmount = $requestStockTransferDetail['quantity'] * $requestStockTransferDetail['price'];

            $productsCount += 1;
            $amount += $stockTransferDetailAmount;

            $stockTransferDetailData[$i] = [
                'product_id' => $requestStockTransferDetail['product_id'],
                'quantity_boxes' => $requestStockTransferDetail['quantity_boxes'],
                'units_in_box' => $requestStockTransferDetail['units_in_box'],
                'price' => $requestStockTransferDetail['price'],
                'quantity' => $requestStockTransferDetail['quantity'],
                'amount' => $stockTransferDetailAmount,
                'product' => $product,
            ];
        }
        unset($i);
        unset($product);

        if ($message) {
            return response()->json([
                "message" => $message,
                "errors" => $errors
            ], 422);
        }

        $inventoryService = new InventoryService();

        DB::beginTransaction();
        $stockTransfer = StockTransfer::create([
            "date" => $data['date'],
            "from_location_id" => $data['from_location_id'],
            "to_location_id" => $data['to_location_id'],
            "description" => $data['description'],
            "products_count" => $productsCount,
            "amount" => $amount,
            "status" => StockTransfer::STOCK_TRANSFER_STATUS_TRANSFERRED
        ]);

        foreach ($stockTransferDetailData as $stockTransferDetailEntry) {
            $stockTransferDetailEntry['stock_transfer_id'] = $stockTransfer->id;

            if ($stockTransfer->status == StockTransfer::STOCK_TRANSFER_STATUS_TRANSFERRED) {
                $inventoryService->moveInventory(
                    referenceId: $stockTransfer->id,
                    date: $stockTransfer->date,
                    fromLocationId: $stockTransfer->from_location_id,
                    toLocationId: $stockTransfer->to_location_id,
                    productId: $stockTransferDetailEntry['product_id'],
                    purchasedPrice: $stockTransferDetailEntry['price'],
                    transferredQuantity: $stockTransferDetailEntry['quantity']
                );
                $inventoryService->updateProductQuantityOnSale(
                    $stockTransferDetailEntry['product'],
                    $stockTransferDetailEntry['quantity']
                );
            }

            $stockTransferDetail = StockTransferDetail::create($stockTransferDetailEntry);
        }


        DB::commit();

        return response()->json([
            'message'   => 'Stock transfer created successfully.',
            'data'      => $stockTransfer,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\Response
     */
    public function show(StockTransfer $stockTransfer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StockTransfer $stockTransfer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\Response
     */
    public function destroy(StockTransfer $stockTransfer)
    {
        //
    }
}
