<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\Receipt;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate = request('paginate', 10);
        // $term     = request('search', '');
        // $sortOrder     = request('sortOrder', 'desc');
        // $orderBy       = request('orderBy', 'created_at');

        $purchaseReturns = PurchaseReturn::select(
            'id',
            'purchase_id',
            'date',
            'purchase_amount_before_return',
            'purchase_amount_after_return',
            'purchase_return_amount',
            'received_amount',
            'payment_status'
        )

            ->with([
                'purchase' => function ($q) {
                    $q->select('id', 'reference_number', 'account_id', 'date')
                        ->with(['account' => function ($q) {
                            $q->select('id', 'name');
                        }]);
                },
            ])
            ->orderBy('date')
            ->paginate($paginate);
        return response()->json(['data' => $purchaseReturns]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $purchase = null;
        $createdBy = auth()->user()->id;
        /* custom validation for preventing return if status is final */
        try {
            $purchase = Purchase::findOrFail($request->purchase_id);
        } catch (\Throwable $th) {
            if ($th instanceof ModelNotFoundException) {
                return response()->json([
                    "message" => "The selected purchase id is invalid.",
                    "errors" => [['purchase_id' => ["The selected purchase id is invalid."]]],
                ], 422);
            }
        }

        $request->request->add(['purchase_status' => $purchase->status]); //add request
        $request->request->add(['purchase_date' => $purchase->date]); //add request

        $data = $request->validate([
            'purchase_id' => 'required',
            'purchase_status' => Rule::notIn([Purchase::PURCHASE_STATUS_FINAL]),
            'date' => 'required|date|after_or_equal:purchase_date',
            'received_amount' => 'required|numeric',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'purchase_return_details' => 'required|array',
            'purchase_return_details.*.product_id' => 'required|exists:products,id',
            'purchase_return_details.*.quantity_return' => 'required|numeric',
            'purchase_return_details.*.amount' => 'required|numeric',
        ]);

        $locationId = 1; // will get from session

        $purchaseReturnData = $request->only([
            'purchase_id',
            'purchase_status',
            'date',
            'received_amount',
        ]);

        // $data = $request->only([
        //     'date',

        //     'gross_amount',
        //     'net_amount',

        //     'account_id',
        //     'reference_number',
        //     'paid_amount',
        //     'discount_type',
        //     'discount_amount',
        //     'discount_percentage',
        //     'status',

        //     'purchase_order_id'
        // ]);

        $productsCount = 0;
        $quantityCount = 0;

        $grossAmount = 0;
        $purchaseReturnAmount = 0;

        $purchaseReturnDetailData = [];

        $purchaseDetails = $purchase->purchaseDetails;

        $message = null;
        $errors = [];



        /* product_id=>quantity_sent */


        $inventoryService = new InventoryService();

        foreach ($request->purchase_return_details as $i => $requestPurchaseReturnDetail) {
            $purchaseDetail = $purchaseDetails->where('product_id', $requestPurchaseReturnDetail['product_id'])->first();

            /* quantity validation */
            if ($requestPurchaseReturnDetail['quantity_return'] > $purchaseDetail->quantity) {
                $errors[] = ['purchase_return_details.' . $i . '.quantity_return' => ["The purchase_return_details.{$i}.quantity_return must be less than or equal to {$purchaseDetail->quantity}."]];
                if (!$message) {
                    $message = "The purchase_return_details.{$i}.quantity_return must be less than or equal to {$purchaseDetail->quantity}.";
                }
            }

            if ($message) continue;

            // $purchaseDetailAmount = $requestPurchaseDetail['quantity'] * $requestPurchaseDetail['price'];
            $purchaseReturnDetailAmount = $requestPurchaseReturnDetail['amount'];

            $productsCount += 1;
            $quantityCount += $requestPurchaseReturnDetail['quantity_return'];

            $grossAmount += $purchaseReturnDetailAmount;


            /* $purchaseDetail->quantity should be changed when a purchase can returned more than 1 time */

            $purchaseReturnDetailData[] = [
                "product_id" => $requestPurchaseReturnDetail['product_id'],
                "price" => $purchaseDetail->price, //price
                "quantity_before_return" => $purchaseDetail->quantity, //quantity when purchase made
                "quantity_return" => $requestPurchaseReturnDetail['quantity_return'],
                "amount" => $purchaseReturnDetailAmount
            ];
        }

        if ($message) {
            return response()->json([
                "message" => $message,
                "errors" => $errors
            ], 422);
        }

        $purchaseReturnAmount = $grossAmount;



        // calculate discount amount
        // if (isset($purchaseData['discount_amount'])) {
        //     $purchaseReturnAmount = $grossAmount - $purchaseData['discount_amount'];
        // }


        $purchaseReturnData['account_id'] = $purchase->account_id;
        $purchaseReturnData['purchase_amount_before_return'] = $purchase->net_amount;
        $purchaseReturnData['purchase_amount_after_return'] = $purchase->net_amount - $purchaseReturnAmount;
        $purchaseReturnData['purchase_return_amount'] = $purchaseReturnAmount;
        $purchaseReturnData['products_count'] = $productsCount;
        $purchaseReturnData['created_by'] = $createdBy;
        $purchaseReturnData['payment_status'] = $purchaseReturnAmount == $purchaseReturnData['received_amount'] ? 'received' : 'due';

        $journalEntryService = new JournalEntryService();

        DB::beginTransaction();

        $purchaseReturn = PurchaseReturn::create($purchaseReturnData);

        /* purchase return 2900 credit entry */
        /* cash debit 1500 */
        /* payable 1400 */

        /* purchase return 2142 credit entry */
        /* cash debit 742 or recivable */
        /* payable 1400 debit */

        /* purchase return 428.4 credit entry */
        /* payable 428.4 debit */

        /* Add payment entry if paid amount > 0 */
        if ($purchaseReturn->received_amount > 0) {
            $receipt = Receipt::create([
                'date' => $purchaseReturn->date,
                'account_id' => $purchase->account_id,
                'purchase_return_id' => $purchaseReturn->id,
                'amount' => $purchaseReturn->received_amount,
                'payment_method_id' => $request->payment_method_id,
                'created_by' => $createdBy
            ]);

            /* Cash Debit & Purchase Return Credit */
            $journalEntrySerialNumber = $journalEntryService->getSerialNumber();
            $journalEntryService->recordEntry(
                $journalEntrySerialNumber,
                AccountHead::CASH_ID, //cash will be dynamic
                AccountHead::PURCHASE_RETURN_ID,
                $purchaseReturn->received_amount,
                0,
                $purchaseReturn->date,
                Receipt::class,
                $receipt->id
            );
            $journalEntryService->recordEntry(
                $journalEntrySerialNumber,
                AccountHead::PURCHASE_RETURN_ID,
                AccountHead::CASH_ID, //cash will be dynamic
                0,
                $purchaseReturn->received_amount,
                $purchaseReturn->date,
                Receipt::class,
                $receipt->id
            );
        }

        /* Add receivable entry if amount is not full paid  */
        if ($purchaseReturn->received_amount != $purchaseReturnAmount) {
            //     /* Purchase Debit & Payable Credit */

            $journalEntrySerialNumber = $journalEntryService->getSerialNumber();
            $balanceAmount = $purchaseReturnAmount - $purchaseReturn->received_amount;

            $journalEntryService->recordEntry(
                $journalEntrySerialNumber,
                AccountHead::ACCOUNT_RECEIVABLE_ID,
                AccountHead::PURCHASE_RETURN_ID,
                $balanceAmount,
                0,
                $purchase['date'],
                Purchase::class,
                $purchase->id
            );
            $journalEntryService->recordEntry(
                $journalEntrySerialNumber,
                AccountHead::PURCHASE_RETURN_ID,
                AccountHead::ACCOUNT_RECEIVABLE_ID,
                0,
                $balanceAmount,
                $purchase['date'],
                Purchase::class,
                $purchase->id
            );
        }

        foreach ($purchaseReturnDetailData as $purchaseReturnDetailEntry) {
            $purchaseReturnDetailEntry['purchase_return_id'] = $purchaseReturn->id;

            $purchaseDetail = $purchaseDetails->where('product_id', $purchaseReturnDetailEntry['product_id'])->first();
            $purchaseReturnDetail = PurchaseReturnDetail::create($purchaseReturnDetailEntry);

            /* purchase return will be from headquarter not from branch */

            /* reverse inventory */
            $inventoryService->updateInventoryOnPurchaseReturn(
                $locationId,
                $purchase->id,
                $purchaseReturnDetailEntry['product_id'],
                $purchaseReturnDetailEntry['quantity_return'],
                $purchaseReturnDetailEntry['amount']
            );
            $inventoryService->updateProductQuantityOnPurchaseReturn($purchaseReturnDetailEntry['product_id'], $purchaseReturnDetailEntry['quantity_return']);
        }

        $purchase->status = 'returned';
        $purchase->save();

        $account = Account::find($purchase->account_id);
        $account->purchases_amount -= $purchaseReturnAmount;

        /* check if account has balance */
        if ($purchaseReturn->payment_status == 'due') {
            $account->balance = $account->balance - $purchaseReturnAmount + $purchaseReturn->received_amount;
        }

        $account->save();

        DB::commit();

        return response()->json([
            'message'   => 'Purchase return created successfully.',
            'data'      => $purchaseReturn,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchaseReturn  $purchaseReturn
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load([
            'purchase' => function ($q) {
                $q->select('id', 'account_id', 'date')
                    ->with(['account' => function ($q) {
                        $q->select('id', 'name');
                    }]);
            },
            'purchaseReturnDetails' => function ($q) {
                $q->select('id', 'purchase_return_id', 'product_id', 'price', 'quantity_before_return', 'quantity_return', 'amount')
                    ->with(['product' => function ($q) {
                        $q->select('id', 'name');
                    }]);
            }
        ]);
        return response()->json(['data' => $purchaseReturn]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PurchaseReturn  $purchaseReturn
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PurchaseReturn $purchaseReturn)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseReturn  $purchaseReturn
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseReturn $purchaseReturn)
    {
        //
    }
}
