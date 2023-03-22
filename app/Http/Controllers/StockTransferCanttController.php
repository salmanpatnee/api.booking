<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StockTransferCanttController extends Controller
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
        $request->validate([
            'date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'bank_account_id' => Rule::requiredIf($request->payment_method_id == PaymentMethod::BANK_ID),

            'status' => [
                'required',
                'in:completed',
            ],

            // 'is_deliverable' => 'nullable|boolean',

            // 'shipping_details' => 'nullable',
            // 'shipping_address' => 'required_if:is_deliverable,true',
            // 'shipping_address' => 'nullable',
            // 'shipping_charges' => 'required_if:is_deliverable,true|numeric',
            // 'shipping_status' => 'required_if:is_deliverable,true|in:ordered,packed,shipped',


            'sale_details' => 'required|array',
            'sale_details.*.id' => 'nullable',
            'sale_details.*.product_id' => 'required|exists:products,id',
            'sale_details.*.original_price' => 'required|numeric',
            // 'sale_details.*.discount_rate' => 'nullable|numeric',
            'sale_details.*.price' => 'required|numeric',
            'sale_details.*.quantity' => 'required|numeric|min:1',


        ]);

        $user = auth()->user();

        /* from ordered to completed */
        // $locationId = $user->location_id;
        $locationId = 1;
        /* account id for PHARMA SQUARE CANTT */
        $accountId = 40;
        $createdBy = $user->id;

        /* check whether amount is  */
        $saleData = $request->only([
            'date',
            'gross_amount',
            'net_amount',
            'payment_method_id',
            'bank_account_id',
            'status',
            'paid_amount',
            'returned_amount'
        ]);

        $productsCount = 0;
        $quantityCount = 0;
        $grossAmount = 0;
        $saleAmount = 0;

        $saleDetailData = [];

        $errorMessage = null;

        foreach ($request->sale_details as $i => $requestSaleDetail) {
            $product = Product::find($requestSaleDetail['product_id']);


            /* available quantity = product quantity */
            $availableQuantity = $product->quantity;

            /* quantity validation */
            if ($requestSaleDetail['quantity'] > $availableQuantity) {
                $errors[] = ['sale_details.' . $i . '.quantity' => ["The selected sale_details.{$i}.quantity can not be greater than {$product->quantity}"]];
                if (!$errorMessage) {
                    $errorMessage = "The selected sale_details.{$i}.quantity can not be greater than {$product->quantity}";
                }
            }

            $saleDetailAmount = $requestSaleDetail['quantity'] * $requestSaleDetail['price'];

            $productsCount += 1;
            $quantityCount += $requestSaleDetail['quantity'];
            $grossAmount += $requestSaleDetail['quantity'] * $requestSaleDetail['original_price'];

            $saleDetailData[] = [
                'product_id' => $requestSaleDetail['product_id'],
                'original_price' => $requestSaleDetail['original_price'],
                'price' => $requestSaleDetail['price'],
                'quantity' => $requestSaleDetail['quantity'],
                'amount' => $saleDetailAmount,
                'product' => $product,
            ];
        }
        unset($i);
        unset($product);

        if (!empty($errors)) {
            return response()->json(["message" => $errorMessage, "errors" => $errors], 422);
        }

        $saleAmount = $grossAmount;

        // calculate discount amount


        // add shipping charges in sale amount


        $saleData['account_id'] = $accountId;
        $saleData['gross_amount'] = $grossAmount;
        $saleData['net_amount'] = $saleAmount;
        $saleData['products_count'] = $productsCount;
        $saleData['created_by'] = $createdBy;
        $saleData['location_id'] = $locationId;

        $journalEntryService = new JournalEntryService();
        $inventoryService = new InventoryService();

        $purchaseAmount = 0;

        DB::beginTransaction();

        $sale = Sale::create($saleData);

        foreach ($saleDetailData as $saleDetailEntry) {
            $saleDetailEntry['sale_id'] = $sale->id;


            $saleDetailPurchaseAmount = $inventoryService->updateInventoryOnSale($sale->id, $sale->date, $saleDetailEntry['product_id'], $saleDetailEntry['price'], $saleDetailEntry['quantity'], 0);
            $inventoryService->updateProductQuantityOnSale($saleDetailEntry['product'], $saleDetailEntry['quantity']);
            $saleDetailEntry['purchase_amount'] = $saleDetailPurchaseAmount;
            SaleDetail::create($saleDetailEntry);

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

        return response()->json([
            'message'   => 'Stock transfer sale completed successfully.',
            'data'      => $sale,
            'status'    => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
