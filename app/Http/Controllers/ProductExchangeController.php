<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\ProductExchange;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalesReturn;
use App\Models\SalesReturnDetail;
use App\Services\CashRegisterService;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProductExchangeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate = request('paginate', 10);
        $term     = request('search', '');
        // $sortOrder     = request('sortOrder', 'desc');
        // $orderBy       = request('orderBy', 'created_at');

        $productExchanges = ProductExchange::search($term)->select('id', 'sale_id', 'date', 'sale_amount', 'sales_return_amount', 'net_amount')
            ->with([
                'sale' => function ($q) {
                    $q->select('id', 'account_id', 'date')
                        ->with(['account' => function ($q) {
                            $q->select('id', 'name');
                        }]);
                },
            ])
            ->orderBy('date')
            ->paginate($paginate);
        return response()->json(['data' => $productExchanges]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $locationId = 1; //will be dynamic
        $createdBy = auth()->user()->id;

        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id', //sales return sale id
            'date' => 'required|date',
            'payment_method_id' => 'required',
            'sales_return_details' => 'required|array',
            'sales_return_details.*.product_id' => 'required|exists:products,id',
            'sales_return_details.*.quantity_return' => 'required|numeric',
            'sale_details' => 'required|array',
            'sale_details.*.product_id' => 'required|exists:products,id',
            'sale_details.*.original_price' => 'required|numeric',
            'sale_details.*.discount_rate' => 'nullable|numeric',
            'sale_details.*.price' => 'required|numeric',
            'sale_details.*.quantity' => 'required|numeric',
        ]);

        $productsCountForReturn = 0;
        $quantityCountForReturn = 0;

        $grossAmountForReturn = 0;
        $salesReturnAmount = 0;

        $productsForReturn = [];

        $salesReturnDetailData = [];

        $saleForReturn = Sale::find($request->sale_id);
        $saleDetailsForReturn = $saleForReturn->saleDetails;

        /* variables for sale */
        $productsCount = 0;
        $quantityCount = 0;
        $grossAmountForSale = 0;
        $saleAmount = 0;
        $products = [];

        $saleDetailData = [];

        $message = null;
        $errors = [];

        foreach ($request->sale_details as $i => $requestSaleDetail) {
            $products[$i] = Product::find($requestSaleDetail['product_id']);

            /* quantity validation */
            if ($requestSaleDetail['quantity'] > $products[$i]->quantity) {
                $errors[] = ['sale_details.' . $i . '.quantity' => ["The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}"]];
                if (!$message) {
                    $message = "The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}";
                }
            }

            if ($message) continue;



            $saleDetailAmount = $requestSaleDetail['quantity'] * $requestSaleDetail['price'];

            $productsCount += 1;
            $quantityCount += $requestSaleDetail['quantity'];
            $grossAmountForSale += $saleDetailAmount;

            $saleDetailData[] = [
                'product_id' => $requestSaleDetail['product_id'],
                'original_price' => $requestSaleDetail['original_price'],
                'discount_rate' => $requestSaleDetail['discount_rate'],
                'price' => $requestSaleDetail['price'],
                'quantity' => $requestSaleDetail['quantity'],
                'amount' => $saleDetailAmount,
            ];
        }

        foreach ($request->sales_return_details as $i => $requestSalesReturnDetail) {
            $saleDetailForReturn = $saleDetailsForReturn->where('product_id', $requestSalesReturnDetail['product_id'])->first();


            $productsForReturn[$i] = Product::find($requestSalesReturnDetail['product_id']);

            /* quantity validation */
            if ($requestSalesReturnDetail['quantity_return'] > $saleDetailForReturn->quantity) {
                $errors[] = ['sales_return_details.' . $i . '.quantity_return' => ["The sales_return_details.{$i}.quantity_return must be less than or equal to {$saleDetailForReturn->quantity}."]];
                if (!$message) {
                    $message = "The sales_return_details.{$i}.quantity_return must be less than or equal to {$saleDetailForReturn->quantity}.";
                }
            }

            if ($message) continue;

            $saleDetailAmountForReturn = $requestSalesReturnDetail['quantity_return'] * $saleDetailForReturn['price'];

            $productsCountForReturn += 1;
            $quantityCountForReturn += $requestSalesReturnDetail['quantity_return'];

            $grossAmountForReturn += $saleDetailAmountForReturn;

            $salesReturnDetailData[] = [
                'product_id' => $requestSalesReturnDetail['product_id'],
                'price' => $saleDetailForReturn['price'],
                'quantity_before_return' => $saleDetailForReturn['quantity'],
                'quantity_return' => $requestSalesReturnDetail['quantity_return'],
                'amount' => $saleDetailAmountForReturn
            ];
        }

        if ($message) {
            return response()->json([
                "message" => $message,
                "errors" => $errors
            ], 422);
        }

        $cashRegister = CashRegister::where('user_id', $createdBy)
            ->whereNull('end_datetime')
            ->first();
        if (!$cashRegister) {
            return response()->json(["message" => "No register found", "errors" => []], 422);
        }

        $salesReturnAmount = $grossAmountForReturn;

        $journalEntryService = new JournalEntryService();
        $inventoryService = new InventoryService();

        DB::beginTransaction();

        $saleForReturn->status = 'final';
        $saleForReturn->save();
        // 'date' => $request->date,
        $salesReturn = SalesReturn::create([
            'sale_id' => $saleForReturn->id,
            'date' => $cashRegister->date,
            'sale_amount_before_return' => $saleForReturn->net_amount,
            'sale_amount_after_return' => $saleForReturn->net_amount - $salesReturnAmount,
            'sale_return_amount' => $salesReturnAmount,
            'created_by' => $createdBy,
        ]);

        /* Sales Return Debit & Cash Credit */
        $journalEntrySerialNumber = $journalEntryService->getSerialNumber();

        $journalEntryService->recordEntry(
            $journalEntrySerialNumber,
            AccountHead::SALES_RETURN, //cash will be dynamic
            AccountHead::CASH_ID,
            $salesReturnAmount,
            0,
            $salesReturn->date,
            SalesReturn::class,
            $salesReturn->id,
        );

        $journalEntryService->recordEntry(
            $journalEntrySerialNumber,
            AccountHead::CASH_ID,
            AccountHead::SALES_RETURN, //cash will be dynamic
            0,
            $salesReturnAmount,
            $salesReturn->date,
            SalesReturn::class,
            $salesReturn->id,
        );

        foreach ($salesReturnDetailData as $i => $salesReturnDetailEntry) {
            $salesReturnDetailEntry['sales_return_id'] = $salesReturn->id;
            // $saleDetailForReturn = SaleDetail::where('sale_id', $saleForReturn->id)
            // ->where('product_id', $salesReturnDetailEntry['product_id'])
            // ->first();
            $salesReturnDetail = SalesReturnDetail::create($salesReturnDetailEntry);

            $inventoryService->updateInventoryOnSalesReturn($saleForReturn->id, $salesReturn->id, $salesReturn->date, $salesReturnDetail['product_id'], $saleDetailForReturn->price, $salesReturnDetail['quantity_return']);
            $inventoryService->updateProductQuantityOnSalesReturn($productsForReturn[$i], $salesReturnDetail['quantity_return']);
        }

        /* Add sale after sales return */
        $saleAmount = $grossAmountForSale;

        $saleData = [
            'date' => $cashRegister->date,
            'account_id' => $saleForReturn->account_id,
            // 'reference_number' => $saleForReturn->reference_number,
            'gross_amount',
            'net_amount',
            'payment_method_id' => $data['payment_method_id'],
            'status' => 'ordered', //need some brainstorming
        ];

        // calculate discount amount        

        // add shipping charges in sale amount

        $saleData['gross_amount'] = $grossAmountForSale;
        $saleData['net_amount'] = $saleAmount;
        $saleData['products_count'] = $productsCount;
        $saleData['location_id'] = $locationId;

        $saleData['created_by'] = $createdBy;

        $sale = Sale::create($saleData);

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

        foreach ($saleDetailData as $i => $saleDetailEntry) {
            $saleDetailEntry['sale_id'] = $sale->id;            
            $saleDetailPurchaseAmount = $inventoryService->updateInventoryOnSale($sale->id, $sale->date, $saleDetailEntry['product_id'], $saleDetailEntry['price'], $saleDetailEntry['quantity'], 0);
            $inventoryService->updateProductQuantityOnSale($products[$i], $saleDetailEntry['quantity']);

            $saleDetailEntry['purchase_amount'] = $saleDetailPurchaseAmount;
            
            SaleDetail::create($saleDetailEntry);
        }

        $account = Account::find($sale->account_id);
        $account->sales_amount += $account->sales_amount + $saleAmount - $salesReturnAmount;
        $account->sales_count += 1;
        $account->save();

        $productExchange = ProductExchange::create([
            'sale_id' => $sale->id,
            'sales_return_id' => $salesReturn->id,
            'date' => $cashRegister->date,
            'sale_amount' => $sale->net_amount,
            'sales_return_amount' => $salesReturn->sale_return_amount,
            'net_amount' => $sale->net_amount - $salesReturn->sale_return_amount,
            'status' => 'ordered',
            'created_by' => $createdBy,
        ]);

        $cashRegisterService = new CashRegisterService();

        $cashRegisterService->saveEntry(
            cashRegisterId: $cashRegister->id,
            description: "Product Exchange",
            cashRegisterBalance: $cashRegister->balance,
            referenceType: ProductExchange::class,
            referenceId: $productExchange->id,
            debit: $sale->net_amount,
            credit: $salesReturn->sale_return_amount
        );

        $cashRegisterService->updateBalance($cashRegister, debit: $sale->net_amount, credit: $salesReturn->sale_return_amount);

        DB::commit();

        return response()->json([
            'message'   => 'Product Exchanged successfully.',
            'data'      => $productExchange,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductExchange  $productExchange
     * @return \Illuminate\Http\Response
     */
    public function show(ProductExchange $productExchange)
    {
        $productExchange->load([
            'sale' => function ($q) {
                $q->select('id', 'products_count', 'gross_amount', 'net_amount', 'account_id')
                    ->with([
                        'account' => function ($q) {
                            $q->select('id', 'name');
                        },
                        'saleDetails' => function ($q) {
                            $q->select('id', 'sale_id', 'product_id', 'price', 'quantity', 'amount')
                                ->with(['product' => function ($q) {
                                    $q->select('id', 'name');
                                }]);
                        }
                    ]);
            },
            'salesReturn' => function ($q) {
                $q->select('id', 'sale_amount_before_return', 'sale_amount_after_return', 'sale_return_amount')
                    ->with([
                        'salesReturnDetails' => function ($q) {
                            $q->select('id', 'sales_return_id', 'product_id', 'price', 'quantity_before_return', 'quantity_return')
                                ->with(['product' => function ($q) {
                                    $q->select('id', 'name');
                                }]);
                        }
                    ]);
            },
        ]);
        return response()->json(['data' => $productExchange]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductExchange  $productExchange
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductExchange $productExchange)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductExchange  $productExchange
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductExchange $productExchange)
    {
        //
    }
}
