<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductInventoryEntry;
use App\Models\ProductInventoryOutflow;
use App\Models\ProductInventoryOutflowDetail;
use App\Models\ProductInventoryPurchase;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate = request('paginate', 10);
        $term     = request('search', '');
        $sortOrder     = request('sortOrder', 'desc');
        $orderBy       = request('orderBy', 'created_at');

        $purchases = Purchase::search($term)->with(['account' => function ($q) {
            $q->select('id', 'name');
        }]);

        if (!empty($request->start_date))
            $purchases->where('date', '>=', $request->start_date);
        if (!empty($request->end_date))
            $purchases->where('date', '<=', $request->end_date);
        if (!empty($request->status))
            $purchases->where('status', $request->status);

        $purchases = $purchases->orderBy($orderBy, $sortOrder)->paginate($paginate);

        return PurchaseResource::collection($purchases);
        // return response()->json(['data' => $purchases]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePurchaseRequest $request)
    {
        $userId = auth()->user()->id;
        $purchaseData = $request->only([
            'date',

            'gross_amount',
            'net_amount',

            'account_id',
            'reference_number',
            'paid_amount',
            'discount_type',
            'discount_amount',
            'discount_percentage',
            'tax_amount',
            'status',

            'purchase_order_id'
        ]);

        $productsCount = 0;
        $quantityCount = 0;
        $grossAmount = 0;
        $purchaseAmount = 0;
        $purchaseDetailData = [];
        $productInventoryPurchases = []; // used for outflow inventory
        $productInventoryOutflowDetails = []; // used for increasing inventory
        /* product_id=>quantity_sent */
        $purchaseOrderDetailsQuantitySent = []; // used for quantity_sent in purchase_order_details

        $inventoryService = new InventoryService();

        foreach ($request->purchase_details as $requestPurchaseDetail) {
            // $purchaseDetailAmount = $requestPurchaseDetail['quantity'] * $requestPurchaseDetail['price'];
            $purchaseDetailAmount = $requestPurchaseDetail['amount'];

            $productsCount += 1;
            $quantityCount += $requestPurchaseDetail['quantity'];
            $grossAmount += $purchaseDetailAmount;

            $expiryDate = isset($requestPurchaseDetail['expiry_date']) ? $requestPurchaseDetail['expiry_date'] : null;

            $purchaseDetailData[] = $inventoryService->getPurchaseDetailArray(
                $requestPurchaseDetail['product_id'],
                $requestPurchaseDetail['price'],
                $requestPurchaseDetail['quantity'],
                $purchaseDetailAmount,
                $requestPurchaseDetail['quantity_boxes'],
                $requestPurchaseDetail['units_in_box'],
                $requestPurchaseDetail['quantity_strips'],
                $requestPurchaseDetail['units_in_strip'],
                $requestPurchaseDetail['quantity_units'],
                $requestPurchaseDetail['sale_price'],
                $expiryDate,
                totalSalePrice: $requestPurchaseDetail['total_sale_price'],
                uomOfBoxes: $requestPurchaseDetail['units_in_box'],
                boxSalePrice: $requestPurchaseDetail['box_sale_price'],
            );

            $purchaseOrderDetailsQuantitySent[$requestPurchaseDetail['product_id']] = $requestPurchaseDetail['quantity'];
        }

        $purchaseAmount = $grossAmount;

        // calculate discount amount
        if (isset($purchaseData['discount_amount'])) {
            $purchaseAmount = $grossAmount - $purchaseData['discount_amount'];
        }

        // calculate tax amount
        if (isset($purchaseData['tax_amount'])) {
            $purchaseAmount = $purchaseAmount + $purchaseData['tax_amount'];
        }

        $purchaseData['gross_amount'] = $grossAmount;
        $purchaseData['net_amount'] = $purchaseAmount;
        $purchaseData['products_count'] = $productsCount;
        $purchaseData['created_by'] = $userId;
        $purchaseData['payment_status'] = $purchaseAmount == $purchaseData['paid_amount'] ? 'paid' : 'due';

        $journalEntryService = new JournalEntryService();

        DB::beginTransaction();

        $purchase = Purchase::create($purchaseData);



        /* Add payment entry if paid amount > 0 */
        if ($purchase->paid_amount > 0) {
            $payment = Payment::create([
                'date' => $purchase->date,
                'account_id' => $purchase->account_id,
                'purchase_id' => $purchase->id,
                'amount' => $purchase->paid_amount,
                'payment_method_id' => $request->payment_method_id,
                'created_by' => $userId
            ]);

            /* Purchase Debit & Cash Credit */
            $journalEntrySerialNumber = $journalEntryService->getSerialNumber();
            $journalEntryService->recordEntry(
                $journalEntrySerialNumber,
                AccountHead::PURCHASE_ID,
                AccountHead::CASH_ID, //cash will be dynamic
                $purchase->paid_amount,
                0,
                $purchase['date'],
                Payment::class,
                $payment->id
            );
            $journalEntryService->recordEntry(
                $journalEntrySerialNumber,
                AccountHead::CASH_ID, //cash will be dynamic
                AccountHead::PURCHASE_ID,
                0,
                $purchase->paid_amount,
                $purchase['date'],
                Payment::class,
                $payment->id
            );
        }

        /* Add payable entry if amount is not full paid  */
        if ($purchase->paid_amount != $purchaseAmount) {
            /* Purchase Debit & Payable Credit */

            $journalEntrySerialNumber = $journalEntryService->getSerialNumber();
            $balanceAmount = $purchaseAmount - $purchase->paid_amount;

            $journalEntryService->recordEntry(
                $journalEntrySerialNumber,
                AccountHead::PURCHASE_ID,
                AccountHead::ACCOUNT_PAYABLE_ID,
                $balanceAmount,
                0,
                $purchase['date'],
                Purchase::class,
                $purchase->id
            );
            $journalEntryService->recordEntry(
                $journalEntrySerialNumber,
                AccountHead::ACCOUNT_PAYABLE_ID,
                AccountHead::PURCHASE_ID,
                0,
                $balanceAmount,
                $purchase['date'],
                Purchase::class,
                $purchase->id
            );
        }

        foreach ($purchaseDetailData as $purchaseDetailEntry) {
            $purchaseDetailEntry['purchase_id'] = $purchase->id;
            $purchaseDetailEntry['profit_margin'] = $inventoryService->getProfitMargin($purchaseDetailEntry['price'], $purchaseDetailEntry['sale_price']);
            $purchaseDetail = PurchaseDetail::create($purchaseDetailEntry);

            /* purchased inventory for centralized db */
            $productInventoryPurchased = $inventoryService->storeInventoryPurchase(
                $purchaseDetail['product_id'],
                $purchase->id,
                $purchaseDetailEntry['price'],
                $purchaseDetail['quantity'],
                $purchaseDetail['expiry_date']
            );

            /* add in array for outflow */
            $productInventoryPurchases[] = $productInventoryPurchased;
        }

        /* purchased inventory outflow for centralized db */
        $productInventoryOutflow = ProductInventoryOutflow::create([
            'date' => $purchase->date,
            'products_count' => $productsCount,
            'outflow_quantity' => $quantityCount,
        ]);

        /* purchased inventory outflow_details for centralized db */
        foreach ($productInventoryPurchases as $productInventoryPurchase) {
            $productInventoryOutflowDetail = ProductInventoryOutflowDetail::create([
                'product_inventory_outflow_id' => $productInventoryOutflow->id,
                'product_id' => $productInventoryPurchase['product_id'],
                'product_inventory_purchase_id' => $productInventoryPurchase->id,
                'quantity' => $productInventoryPurchase['available_quantity']
            ]);
            $productInventoryOutflowDetails[] = $productInventoryOutflowDetail;

            /* decreased available quantity */
            $productInventoryPurchase->available_quantity -= $productInventoryPurchase['available_quantity'];
            $productInventoryPurchase->save();
        }


        foreach ($productInventoryOutflowDetails as $productInventoryOutflowDetail) {
            $productInventoryEntry = $inventoryService->storeInventoryEntryOnPurchase(
                $productInventoryOutflow['date'],
                $productInventoryOutflowDetail['product_id'],
                $productInventoryOutflowDetail['product_inventory_outflow_id'],
                ProductInventoryOutflow::class,
                $productInventoryOutflowDetail->productInventoryPurchase->purchased_price,
                $productInventoryOutflowDetail['quantity'],
                $productInventoryOutflowDetail->productInventoryPurchase->expiry_date,
            );

            $purchaseDetailAr = Arr::first($purchaseDetailData, function ($value, $key) use ($productInventoryOutflowDetail) {
                return $value['product_id'] == $productInventoryOutflowDetail['product_id'];
            });

            /* Update product quantity */
            // $productInventoryOutflowDetail->productInventoryPurchase->purchased_price,
            $product = Product::find($productInventoryOutflowDetail['product_id']);
            $inventoryService->updateProductPriceQuantityOnPurchase(
                $product,
                $productInventoryEntry->purchased_price,
                $purchaseDetailAr['sale_price'],
                $productInventoryEntry->initial_quantity,
                boxSalePrice: $purchaseDetailAr['box_sale_price']
            );
        }

        $account = Account::find($purchase->account_id);
        $account->purchases_amount += $purchaseAmount;
        $account->purchases_count += 1;

        /* check if account has balance */
        if ($purchase->payment_status == 'due') {
            $account->balance = $account->balance + $purchase->net_amount - $purchase->paid_amount;
        }

        $account->save();

        /* Change Purchase Order status if purchase order is not null */
        if ($purchase->purchase_order_id) {
            $purchaseOrder = PurchaseOrder::find($purchase->purchase_order_id);
            $purchaseOrder->status = "accepted";
            $purchaseOrder->save();
            foreach ($purchaseOrder->purchaseOrderDetails as $purchaseOrderDetail) {
                $purchaseOrderDetail->quantity_sent = $purchaseOrderDetailsQuantitySent[$purchaseOrderDetail->product_id];
                $purchaseOrderDetail->save();
            }
        }

        DB::commit();

        return response()->json([
            'message'   => 'Purchase created successfully.',
            'data'      => $purchase,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase)
    {
        $purchase->load([
            'account' => function ($q) {
                $q->select('id', 'name');
            },
            'payments' => function ($q) {
                $q->select('id', 'date', 'amount', 'purchase_id');
            },
            'purchaseDetails' => function ($q) {
                $q->select('id', 'purchase_id', 'product_id', 'price', 'quantity', 'amount', 'expiry_date', 'sale_price', 'profit_margin', 'units_in_box')
                    ->with(['product' => function ($q) {
                        $q->select('id', 'name', 'uom_of_boxes');
                    }]);
            }
        ]);
        return response()->json(['data' => $purchase]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Purchase $purchase)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(Purchase $purchase)
    {
        //
    }

    public function newImport()
    {
        // $dummySupplierId = 
        // Product::chunk(200, function($products){
        //     foreach($products as $product) {
        //         dd($product);
        //     }
        // });
    }

    public function import(Request $request)
    {
        // , "1801845934", "313535981", "1923805393", "1492939220", "629076118", "1666316526", "1688811559"
        // $ignoringPurchaseIds = ["1787958100"];
        $lastId = null;
        $lastPurchase = Purchase::whereNotNull('ref_id')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPurchase) {
            $lastOldPurchase = DB::connection("mysql2")->table("product_purchase")
                ->select('id', 'purchase_id')
                ->where('purchase_id', $lastPurchase->ref_id)
                ->first();
            $lastId = $lastOldPurchase->id;
        }

        /* ignoring purchases where amount = 0 */
        $query = DB::connection("mysql2")->table("product_purchase")
            ->select("id", "purchase_id", "chalan_no", "supplier_id", "grand_total_amount", "paid_amount", "due_amount", "total_discount", "purchase_date", "purchase_details")
            ->where("grand_total_amount", "!=", 0);

        if ($lastId)
            $query->where("id", ">", $lastId);

        // ->whereNotIn("purchase_id", $ignoringPurchaseIds)

        $query = $query->orderBy('id')->chunk(300, function ($oldPurchases) {
            foreach ($oldPurchases as $oldPurchase) {
                $isError = false;
                $purchase = Purchase::where('ref_id', $oldPurchase->purchase_id)
                    ->first();

                if (!$purchase) {

                    if ($oldPurchase->id == "19272") {
                        DB::table("purchase_migration_logs")->insert([
                            'opharma_purchase_id' => $oldPurchase->purchase_id,
                            'opharma_purchase_detail_id' => null,
                            'opharma_product_id' => null,
                            'remarks' => "NaN in retail price & very big numbers in retail price"
                        ]);
                        continue;
                    }

                    $supplier = Account::where('account_type', 'supplier')
                        ->where('ref_id', $oldPurchase->supplier_id)
                        ->first();

                    if (!$supplier) {
                        dd($supplier);
                    }

                    $purchaseData = [
                        'date' => $oldPurchase->purchase_date,

                        'account_id' => $supplier->id,
                        'reference_number' => $oldPurchase->chalan_no,
                        'paid_amount' => $oldPurchase->paid_amount,
                        'discount_type' => !empty($oldPurchase->total_discount) ? 'fixed' : null,
                        'discount_amount' => $oldPurchase->total_discount,
                        'discount_percentage' => null,
                        'status' => 'received',

                        'remarks' => $oldPurchase->purchase_details,

                        'ref_id' => $oldPurchase->purchase_id,
                    ];

                    $productsCount = 0;
                    $quantityCount = 0;
                    $grossAmount = 0;
                    $purchaseAmount = 0;
                    $purchaseDetailData = [];
                    $productInventoryPurchases = []; // used for outflow inventory
                    $productInventoryOutflowDetails = []; // used for increasing inventory
                    /* product_id=>quantity_sent */
                    $purchaseOrderDetailsQuantitySent = []; // used for quantity_sent in purchase_order_details

                    $inventoryService = new InventoryService();

                    $oldPurchaseDetails = DB::connection("mysql2")->table("product_purchase_details")
                        ->select("id", "purchase_detail_id", "purchase_id", "product_id", "quantity", "rate", "total_amount", "discount", "pack_cost", "uom", "unit_cost", "retail_price", "retail_per_unit_price", "profit_margin", "status")
                        ->where('purchase_id', $oldPurchase->purchase_id)
                        ->get();

                    if (!$oldPurchaseDetails->count()) {
                        DB::table("purchase_migration_logs")->insert([
                            'opharma_purchase_id' => $oldPurchase->purchase_id,
                            'opharma_purchase_detail_id' => null,
                            'opharma_product_id' => null,
                            'remarks' => "No purchase details found"
                        ]);
                        continue;
                    }

                    foreach ($oldPurchaseDetails as $oldPurchaseDetail) {
                        // $purchaseDetailAmount = $requestPurchaseDetail['quantity'] * $requestPurchaseDetail['price'];
                        // $purchaseDetailAmount = $oldPurchaseDetail->unit_cost * $oldPurchaseDetail->quantity;
                        $purchaseDetailAmount = $oldPurchaseDetail->rate;

                        $product = Product::where("ref_id", $oldPurchaseDetail->product_id)->first();
                        if (!$product) {
                            // dd($oldPurchase, $oldPurchaseDetail);
                            DB::table("purchase_migration_logs")->insert([
                                'opharma_purchase_id' => $oldPurchase->purchase_id,
                                'opharma_purchase_detail_id' => $oldPurchaseDetail->purchase_detail_id,
                                'opharma_product_id' => !empty($oldPurchaseDetail->product_id) ? $oldPurchaseDetail->product_id : null,
                                'remarks' => "No product found"
                            ]);
                            $isError = true;
                            continue;
                        }
                        /* sale price empty */
                        if (empty($oldPurchaseDetail->retail_per_unit_price)) {
                            DB::table("purchase_migration_logs")->insert([
                                'opharma_purchase_id' => $oldPurchase->purchase_id,
                                'opharma_purchase_detail_id' => $oldPurchaseDetail->purchase_detail_id,
                                'opharma_product_id' => !empty($oldPurchaseDetail->product_id) ? $oldPurchaseDetail->product_id : null,
                                'remarks' => "Retail price is empty"
                            ]);
                            $isError = true;
                            continue;
                        }

                        if ($oldPurchaseDetail->retail_per_unit_price == "NaN") {
                            DB::table("purchase_migration_logs")->insert([
                                'opharma_purchase_id' => $oldPurchase->purchase_id,
                                'opharma_purchase_detail_id' => $oldPurchaseDetail->purchase_detail_id,
                                'opharma_product_id' => !empty($oldPurchaseDetail->product_id) ? $oldPurchaseDetail->product_id : null,
                                'remarks' => "NaN in retail price"
                            ]);
                            $isError = true;
                            continue;
                        }

                        $productsCount += 1;
                        $quantityCount += $oldPurchaseDetail->quantity;
                        $grossAmount += $purchaseDetailAmount;

                        $expiryDate = null;

                        // dd($oldPurchaseDetail, var_dump($oldPurchaseDetail->unit_cost), var_dump($oldPurchaseDetail->retail_per_unit_price));

                        $purchaseDetailData[] = $inventoryService->getPurchaseDetailArray(
                            $product->id,
                            $oldPurchaseDetail->unit_cost,
                            $oldPurchaseDetail->quantity,
                            $purchaseDetailAmount,
                            null,
                            null,
                            null,
                            null,
                            $oldPurchaseDetail->quantity,
                            $oldPurchaseDetail->retail_per_unit_price,
                            $expiryDate,
                            totalSalePrice: $oldPurchaseDetail->quantity * $oldPurchaseDetail->retail_per_unit_price,
                            uomOfBoxes: $product->uom_of_boxes,
                            boxSalePrice: $oldPurchaseDetail->retail_price,
                        );

                        $purchaseOrderDetailsQuantitySent[$product->id] = $oldPurchaseDetail->quantity;
                    }

                    if ($isError) {
                        continue;
                    }

                    $purchaseAmount = $grossAmount;

                    // calculate discount amount
                    if (isset($purchaseData['discount_amount'])) {
                        $purchaseAmount = $grossAmount - $purchaseData['discount_amount'];
                    }

                    $purchaseData['gross_amount'] = $grossAmount;
                    $purchaseData['net_amount'] = $purchaseAmount;
                    $purchaseData['products_count'] = $productsCount;
                    $purchaseData['created_by'] = 1; //will be dynamic
                    $purchaseData['payment_status'] = $purchaseAmount == $purchaseData['paid_amount'] ? 'paid' : 'due';

                    if ($purchaseData['net_amount'] != (float) $oldPurchase->grand_total_amount) {
                        // dd($purchaseData['net_amount'], (float) $oldPurchase->grand_total_amount, $oldPurchase);
                        DB::table("purchase_migration_logs")->insert([
                            'opharma_purchase_id' => $oldPurchase->purchase_id,
                            'opharma_purchase_detail_id' => null,
                            'opharma_product_id' => null,
                            'remarks' => "Sale amount & products amount are not matching"
                        ]);
                        // continue;
                    }

                    $journalEntryService = new JournalEntryService();

                    DB::beginTransaction();

                    $purchase = Purchase::create($purchaseData);



                    /* Add payment entry if paid amount > 0 */
                    if ($purchase->paid_amount > 0) {
                        $payment = Payment::create([
                            'date' => $purchase->date,
                            'account_id' => $purchase->account_id,
                            'purchase_id' => $purchase->id,
                            'amount' => $purchase->paid_amount,
                            'payment_method_id' => PaymentMethod::CASH_ID,
                            'created_by' => 1 //will be dynamic
                        ]);

                        /* Purchase Debit & Cash Credit */
                        $journalEntrySerialNumber = $journalEntryService->getSerialNumber();
                        $journalEntryService->recordEntry(
                            $journalEntrySerialNumber,
                            AccountHead::PURCHASE_ID,
                            AccountHead::CASH_ID, //cash will be dynamic
                            $purchase->paid_amount,
                            0,
                            $purchase['date'],
                            Payment::class,
                            $payment->id
                        );
                        $journalEntryService->recordEntry(
                            $journalEntrySerialNumber,
                            AccountHead::CASH_ID, //cash will be dynamic
                            AccountHead::PURCHASE_ID,
                            0,
                            $purchase->paid_amount,
                            $purchase['date'],
                            Payment::class,
                            $payment->id
                        );
                    }

                    /* Add payable entry if amount is not full paid  */
                    if ($purchase->paid_amount != $purchaseAmount) {
                        /* Purchase Debit & Payable Credit */

                        $journalEntrySerialNumber = $journalEntryService->getSerialNumber();
                        $balanceAmount = $purchaseAmount - $purchase->paid_amount;

                        $journalEntryService->recordEntry(
                            $journalEntrySerialNumber,
                            AccountHead::PURCHASE_ID,
                            AccountHead::ACCOUNT_PAYABLE_ID,
                            $balanceAmount,
                            0,
                            $purchase['date'],
                            Purchase::class,
                            $purchase->id
                        );
                        $journalEntryService->recordEntry(
                            $journalEntrySerialNumber,
                            AccountHead::ACCOUNT_PAYABLE_ID,
                            AccountHead::PURCHASE_ID,
                            0,
                            $balanceAmount,
                            $purchase['date'],
                            Purchase::class,
                            $purchase->id
                        );
                    }

                    foreach ($purchaseDetailData as $purchaseDetailEntry) {
                        $purchaseDetailEntry['purchase_id'] = $purchase->id;
                        $purchaseDetailEntry['profit_margin'] = $inventoryService->getProfitMargin($purchaseDetailEntry['price'], $purchaseDetailEntry['sale_price']);
                        $purchaseDetailEntry['ref_id'] = $oldPurchaseDetail->purchase_detail_id;
                        $purchaseDetail = PurchaseDetail::create($purchaseDetailEntry);

                        /* purchased inventory for centralized db */
                        $productInventoryPurchased = $inventoryService->storeInventoryPurchase(
                            $purchaseDetail['product_id'],
                            $purchase->id,
                            $purchaseDetailEntry['price'],
                            $purchaseDetail['quantity'],
                            $purchaseDetail['expiry_date']
                        );

                        /* add in array for outflow */
                        $productInventoryPurchases[] = $productInventoryPurchased;
                    }

                    /* purchased inventory outflow for centralized db */
                    $productInventoryOutflow = ProductInventoryOutflow::create([
                        'date' => $purchase->date,
                        'products_count' => $productsCount,
                        'outflow_quantity' => $quantityCount,
                    ]);

                    /* purchased inventory outflow_details for centralized db */
                    foreach ($productInventoryPurchases as $productInventoryPurchase) {
                        $productInventoryOutflowDetail = ProductInventoryOutflowDetail::create([
                            'product_inventory_outflow_id' => $productInventoryOutflow->id,
                            'product_id' => $productInventoryPurchase['product_id'],
                            'product_inventory_purchase_id' => $productInventoryPurchase->id,
                            'quantity' => $productInventoryPurchase['available_quantity']
                        ]);
                        $productInventoryOutflowDetails[] = $productInventoryOutflowDetail;

                        /* decreased available quantity */
                        $productInventoryPurchase->available_quantity -= $productInventoryPurchase['available_quantity'];
                        $productInventoryPurchase->save();
                    }


                    foreach ($productInventoryOutflowDetails as $productInventoryOutflowDetail) {
                        $productInventoryEntry = $inventoryService->storeInventoryEntryOnPurchase(
                            $productInventoryOutflow['date'],
                            $productInventoryOutflowDetail['product_id'],
                            $productInventoryOutflowDetail['product_inventory_outflow_id'],
                            ProductInventoryOutflow::class,
                            $productInventoryOutflowDetail->productInventoryPurchase->purchased_price,
                            $productInventoryOutflowDetail['quantity'],
                            $productInventoryOutflowDetail->productInventoryPurchase->expiry_date
                        );

                        $purchaseDetailAr = Arr::first($purchaseDetailData, function ($value, $key) use ($productInventoryOutflowDetail) {
                            return $value['product_id'] == $productInventoryOutflowDetail['product_id'];
                        });

                        /* Update product quantity */
                        $product = Product::find($productInventoryOutflowDetail['product_id']);
                        $inventoryService->updateProductPriceQuantityOnPurchase(
                            $product,
                            $productInventoryOutflowDetail->productInventoryPurchase->purchased_price,
                            $purchaseDetailAr['sale_price'],
                            $productInventoryEntry['initial_quantity'],
                            boxSalePrice: $purchaseDetailAr['box_sale_price']
                        );
                        // $product->quantity = $product->quantity + $productInventoryEntry['initial_quantity'];
                        // $product->update();
                    }

                    $account = Account::find($purchase->account_id);
                    $account->purchases_amount += $purchaseAmount;
                    $account->purchases_count += 1;

                    /* check if account has balance */
                    if ($purchase->payment_status == 'due') {
                        $account->balance = $account->balance + $purchase->net_amount - $purchase->paid_amount;
                    }

                    $account->save();

                    /* Change Purchase Order status if purchase order is not null */
                    if ($purchase->purchase_order_id) {
                        $purchaseOrder = PurchaseOrder::find($purchase->purchase_order_id);
                        $purchaseOrder->status = "accepted";
                        $purchaseOrder->save();
                        foreach ($purchaseOrder->purchaseOrderDetails as $purchaseOrderDetail) {
                            $purchaseOrderDetail->quantity_sent = $purchaseOrderDetailsQuantitySent[$purchaseOrderDetail->product_id];
                            $purchaseOrderDetail->save();
                        }
                    }

                    DB::commit();
                }
            }
        });
    }
}
