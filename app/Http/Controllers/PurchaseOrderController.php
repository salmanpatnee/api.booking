<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate = request('paginate', 10);
        $sortOrder     = request('sortOrder', 'desc');
        $orderBy       = request('orderBy', 'created_at');

        $purchaseOrders = PurchaseOrder::select('id', 'date', 'location_id', 'products_count', 'status')
            ->with(['location' => function ($q) {
                $q->select('id', 'name');
            }])
            ->orderBy($orderBy, $sortOrder)->paginate($paginate);
        return response()->json(['data' => $purchaseOrders]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'purchase_order_details' => 'required|array',
            'purchase_order_details.*.product_id' => 'required|exists:products,id',
            'purchase_order_details.*.quantity_demanded' => 'required|numeric|gt:0',
        ]);

        $locationId = 1; //will be dynamic
        $createdBy = auth()->user()->id;

        $purchaseOrderData = $request->only('date');


        $productsCount = 0;
        $quantityCount = 0;

        $purchaseOrderDetailData = [];

        foreach ($request->purchase_order_details as $i => $requestPurchaseOrderDetail) {
            $productsCount += 1;
            $quantityCount += $requestPurchaseOrderDetail['quantity_demanded'];

            $purchaseOrderDetailData[] = [
                'product_id' => $requestPurchaseOrderDetail['product_id'],
                'quantity_demanded' => $requestPurchaseOrderDetail['quantity_demanded'],
            ];
        }

        $purchaseOrderData['location_id'] = $locationId;
        $purchaseOrderData['products_count'] = $productsCount;
        $purchaseOrderData['created_by'] = $createdBy;
        $purchaseOrderData['status'] = 'demanded';


        /* Purchase Order Log needs to be implemented */

        DB::beginTransaction();

        $purchaseOrder = PurchaseOrder::create($purchaseOrderData);

        foreach ($purchaseOrderDetailData as $purchaseOrderDetailEntry) {
            $purchaseOrderDetailEntry['purchase_order_id'] = $purchaseOrder->id;
            $purchaseOrderDetail = PurchaseOrderDetail::create($purchaseOrderDetailEntry);
        }

        DB::commit();

        return response()->json([
            'message'   => 'Purchase Order created successfully.',
            'data'      => $purchaseOrder,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseOrder $purchaseOrder, Request $request)
    {
        if ($request->for == 'purchases.create') {
            $purchaseOrder->load(['purchaseOrderDetails' => function ($q) {
                $q->select('id', 'product_id', 'purchase_order_id', 'quantity_demanded', 'quantity_received')
                    ->with(['product' => function ($q) {
                        $q->select('id', 'name', 'quantity', 'quantity_threshold', 'default_purchase_price', 'default_selling_price', 'is_active');
                    }]);
            }]);
            return response()->json(['data' => $purchaseOrder]);
        }
        $purchaseOrder->load([
            'createdBy' => function ($q) {
                $q->select('id', 'name');
            },
            'location' => function ($q) {
                $q->select('id', 'name');
            },
            'purchaseOrderDetails' => function ($q) {
                $q->select('id', 'product_id', 'purchase_order_id', 'quantity_demanded', 'quantity_received')
                    ->with(['product' => function ($q) {
                        $q->select('id', 'name');
                    }]);
            }
        ]);
        return response()->json(['data' => $purchaseOrder]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {        
        $purchaseOrder->load('purchaseOrderDetails');
        $productsCount = $purchaseOrder->purchaseOrderDetails->count();
        $request->validate([
            'purchase_order_details' => 'required|array|size:'.$productsCount,
            'purchase_order_details.*.product_id' => 'required|exists:products,id',
            'purchase_order_details.*.quantity_demanded' => 'required|numeric|gt:0',
            'purchase_order_details.*.quantity_received' => 'required|numeric',
            'purchase_order_details.*.remarks' => 'nullable|max:1024',
            'purchase_order_details.*.is_checked' => 'required|accepted',
        ]);

        // $purchaseOrderProductIds = $purchaseOrder->purchaseOrderDetails->pluck('product_id')->toArray();
        // $requestProductIds = Arr::pluck($request->purchase_order_details, 'product_id');

        // if ($purchaseOrderProductIds != $requestProductIds) {
        //     $message = "The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}";
        // }

        DB::beginTransaction();
        foreach ($request->purchase_order_details as $requestPurchaseOrderDetail) {
            $purchaseOrderDetail = $purchaseOrder->purchaseOrderDetails->where('product_id', $requestPurchaseOrderDetail['product_id'])->first();
            $purchaseOrderDetail->quantity_received = $requestPurchaseOrderDetail['quantity_received'];
            $purchaseOrderDetail->save();
        }

        $purchaseOrder->status = "checked";
        $purchaseOrder->save();

        DB::commit();

        return response()->json([
            'message'   => 'Purchase Order marked as checked successfully.',
            'data'      => $purchaseOrder,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        //
    }
}
