<?php

namespace App\Http\Controllers;

use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Account;
use App\Models\AccountHead;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductInventoryHolder;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Services\CashRegisterService;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate = request('paginate', 20);
        // $term     = request('search', '');
        // $sortOrder     = request('sortOrder', 'desc');
        // $orderBy       = request('orderBy', 'created_at');

        // $sales = Sale::search($term)->with(['account' => function ($q) {
        //     $q->select('id', 'name');
        // }]);
        // if (!empty($request->start_date))
        //     $sales->where('date', '>=', $request->start_date);
        // if (!empty($request->end_date))
        //     $sales->where('date', '<=', $request->end_date);
        // if (!empty($request->status))
        //     $sales->where('status', $request->status);
        // else {
        //     $sales->whereIn('status', ['completed', 'returned', 'final']);
        // }

        // $sales->orderBy($orderBy, $sortOrder);
        $sales = Sale::query()->indexQuery()->paginate($paginate);
        return response()->json(['data' => $sales]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSaleRequest $request)
    {
        $locationId = 1;
        $createdBy = auth()->user()->id;

        $saleData = $request->only([
            'date',
            'account_id',
            'discount_type',
            'discount_rate',
            'discount_amount',
            'gross_amount',
            'net_amount',
            'cash_register_id',
            'payment_method_id',
            'status',
            'is_deliverable',
            'shipping_details',
            'shipping_address',
            'shipping_charges',
            'shipping_status',
            'paid_amount',
            'returned_amount'
        ]);

        // $cashRegister = CashRegister::find($request->cash_register_id);        

        $productsCount = 0;
        $quantityCount = 0;
        $grossAmount = 0;
        $saleAmount = 0;
        $products = [];

        $saleDetailData = [];

        foreach ($request->sale_details as $i => $requestSaleDetail) {
            $products[$i] = Product::find($requestSaleDetail['product_id']);

            /* quantity validation */
            if ($requestSaleDetail['quantity'] > $products[$i]->quantity) {
                $errors[] = ['sale_details.' . $i . '.quantity' => ["The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}"]];
            }

            // $saleDetailAmount = $requestSaleDetail['quantity'] * $requestSaleDetail['price'];
            $saleDetailAmount = $requestSaleDetail['quantity'] * $requestSaleDetail['price'];
            // $saleAmount += $saleDetailAmount;

            $productsCount += 1;
            $quantityCount += $requestSaleDetail['quantity'];
            $grossAmount += $requestSaleDetail['quantity'] * $requestSaleDetail['original_price'];

            $saleDetailData[] = [
                'product_id' => $requestSaleDetail['product_id'],
                'original_price' => $requestSaleDetail['original_price'],
                'discount_rate' => $requestSaleDetail['discount_rate'],
                'price' => $requestSaleDetail['price'],
                'quantity' => $requestSaleDetail['quantity'],
                'amount' => $saleDetailAmount
            ];
        }

        if (!empty($errors)) {
            return response()->json(["message" => "The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}", "errors" => $errors], 422);
        }

        $saleAmount = $grossAmount;

        // calculate discount amount
        if (!empty($saleData['discount_amount'])) {
            $saleAmount = $saleAmount - $saleData['discount_amount']; //net amount
        }

        // add shipping charges in sale amount
        if (!empty($saleData['shipping_charges'])) {
            $saleAmount = $saleAmount + $saleData['shipping_charges'];
        }

        $saleData['gross_amount'] = $grossAmount;
        $saleData['net_amount'] = $saleAmount;
        $saleData['products_count'] = $productsCount;
        $saleData['location_id'] = $locationId;

        $saleData['created_by'] = $createdBy;

        if ($saleData['is_deliverable']) {
            $saleData['shipping_status'] = "ordered";
        }

        $inventoryService = new InventoryService();

        DB::beginTransaction();

        $sale = Sale::create($saleData);

        foreach ($saleDetailData as $i => $saleDetailEntry) {
            $saleDetailEntry['sale_id'] = $sale->id;

            if ($sale->status == SALE::SALE_STATUS_ORDERED) {
                $purchaseAmount = $inventoryService->holdInventoryOnSale($sale->id, $sale->date, $saleDetailEntry['product_id'], $saleDetailEntry['price'], $saleDetailEntry['quantity'], 0);
                $inventoryService->updateProductQuantityOnSale($products[$i], $saleDetailEntry['quantity']);
            }

            $saleDetailEntry['purchase_amount'] = $purchaseAmount;
            $saleDetail = SaleDetail::create($saleDetailEntry);
        }

        DB::commit();

        return response()->json([
            'message'   => 'Sale created successfully.',
            'data'      => $sale,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $sale, Request $request)
    {
        if ($request->for == 'sales-returns.create') {
            $sale->load([
                'account' => function ($q) {
                    $q->select('id', 'name');
                },
                'saleDetails' => function ($q) {
                    // , 'products.name as product_name'
                    $q->select(
                        'sale_details.id',
                        'sale_id',
                        'sale_details.product_id',
                        'original_price',
                        'discount_rate',
                        'sale_details.price',
                        'sale_details.quantity',
                        DB::raw("CAST(COALESCE((select (sale_details.quantity - SUM(sales_return_details.quantity_return)) from sales_return_details left join sales_returns on sales_returns.id = sales_return_details.sales_return_id where sales_returns.sale_id = sale_details.sale_id and sales_return_details.product_id = sale_details.product_id), sale_details.quantity) AS UNSIGNED)  as remaining_quantity"),
                        'sale_details.amount'
                    )
                        // ->leftJoin('products', 'products.id', '=', 'sales.product_id')
                        ->with(['product' => function ($q) {
                            $q->select('id', 'name', 'quantity as stock', 'default_selling_price', 'default_selling_price_old', 'discount_rate_cash', 'discount_rate_card', 'discount_rate_shipment', 'is_locked');
                        }]);
                },
                'salesReturns' => function ($q) {
                    $q->select("id", "sale_id", "date", "sale_amount_before_return", "sale_amount_after_return", "sale_return_amount");
                    // ->with(['salesReturnDetails'=>function($q){
                    //     $q->select('id', )
                    // }]);
                },
            ]);
            return response()->json(['data' => $sale]);
        }
        // return $sale;
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
            },
            'salesReturns' => function ($q) {
                $q->select("id", "sale_id", "date", "sale_amount_before_return", "sale_amount_after_return", "sale_return_amount");
                // ->with(['salesReturnDetails'=>function($q){
                //     $q->select('id', )
                // }]);
            },
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
    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        $user = auth()->user();

        /* from ordered to completed */
        // $locationId = $user->location_id;
        $locationId = 1;
        $updatedBy = $user->id;

        /* check whether amount is  */

        $saleData = $request->only([
            'discount_type',
            'discount_rate',
            'discount_amount',
            'gross_amount',
            'net_amount',
            'payment_method_id',
            'bank_account_id',
            'status',
            'is_deliverable',
            'shipping_details',
            'shipping_address',
            'shipping_charges',
            'shipping_status',
            'paid_amount',
            'returned_amount'
        ]);

        $saleDetails = $sale->saleDetails;

        $productsCount = 0;
        $quantityCount = 0;
        $grossAmount = 0;
        $saleAmount = 0;
        $products = [];

        $saleDetailData = [];

        $errorMessage = null;

        foreach ($request->sale_details as $i => $requestSaleDetail) {
            $products[$i] = Product::find($requestSaleDetail['product_id']);


            /* available quantity = ordered quantity + product quantity */
            $availableQuantity = $products[$i]->quantity;
            if (array_key_exists("id", $requestSaleDetail)) {
                $saleDetail = $saleDetails->where('id', $requestSaleDetail['id'])->first();
                $availableQuantity += $saleDetail->quantity;
            }

            /* quantity validation */
            if ($requestSaleDetail['quantity'] > $availableQuantity) {
                $errors[] = ['sale_details.' . $i . '.quantity' => ["The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}"]];
                if (!$errorMessage) {
                    $errorMessage = "The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}";
                }
            }

            $saleDetailAmount = $requestSaleDetail['quantity'] * $requestSaleDetail['price'];

            $productsCount += 1;
            $quantityCount += $requestSaleDetail['quantity'];
            $grossAmount += $requestSaleDetail['quantity'] * $requestSaleDetail['original_price'];

            $saleDetailData[] = [
                'id' => $requestSaleDetail['id'] ?? null,
                'product_id' => $requestSaleDetail['product_id'],
                'original_price' => $requestSaleDetail['original_price'],
                'discount_rate' => $requestSaleDetail['discount_rate'],
                'price' => $requestSaleDetail['price'],
                'quantity' => $requestSaleDetail['quantity'],
                'amount' => $saleDetailAmount,
                'product' => $products[$i],
            ];
        }
        unset($i);

        if (!empty($errors)) {
            return response()->json(["message" => $errorMessage, "errors" => $errors], 422);
        }

        $saleAmount = $grossAmount;

        // calculate discount amount
        if (!empty($saleData['discount_amount'])) {
            $saleAmount = $saleAmount - $saleData['discount_amount']; //net amount
        }

        // add shipping charges in sale amount
        if (!empty($saleData['shipping_charges'])) {
            $saleAmount = $saleAmount + $saleData['shipping_charges'];
        }

        $saleData['gross_amount'] = $grossAmount;
        $saleData['net_amount'] = $saleAmount;
        $saleData['products_count'] = $productsCount;
        $saleData['updated_by'] = $updatedBy;


        $journalEntryService = new JournalEntryService();
        $inventoryService = new InventoryService();

        $cashRegister = CashRegister::where('user_id', $user->id)
            ->whereNull('end_datetime')
            ->first();
        if (!$cashRegister) {
            return response()->json(["message" => "No register found", "errors" => []], 422);
        }

        $saleData['date'] = $cashRegister->date;

        $saleDetailIds = $saleDetails->pluck('id', 'id');
        $purchaseAmount = 0;

        DB::beginTransaction();

        // $inventoryHolders = ProductInventoryHolder::where('sale_id', $sale->id)->get();
        // $inventoryHolderIds = [];
        // $productInventoryHolders = $inventoryHolders->where('product_id', $saleDetailEntry['product_id']);
        //     dd($productInventoryHolders);

        foreach ($saleDetailData as $saleDetailEntry) {
            $saleDetailEntry['sale_id'] = $sale->id;

            /* check if old entry exist */
            if ($saleDetailEntry['id']) {
                $saleDetail = $saleDetails->where('id', $saleDetailEntry['id'])->first();
                /* check if entry is change */
                if ($saleDetail->quantity != $saleDetailEntry['quantity'] || $saleDetail->price != $saleDetailEntry['price']) {
                    /* reverse inventory */
                    $inventoryService->reverseInventoryFromHolder($locationId, $sale->id, $saleDetail->product_id);
                    $inventoryService->updateProductQuantityOnSalesReturn($saleDetailEntry['product'], $saleDetail->quantity);

                    /* store inventory */
                    $saleDetailPurchaseAmount = $inventoryService->updateInventoryOnSale($sale->id, $sale->date, $saleDetailEntry['product_id'], $saleDetailEntry['price'], $saleDetailEntry['quantity'], 0);
                    $inventoryService->updateProductQuantityOnSale($saleDetailEntry['product'], $saleDetailEntry['quantity']);

                    /* update sale detail */
                    $saleDetailEntry['purchase_amount'] = $saleDetailPurchaseAmount;
                    $saleDetail->update($saleDetailEntry);

                    $purchaseAmount += $saleDetailPurchaseAmount;
                } else {
                    /* move quantity from holder to sold */
                    $inventoryService->updateInventoryFromHolder($locationId, $sale->id, $sale->date, $saleDetailEntry['product_id']);

                    $purchaseAmount += $saleDetail->purchase_amount;
                }
                $saleDetailIds->forget($saleDetail->id);
            } else {
                $saleDetailPurchaseAmount = $inventoryService->updateInventoryOnSale($sale->id, $sale->date, $saleDetailEntry['product_id'], $saleDetailEntry['price'], $saleDetailEntry['quantity'], 0);
                $inventoryService->updateProductQuantityOnSale($saleDetailEntry['product'], $saleDetailEntry['quantity']);
                $saleDetailEntry['purchase_amount'] = $saleDetailPurchaseAmount;
                $saleDetail = SaleDetail::create($saleDetailEntry);

                $purchaseAmount += $saleDetailPurchaseAmount;
            }

            // $saleDetail = SaleDetail::create($saleDetailEntry);

            // $inventoryService->updateInventoryOnSale($sale->id, $sale->date, $saleDetail['product_id'], $saleDetail['quantity']);
            // $inventoryService->updateProductQuantityOnSale($products[$i], $saleDetail['quantity']);
        }

        /* delete entries */
        foreach ($saleDetailIds as $saleDetailId) {
            $saleDetail = $saleDetails->where('id', $saleDetailId)->first();

            $product = $saleDetail->product;

            $inventoryService->reverseInventoryFromHolder($locationId, $sale->id, $saleDetail->product_id, $saleDetail->quantity);
            $inventoryService->updateProductQuantityOnSalesReturn($product, $saleDetail->quantity);

            $saleDetail = $saleDetails->where('id', $saleDetailId)->first();
            $saleDetail->delete();
        }

        // /* delete ids */
        // dd($saleDetailIds);
        // dd("end");

        $saleData['purchase_amount'] = $purchaseAmount;
        $saleData['status'] = "completed";
        if ($sale->is_deliverable) {
            $saleData['status'] = 'ordered';
            $saleData['shipping_status'] = "shipped";
        }

        $sale->update($saleData);

        /* Cash Debit & Sale Credit */
        $journalEntrySerialNumber = $journalEntryService->getSerialNumber();

        $journalEntryService->recordEntry(
            $journalEntrySerialNumber,
            AccountHead::CASH_ID, //cash will be dynamic
            AccountHead::SALE_ID,
            $saleAmount,
            0,
            $sale['date'],
            Sale::class,
            $sale->id
        );

        $journalEntryService->recordEntry(
            $journalEntrySerialNumber,
            AccountHead::SALE_ID,
            AccountHead::CASH_ID, //cash will be dynamic
            0,
            $saleAmount,
            $sale['date'],
            Sale::class,
            $sale->id
        );

        $account = Account::find($sale->account_id);
        $account->sales_amount += $saleAmount;
        $account->sales_count += 1;
        $account->save();

        if ($sale->payment_method_id == PaymentMethod::CASH_ID && !$sale->is_deliverable) {
            $cashRegisterService = new CashRegisterService();

            $cashRegisterService->saveEntry(
                cashRegisterId: $cashRegister->id,
                description: "Sale",
                cashRegisterBalance: $cashRegister->balance,
                referenceType: Sale::class,
                referenceId: $sale->id,
                debit: $sale->net_amount
            );

            $cashRegisterService->updateBalance($cashRegister, debit: $sale->net_amount);
        }

        DB::commit();

        return response()->json([
            'message'   => 'Sale completed successfully.',
            'data'      => $sale,
            'status'    => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sale $sale)
    {
        $locationId = 1;
        if ($sale->status == Sale::SALE_STATUS_ORDERED) {
            $inventoryService = new InventoryService();

            $saleDetails = $sale->saleDetails;
            foreach ($saleDetails as $saleDetail) {

                $product = $saleDetail->product;

                $inventoryService->reverseInventoryFromHolder($locationId, $sale->id, $saleDetail->product_id, $saleDetail->quantity);
                $inventoryService->updateProductQuantityOnSalesReturn($product, $saleDetail->quantity);

                $saleDetail->delete();
            }

            $sale->forceDelete();

            return response()->json([
                'message'   => 'Sale deleted successfully.',
                'data'      => $sale,
                'status'    => 'success'
            ], Response::HTTP_OK);
        } else if ($sale->status == Sale::SALE_STATUS_COMPLETED) {
            # code...
        }
    }

    public function export(Request $request)
    {
        return Excel::download(new SalesExport($request), 'sales.xlsx');
    }

    public function import()
    {

        // $sale = Sale::find(1);
        // $saleDiscountAmount = 0;
        // foreach ($sale->saleDetails as $i => $saleDetail) {
        //     if($saleDetail->discount_rate) {
        //         $discountInPoints = ($saleDetail->discount_rate / 100);


        //         $saleDiscountAmount += ($saleDetail->price * $discountInPoints) * $saleDetail->quantity;
        //     }
        // }



        $lastId = null;
        $lastSale = Sale::whereNotNull('ref_id')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale) {
            $lastOldSale = DB::connection("mysql2")->table("invoice")
                ->select('id', 'invoice_id')
                ->where('invoice_id', $lastSale->ref_id)
                ->first();
            $lastId = $lastOldSale->id;
        }

        /* ignoring purchases where amount = 0 */
        $query = DB::connection("mysql2")->table("invoice")
            ->select("id", "invoice_id", "customer_id", "date", "total_amount", "paid_amount", "due_amount", "prevous_due", "shipping_cost", "total_discount", "bank_id", "payment_type");


        if ($lastId)
            $query->where("id", ">", $lastId);

        $query->where('invoice_id', '6346887186');


        $query = $query->orderBy('id')->chunk(300, function ($oldSales) {
            foreach ($oldSales as $i => $oldSale) {

                $sale = Sale::where('ref_id', $oldSale->invoice_id)
                    ->first();

                if (!$sale) {
                    if ($oldSale->total_amount == 0) {
                        DB::table("sale_migration_logs")->insert([
                            'opharma_invoice_id' => $oldSale->invoice_id,
                            'opharma_invoice_detail_id' => null,
                            'opharma_product_id' => null,
                            'remarks' => "Total amount is 0"
                        ]);
                        continue;
                    }



                    if ($oldSale->prevous_due != 0) {
                        DB::table("sale_migration_logs")->insert([
                            'opharma_invoice_id' => $oldSale->invoice_id,
                            'opharma_invoice_detail_id' => null,
                            'opharma_product_id' => null,
                            'remarks' => "Total amount is 0"
                        ]);
                    }
                    $bankId = null;

                    $oldBanks = [2 => 1, "Y8COMVRB6O" => 2];
                    if ($oldSale->bank_id) $bankId = $oldBanks[$oldSale->bank_id];

                    $oldCustomer = DB::connection("mysql2")->table("customer_information")
                        ->select("customer_id", "customer_name", "customer_mobile", "customer_email", "create_date")
                        ->where('customer_id', $oldSale->customer_id)
                        ->first();

                    $customer = Account::where(function ($query) use ($oldCustomer) {
                        $query->where('email', $oldCustomer->customer_email)
                            ->orWhere('phone', $oldCustomer->customer_mobile);
                    })
                        ->where('account_type', "customer")
                        ->first();

                    /* from ordered to completed */
                    // $locationId = $user->location_id;
                    $locationId = 1;

                    /* check whether amount is  */

                    $saleData = [
                        'date' => $oldSale->date,
                        'account_id' => $customer ? $customer->id : Account::WALK_IN_CUSTOMER_ID,
                        'discount_type' => null,
                        'discount_rate' => null,
                        'discount_amount' => null,
                        'cash_register_id' => null,
                        'payment_method_id' => in_array($oldSale->payment_type, [1, 2]) ? $oldSale->payment_type : 1,
                        'bank_account_id' => $bankId,
                        'status' => $oldSale->due_amount != 0 ? Sale::SALE_STATUS_ORDERED : Sale::SALE_STATUS_COMPLETED,
                        'is_deliverable' => $oldSale->payment_type == 3 ? true : false,
                        'shipping_details' => null,
                        'shipping_address' => null,
                        'shipping_charges' => $oldSale->shipping_cost,
                        'shipping_status' => SALE::SHIPPING_STATUS_DELIVERED,
                        'paid_amount' => $oldSale->paid_amount,
                        'returned_amount' => null,
                        'ref_id' => $oldSale->invoice_id,
                    ];



                    $productsCount = 0;
                    $quantityCount = 0;
                    $grossAmount = 0;
                    $saleAmount = 0;
                    $products = [];

                    $saleDetailData = [];

                    $saleDiscountAmount = 0;

                    $oldSaleDetails = DB::connection("mysql2")->table("invoice_details")
                        ->select("id", "invoice_details_id", "invoice_id", "product_id", "quantity", "rate", "discount_per")
                        ->where('invoice_id', $oldSale->invoice_id)
                        ->get();

                    foreach ($oldSaleDetails as $i => $oldSaleDetailCollection) {
                        $oldSaleDetail = (array) $oldSaleDetailCollection;

                        $product = Product::where('ref_id', $oldSaleDetail['product_id'])
                            ->first();

                        if (!$product) {
                            DB::table("sale_migration_logs")->insert([
                                'opharma_invoice_id' => $oldSale->invoice_id,
                                'opharma_invoice_detail_id' => $oldSaleDetail['invoice_details_id'],
                                'opharma_product_id' => $oldSaleDetail['product_id'],
                                'remarks' => "Product not found"
                            ]);
                            continue;
                        }

                        // dd($oldSale, $oldSaleDetail);


                        /* available quantity = ordered quantity + product quantity */
                        $availableQuantity = $product->quantity;

                        /* quantity validation */
                        if ($oldSaleDetail['quantity'] > $availableQuantity) {
                            // $errors[] = ['sale_details.' . $i . '.quantity' => ["The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}"]];
                            // dd($oldSale, $oldSaleDetail, $products[$i]);
                            DB::table("sale_migration_logs")->insert([
                                'opharma_invoice_id' => $oldSale->invoice_id,
                                'opharma_invoice_detail_id' => $oldSaleDetail['invoice_details_id'],
                                'opharma_product_id' => $oldSaleDetail['product_id'],
                                'remarks' => "Quantity not available"
                            ]);
                            continue;
                        }

                        $products[$i] = $product;

                        $originalPrice = $oldSaleDetail['rate'];

                        $salePrice = $originalPrice;

                        $discountRate = null;
                        if (!empty($oldSaleDetail['discount_per']) && $oldSaleDetail['discount_per'] > 0) {
                            $discountInPoints = $oldSaleDetail['discount_per'] / 100;

                            $saleDiscountAmount += ($originalPrice * $discountInPoints) * $oldSaleDetail['quantity'];

                            $salePrice = $originalPrice * (1 - $discountInPoints);

                            $discountRate = $oldSaleDetail['discount_per'];
                        }

                        $saleDetailAmount = $oldSaleDetail['quantity'] * $salePrice;

                        $productsCount += 1;
                        $quantityCount += $oldSaleDetail['quantity'];
                        $grossAmount += $oldSaleDetail['quantity'] * $oldSaleDetail['rate'];

                        $saleDetailData[] = [
                            'product_id' => $products[$i]->id,
                            'original_price' => $oldSaleDetail['rate'],
                            'discount_rate' => $discountRate,
                            'price' => $salePrice,
                            'quantity' => $oldSaleDetail['quantity'],
                            'amount' => $saleDetailAmount
                        ];
                    }

                    if (!empty($errors)) {
                        return response()->json(["message" => "The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}", "errors" => $errors], 422);
                    }

                    $saleData['discount_amount'] = $saleDiscountAmount;

                    $saleAmount = $grossAmount;

                    // calculate discount amount
                    if (!empty($saleData['discount_amount'])) {
                        $saleAmount = $saleAmount - $saleData['discount_amount']; //net amount
                    }

                    // add shipping charges in sale net amount
                    if (!empty($saleData['shipping_charges'])) {
                        $saleAmount = $saleAmount + $saleData['shipping_charges'];
                    }

                    $saleData['gross_amount'] = $grossAmount;
                    $saleData['net_amount'] = $saleAmount;
                    $saleData['products_count'] = $productsCount;
                    $saleData['created_by'] = 1;
                    $saleData['location_id'] = $locationId;

                    if ($oldSale->total_amount != $saleData['net_amount']) {
                        DB::table("sale_migration_logs")->insert([
                            'opharma_invoice_id' => $oldSale->invoice_id,
                            'opharma_invoice_detail_id' => null,
                            'opharma_product_id' => null,
                            'remarks' => "Total amount does not match calculated amount"
                        ]);
                    }


                    $journalEntryService = new JournalEntryService();
                    $inventoryService = new InventoryService();

                    $purchaseAmount = 0;

                    DB::beginTransaction();



                    $sale = Sale::create($saleData);

                    foreach ($saleDetailData as $i => $saleDetailEntry) {
                        $saleDetailEntry['sale_id'] = $sale->id;

                        $productAr = Arr::first($products, function ($value, $key) use ($saleDetailEntry) {
                            return $value && $value['id'] == $saleDetailEntry['product_id'];
                        });




                        $saleDetailPurchaseAmount = $inventoryService->updateInventoryOnSale($sale->id, $sale->date, $saleDetailEntry['product_id'], $saleDetailEntry['price'], $saleDetailEntry['quantity'], 0);
                        $inventoryService->updateProductQuantityOnSale($productAr, $saleDetailEntry['quantity']);
                        $saleDetailEntry['purchase_amount'] = $saleDetailPurchaseAmount;
                        $saleDetail = SaleDetail::create($saleDetailEntry);

                        $purchaseAmount += $saleDetailPurchaseAmount;
                    }

                    $saleData['purchase_amount'] = $purchaseAmount;
                    $saleData['status'] = "completed";

                    $sale->update($saleData);

                    /* Cash Debit & Sale Credit */
                    $journalEntrySerialNumber = $journalEntryService->getSerialNumber();

                    $journalEntryService->recordEntry(
                        $journalEntrySerialNumber,
                        AccountHead::CASH_ID, //cash will be dynamic
                        AccountHead::SALE_ID,
                        $saleAmount,
                        0,
                        $sale['date'],
                        Sale::class,
                        $sale->id
                    );

                    $journalEntryService->recordEntry(
                        $journalEntrySerialNumber,
                        AccountHead::SALE_ID,
                        AccountHead::CASH_ID, //cash will be dynamic
                        0,
                        $saleAmount,
                        $sale['date'],
                        Sale::class,
                        $sale->id
                    );

                    $account = Account::find($sale->account_id);
                    $account->sales_amount += $saleAmount;
                    $account->sales_count += 1;
                    $account->save();

                    DB::commit();
                }
            }
        });
        return response()->json([
            'message'   => 'Sales imported successfully.',
            'data'      => [],
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }
}
