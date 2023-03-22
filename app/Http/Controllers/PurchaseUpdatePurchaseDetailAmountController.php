<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\ProductInventoryEntry;
use App\Models\ProductInventoryOutflow;
use App\Models\ProductInventoryOutflowDetail;
use App\Models\ProductInventoryPurchase;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PurchaseUpdatePurchaseDetailAmountController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Purchase $purchase, Request $request)
    {
        $purchaseDetails = $purchase->purchaseDetails;

        $oldPurchaseNetAmount = $purchase->net_amount;
        $oldQuantityCount = $purchaseDetails->sum('quantity');
        $oldRemainingAmount = $oldPurchaseNetAmount - $purchase->paid_amount;

        $userId = auth()->user()->id;

        $request->validate([
            'product_inventory_outflow_id' => 'required',
            'purchase_details' => 'required|array',
            'purchase_details.*.id' => 'required|exists:purchase_details,id',
            'purchase_details.*.product_id' => 'required|exists:products,id',
            'purchase_details.*.price' => 'required|numeric',
            'purchase_details.*.quantity' => 'required|numeric',
            'purchase_details.*.amount' => 'required|numeric',
            'purchase_details.*.quantity_boxes' => 'nullable|numeric',
            'purchase_details.*.units_in_box' => 'nullable|numeric',
            // 'purchase_details.*.sale_price_box' => 'nullable|numeric',
            'purchase_details.*.quantity_strips' => 'nullable|numeric',
            'purchase_details.*.units_in_box' => 'nullable|numeric',
            // 'purchase_details.*.sale_price_strip' => 'nullable|numeric',
            'purchase_details.*.sale_price' => 'required|numeric|gt:purchase_details.*.price',
            'purchase_details.*.expiry_date' => 'required|date|after_or_equal:' . now()->addMonths(1)->format("Y-m-d"),
        ]);

        $inventoryService = new InventoryService();
        $journalEntryService = new JournalEntryService();

        

        $productsCount = $purchaseDetails->count();
        $quantityCount = $oldQuantityCount;

        $grossAmount = $purchaseDetails->sum('amount');

        $purchaseAmount = 0;

        $purchaseDetailData = [];
        $productInventoryPurchases = []; // used for outflow inventory
        $productInventoryOutflowDetails = []; // used for increasing inventory

        foreach ($request->purchase_details as $i => $requestPurchaseDetail) {
            $purchaseDetail = PurchaseDetail::find($requestPurchaseDetail['id']);

            $purchaseDetailAmount = $requestPurchaseDetail['amount'];

            $quantityCount = $quantityCount - $purchaseDetail->quantity + $requestPurchaseDetail['quantity'];
            $grossAmount = $grossAmount - $purchaseDetail->amount + $purchaseDetailAmount;

            $expiryDate = isset($requestPurchaseDetail['expiry_date']) ? $requestPurchaseDetail['expiry_date'] : null;

            $purchaseDetailData[$i] = $inventoryService->getPurchaseDetailArray(
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

            $purchaseDetailData[$i]['purchase_detail'] = $purchaseDetail;

            $purchaseOrderDetailsQuantitySent[$requestPurchaseDetail['product_id']] = $requestPurchaseDetail['quantity'];
        }
        unset($purchaseDetail);
        unset($i);

        $purchaseAmount = $grossAmount;

        // calculate discount amount
        if (isset($purchase->discount_amount)) {
            $purchaseAmount = $grossAmount - $purchase->discount_amount;
        }

        // calculate tax amount
        if (isset($purchase->tax_amount)) {
            $purchaseAmount = $purchaseAmount + $purchase->tax_amount;
        }

        // return ['quantity_count' => $quantityCount, 'gross_amount' => $grossAmount, 'net_amount' => $purchaseAmount, 'purchase_details' => $purchaseDetailData];

        $purchase->updated_by = $userId;
        $purchase->gross_amount = $grossAmount;
        $purchase->net_amount = $purchaseAmount;

        if($oldPurchaseNetAmount == $purchase->paid_amount) {
            $purchase->paid_amount = $purchase->net_amount;
        }
        
        DB::beginTransaction();

        $purchase->save();

        /* Add payment entry if paid amount > 0 */
        if ($purchase->paid_amount > 0) {
            $payments = Payment::where('purchase_id', $purchase->id)->get();
            if ($payments->count() == 1 && $payments[0]->amount == $oldPurchaseNetAmount) {
                $payment = $payments[0];
                $payment->amount = $purchase->net_amount;
                $payment->save();

                /* update journal entries if required */
                $debitEntry = JournalEntry::query()
                    ->where('reference_id', $payment->id)
                    ->where('reference_type', Payment::class)
                    ->where('credit', 0)
                    ->first();
                $debitEntry->debit = $payment->amount;
                $debitEntry->save();

                $creditEntry = JournalEntry::query()
                    ->where('reference_id', $payment->id)
                    ->where('reference_type', Payment::class)
                    ->where('debit', 0)
                    ->first();
                $creditEntry->credit = $payment->amount;
                $creditEntry->save();
            }
        }

        foreach ($purchaseDetailData as $purchaseDetailEntry) {
            $purchaseDetail = $purchaseDetailEntry['purchase_detail'];

            // $requestPurchaseDetail['product_id'],
            //     $requestPurchaseDetail['price'],
            //     $requestPurchaseDetail['quantity'],
            //     $purchaseDetailAmount,
            //     $requestPurchaseDetail['quantity_boxes'],
            //     $requestPurchaseDetail['units_in_box'],
            //     $requestPurchaseDetail['quantity_strips'],
            //     $requestPurchaseDetail['units_in_strip'],
            //     $requestPurchaseDetail['quantity_units'],
            //     $requestPurchaseDetail['sale_price'],
            //     $expiryDate,
            //     totalSalePrice: $requestPurchaseDetail['total_sale_price'],
            //     uomOfBoxes: $requestPurchaseDetail['units_in_box'],
            //     boxSalePrice: $requestPurchaseDetail['box_sale_price'],
            
            $purchaseDetail->profit_margin = $inventoryService->getProfitMargin($purchaseDetailEntry['price'], $purchaseDetailEntry['sale_price']);
            $purchaseDetail->price = $purchaseDetailEntry['price'];
            $purchaseDetail->amount = $purchaseDetailEntry['amount'];
            $purchaseDetail->save();

            $productPurchaseEntry = ProductInventoryPurchase::query()
            ->where('reference_id', $purchase->id)
            ->where('reference_type', Purchase::class)
            ->where('product_id', $purchaseDetail->product_id)
            ->first();

            $productPurchaseEntry->purchased_price = $purchaseDetail->price;
            $productPurchaseEntry->save();

            /* add in array for outflow */
            $productInventoryPurchases[] = $productPurchaseEntry;
        }

        $productInventoryOutflow = ProductInventoryOutflow::find($request->product_inventory_outflow_id);
        $productInventoryOutflow->outflow_quantity = $quantityCount;
        $productInventoryOutflow->save();

        foreach ($productInventoryPurchases as $productInventoryPurchase) {
            $productInventoryOutflowDetail = ProductInventoryOutflowDetail::query()
            ->where('product_id', $productInventoryPurchase->product_id)
            ->where('product_inventory_outflow_id', $productInventoryOutflow->id)
            ->where('product_inventory_purchase_id', $productInventoryPurchase->id)
            ->first();

            // $productInventoryOutflowDetail->quantity = $productInventoryPurchase->quantity;
            $productInventoryOutflowDetail->save();

            $productInventoryOutflowDetails[] = $productInventoryOutflowDetail;

            /* decreased available quantity */
            // $productInventoryPurchase->available_quantity -= $productInventoryPurchase['available_quantity'];
            // $productInventoryPurchase->save();
        }
        unset($productInventoryPurchase);

        foreach ($productInventoryOutflowDetails as $productInventoryOutflowDetail) {
            $productInventoryEntry = ProductInventoryEntry::query()
            ->where('product_id', $productInventoryOutflowDetail->product_id)
            ->where('reference_id', $productInventoryOutflowDetail->product_inventory_outflow_id)
            ->where('reference_type', ProductInventoryOutflow::class)
            ->first();

            $productInventoryPurchase = $productInventoryOutflowDetail->productInventoryPurchase;

            $purchaseDetailAr = Arr::first($purchaseDetailData, function ($value, $key) use ($productInventoryOutflowDetail) {
                return $value['product_id'] == $productInventoryOutflowDetail['product_id'];
            });
            $purchaseDetail = $purchaseDetailAr['purchase_detail'] ;

            $productInventoryEntry->purchased_price = $productInventoryPurchase->purchased_price;
            $productInventoryEntry->purchased_amount = $purchaseDetail->amount;
            $productInventoryEntry->save();

            // $product = Product::find($productInventoryOutflowDetail->product_id);
            // updateProductPriceQuantityOnPurchase
        }

        $account = Account::find($purchase->account_id);
        $account->purchases_amount = $account->purchases_amount - $oldPurchaseNetAmount + $purchaseAmount;

        /* check if account has balance */
        if ($purchase->payment_status == 'due') {
            $remainingAmount = $purchase->net_amount - $purchase->paid_amount;
            
            $account->balance = $account->balance - $oldRemainingAmount + $remainingAmount;
        }

        $account->save();

        DB::commit();

        return response()->json([
            'message'   => 'Purchase purchase detail updated successfully.',
            'data'      => $purchase,
            'status'    => 'success'
        ], Response::HTTP_OK);
    }
}
