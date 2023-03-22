<?php

namespace App\Http\Controllers;

use App\Exports\SaleReturnsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Account;
use App\Models\AccountHead;
use App\Models\CashRegister;
use App\Models\CashRegisterEntry;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\SalesReturnDetail;
use App\Services\CashRegisterService;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SalesReturnController extends Controller
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

        $salesReturns = SalesReturn::indexQuery($term)
            ->paginate($paginate);
        return response()->json(['data' => $salesReturns]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /* custom validation for preventing return if status is final */


        /* double sales return sale quantity validation */
        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'date' => 'required|date',
            'sales_return_details' => 'required|array',
            'sales_return_details.*.product_id' => 'required|exists:products,id',
            'sales_return_details.*.quantity_return' => 'required|numeric',
        ]);

        $userId = auth()->user()->id;

        $productsCount = 0;
        $quantityCount = 0;

        $grossAmount = 0;
        $salesReturnAmount = 0;

        $products = [];

        $salesReturnDetailData = [];

        $sale = Sale::find($request->sale_id);
        $saleDetails = $sale->saleDetails;

        $message = null;
        $errors = [];

        foreach ($request->sales_return_details as $i => $requestSalesReturnDetail) {
            if ($requestSalesReturnDetail['quantity_return'] == 0) continue;
            $saleDetail = $saleDetails->where('product_id', $requestSalesReturnDetail['product_id'])->first();


            $product = Product::find($requestSalesReturnDetail['product_id']);

            /* quantity validation */
            if ($requestSalesReturnDetail['quantity_return'] > $saleDetail->quantity) {
                $errors[] = ['sales_return_details.' . $i . '.quantity_return' => ["The sales_return_details.{$i}.quantity_return must be less than or equal to {$saleDetail->quantity}."]];
                if (!$message) {
                    $message = "The sales_return_details.{$i}.quantity_return must be less than or equal to {$saleDetail->quantity}.";
                }
            }

            if ($message) continue;

            $saleDetailAmount = $requestSalesReturnDetail['quantity_return'] * $saleDetail['price'];

            $productsCount += 1;
            $quantityCount += $requestSalesReturnDetail['quantity_return'];

            $grossAmount += $saleDetailAmount;

            $salesReturnDetailData[] = [
                'product_id' => $requestSalesReturnDetail['product_id'],
                'price' => $saleDetail['price'],
                'quantity_before_return' => $saleDetail['quantity'],
                'quantity_return' => $requestSalesReturnDetail['quantity_return'],
                'amount' => $saleDetailAmount,
                'product' => $product
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

        $cashRegister = CashRegister::where('user_id', $userId)
            ->whereNull('end_datetime')
            ->first();
        if (!$cashRegister) {
            return response()->json(["message" => "No register found", "errors" => []], 422);
        }

        $salesReturnAmount = $grossAmount;

        $journalEntryService = new JournalEntryService();
        $inventoryService = new InventoryService();

        DB::beginTransaction();

        $salesReturn = SalesReturn::create([
            'sale_id' => $sale->id,
            'date' => $cashRegister->date,
            'sale_amount_before_return' => $sale->net_amount,
            'sale_amount_after_return' => $sale->net_amount - $salesReturnAmount,
            'sale_return_amount' => $salesReturnAmount,
            'created_by' => $userId,
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

        foreach ($salesReturnDetailData as $salesReturnDetailEntry) {
            $product = $salesReturnDetailEntry['product'];

            $salesReturnDetailEntry['sales_return_id'] = $salesReturn->id;

            $saleDetail = $saleDetails->where('product_id', $requestSalesReturnDetail['product_id'])->first();
            $salesReturnDetail = SalesReturnDetail::create($salesReturnDetailEntry);

            $inventoryService->updateInventoryOnSalesReturn($sale->id, $salesReturn->id, $salesReturn->date, $salesReturnDetail['product_id'], $saleDetail->price, $salesReturnDetail['quantity_return']);
            $inventoryService->updateProductQuantityOnSalesReturn($product, $salesReturnDetail['quantity_return']);
        }

        if ($cashRegister) {
            $cashRegisterService = new CashRegisterService();

            $cashRegisterService->saveEntry(
                cashRegisterId: $cashRegister->id,
                description: "Sales Return",
                cashRegisterBalance: $cashRegister->balance,
                referenceType: SalesReturn::class,
                referenceId: $salesReturn->id,
                credit: $salesReturn->sale_return_amount
            );

            $cashRegisterService->updateBalance($cashRegister, credit: $salesReturn->sale_return_amount);
        }

        $sale->status = "returned";
        $sale->save();

        $account = Account::find($sale->account_id);
        $account->sales_amount -= $salesReturnAmount;
        $account->save();

        DB::commit();

        return response()->json([
            'message'   => 'Sales return created successfully.',
            'data'      => $salesReturn,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SalesReturn  $salesReturn
     * @return \Illuminate\Http\Response
     */
    public function show(SalesReturn $salesReturn)
    {
        $salesReturn->load([
            'sale' => function ($q) {
                $q->select('id', 'account_id', 'date')
                    ->with(['account' => function ($q) {
                        $q->select('id', 'name');
                    }]);
            },
            'salesReturnDetails' => function ($q) {
                $q->select('id', 'sales_return_id', 'product_id', 'price', 'quantity_before_return', 'quantity_return', 'amount')
                    ->with(['product' => function ($q) {
                        $q->select('id', 'name');
                    }]);
            }
        ]);
        return response()->json(['data' => $salesReturn]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SalesReturn  $salesReturn
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SalesReturn $salesReturn)
    {

        // $userId = auth()->user()->id;
        $userId = $salesReturn->created_by;
        $locationId = 1; // will be dynamic
        if (auth()->user()->id != 1) {
            return response()->json([
                "message" => "You are not allowed",
                "errors" => []
            ], 422);
        }

        /* double sales return sale quantity validation */
        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            // 'sales_return_id' => 'required|exists:sales_returns,id',
            'sales_return_details' => 'required|array',
            'sales_return_details.*.id' => 'required|exists:sales_return_details,id',
            'sales_return_details.*.product_id' => 'required|exists:products,id',
            'sales_return_details.*.quantity_return' => 'required|numeric',
        ]);


        // $productsCount = 0;
        // $quantityCount = 0;

        $grossAmount = $salesReturn->sale_return_amount;
        $oldSalesReturnAmount = $salesReturn->sale_return_amount;
        $salesReturnAmount = $grossAmount;

        $salesReturnDetailData = [];

        $sale = Sale::find($request->sale_id);
        $saleDetails = $sale->saleDetails;

        $message = null;
        $errors = [];

        foreach ($request->sales_return_details as $i => $requestSalesReturnDetail) {
            $salesReturnDetail = $salesReturn->salesReturnDetails->where('product_id', $requestSalesReturnDetail['product_id'])
                ->first();


            $saleDetail = $saleDetails->where('product_id', $requestSalesReturnDetail['product_id'])->first();

            $product = Product::find($requestSalesReturnDetail['product_id']);

            /* quantity validation */
            if ($requestSalesReturnDetail['quantity_return'] > $saleDetail->quantity) {
                $errors[] = ['sales_return_details.' . $i . '.quantity_return' => ["The sales_return_details.{$i}.quantity_return must be less than or equal to {$saleDetail->quantity}."]];
                if (!$message) {
                    $message = "The sales_return_details.{$i}.quantity_return must be less than or equal to {$saleDetail->quantity}.";
                }
            }

            if ($message) continue;

            $salesReturnDetailAmount = $requestSalesReturnDetail['quantity_return'] * $saleDetail['price'];

            // $productsCount += 1;
            // $quantityCount += $requestSalesReturnDetail['quantity_return'];

            $grossAmount = $grossAmount - $salesReturnDetail->amount + $salesReturnDetailAmount;

            $salesReturnDetailData[] = [
                'product_id' => $requestSalesReturnDetail['product_id'],
                'price' => $saleDetail['price'],
                'quantity_before_return' => $saleDetail['quantity'],
                'quantity_return' => $requestSalesReturnDetail['quantity_return'],
                'amount' => $salesReturnDetailAmount,
                'sales_return_detail' => $salesReturnDetail,
                'product' => $product
            ];
        }
        unset($i);
        unset($requestSalesReturnDetail);
        unset($salesReturnDetailAmount);
        unset($salesReturnDetail);

        if ($message) {
            return response()->json([
                "message" => $message,
                "errors" => $errors
            ], 422);
        }

        $cashRegisterEntry = CashRegisterEntry::query()
            ->where('reference_type', SalesReturn::class)
            ->where('reference_id', $salesReturn->id)
            ->first();

        $cashRegister = CashRegister::find($cashRegisterEntry->cash_register_id);

        if (!$cashRegister) {
            return response()->json(["message" => "No register found", "errors" => []], 422);
        }

        $salesReturnAmount = $grossAmount;

        $inventoryService = new InventoryService();

        DB::beginTransaction();

        // $salesReturn = SalesReturn::create([
        //     'sale_id' => $sale->id,
        //     'date' => $cashRegister->date,
        //     'sale_amount_before_return' => $sale->net_amount,
        //     'sale_amount_after_return' => $sale->net_amount - $salesReturnAmount,
        //     'sale_return_amount' => $salesReturnAmount,
        //     'created_by' => $userId,
        // ]);

        $salesReturn->sale_amount_after_return = $sale->net_amount - $salesReturnAmount;
        $salesReturn->sale_return_amount = $salesReturnAmount;
        $salesReturn->save();

        /* Sales Return Debit & Cash Credit */
        $debitEntry = JournalEntry::query()
            ->where('reference_id', $salesReturn->id)
            ->where('reference_type', SalesReturn::class)
            ->where('credit', 0)
            ->first();
        $debitEntry->debit = $salesReturnAmount;
        $debitEntry->save();

        $creditEntry = JournalEntry::query()
            ->where('reference_id', $salesReturn->id)
            ->where('reference_type', SalesReturn::class)
            ->where('debit', 0)
            ->first();
        $creditEntry->credit = $salesReturnAmount;
        $creditEntry->save();

        foreach ($salesReturnDetailData as $salesReturnDetailEntry) {
            $salesReturnDetailEntry['sales_return_id'] = $salesReturn->id;

            $saleDetail = $saleDetails->where('product_id', $salesReturnDetailEntry['product_id'])->first();

            $salesReturnDetail = $salesReturnDetailEntry['sales_return_detail'];
            $oldSalesReturnDetailReturnQuantity = $salesReturnDetail['quantity_return'];
            $salesReturnDetail->quantity_return = $salesReturnDetailEntry['quantity_return'];
            $salesReturnDetail->amount = $salesReturnDetailEntry['amount'];
            $salesReturnDetail->save();

            // 'product_id' => $requestSalesReturnDetail['product_id'],
            // 'price' => $saleDetail['price'],
            // 'quantity_before_return' => $saleDetail['quantity'],
            // 'quantity_return' => $requestSalesReturnDetail['quantity_return'],
            // 'amount' => $salesReturnDetailAmount,
            // 'sales_return_detail' => $salesReturnDetail

            $inventoryService->reverseInventoryOnSalesReturn(
                locationId: $locationId,
                salesReturnId: $salesReturn->id,
                productId: $salesReturnDetailEntry['product_id'],
                salesReturnDetailQuantity: $oldSalesReturnDetailReturnQuantity
            );

            $inventoryService->updateInventoryOnSalesReturnUpdate(
                locationId: $locationId,
                saleId: $sale->id,
                salesReturnId: $salesReturn->id,
                salesReturnDate: $salesReturn->date,
                productId: $salesReturnDetailEntry['product_id'],
                salePrice: $saleDetail->price,
                returnedQuantity: $salesReturnDetailEntry['quantity_return']
            );

            $product = $salesReturnDetailEntry['product'];
            $product->quantity = $product->quantity - $oldSalesReturnDetailReturnQuantity + $salesReturnDetail->quantity_return;
            $product->save();
        }

        if ($cashRegister) {
            $cashRegisterService = new CashRegisterService();

            // $cashRegisterEntry = CashRegisterEntry::query()
            //     ->where('reference_type', SalesReturn::class)
            //     ->where('reference_id', $salesReturn->id)
            //     ->first();

            // $previousCashRegisterEntry = CashRegisterEntry::query()
            // ->where('cash_register_id', $cashRegister->id)
            // ->where('id', '<', $cashRegisterEntry->id)
            // ->orderBy('id', 'DESC')
            // ->first();            

            $cashRegisterEntry->credit = $cashRegisterEntry->credit - $oldSalesReturnAmount + $salesReturn->sale_return_amount;
            $cashRegisterEntry->save();

            $cashRegisterService->recalculateBalance(cashRegister: $cashRegister);
        }


        $account = Account::find($sale->account_id);
        $account->sales_amount = $account->sales_amount + $oldSalesReturnAmount - $salesReturnAmount;
        $account->save();

        DB::commit();

        return response()->json([
            'message'   => 'Sales return updated successfully.',
            'data'      => $salesReturn,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SalesReturn  $salesReturn
     * @return \Illuminate\Http\Response
     */
    public function destroy(SalesReturn $salesReturn)
    {
        //
    }

    public function import()
    {
        $lastSalesReturnDate = null;
        $lastSalesReturn = SalesReturn::whereNotNull('ref_id')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSalesReturn) {
            $lastSalesReturnDate = $lastSalesReturn->date;
        }

        $query = DB::connection("mysql2")->table("product_return")
            ->select("invoice_id", "date_purchase", "date_return", "byy_qty", "ret_qty", "product_rate", "total_ret_amount", "net_total_amount");


        // if ($lastId)
        //     $query->where("id", ">", $lastId);


        $query = $query->orderBy('id')->chunk(300, function ($oldSales) {
        });
    }

    public function export(Request $request)
    {
        return Excel::download(new SaleReturnsExport($request), 'sale-returns.xlsx');
    }
}
