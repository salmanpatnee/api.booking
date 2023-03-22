<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductInventoryEntry;
use App\Models\ProductInventoryOutflow;
use App\Models\ProductInventoryOutflowDetail;
use App\Models\ProductInventoryPurchase;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FixPurchasePriceController extends Controller
{
    public function __invoke()
    {
        $row = [
            'ref_id' => "2356575678345623",
            'unit_cost' => 1.96,
            'unit_price' => 2.35,
            'uom_of_boxes' => 200,
            'discount_on_cash' => null,
            'discount_on_card' => null,
            'discount_on_delivery' => null,
        ];
        $product = Product::where("ref_id", $row['ref_id'])->first();

        if ($product) {
            // dd($product, $row);    

            $inventoryService = new InventoryService();

            DB::beginTransaction();



            $productInventory = DB::table('opharma_products_inventory')
                ->where('product_id', $product->ref_id)
                ->update([
                    'default_purchase_price' => $row['unit_cost'],
                    'default_selling_price' => $row['unit_price'],
                    'uom_of_boxes' => $row['uom_of_boxes'],
                ]);

            $productInventory = DB::table('opharma_products_inventory')
                ->where('product_id', $product->ref_id)
                ->first();

            // dd($product, $productInventory);

            $purchase = Purchase::find(1);

            $grossAmount = $purchase->gross_amount;
            $purchaseAmount = $purchase->net_amount;

            $purchaseDetail = PurchaseDetail::query()
                ->where('purchase_id', $purchase->id)
                ->where('product_id', $product->id)
                ->first();

            $purchaseDetailAmount = $productInventory->default_purchase_price * $purchaseDetail->quantity;

            $grossAmount += $purchaseDetailAmount;

            $purchaseAmount = $grossAmount;


            $purchasePrice = $productInventory->default_purchase_price;

            $purchase->gross_amount = $grossAmount;
            $purchase->net_amount = $purchaseAmount;

            $purchase->save();

            // because 0 paid amount
            // $balanceAmount = $purchaseAmount - $purchase->paid_amount;
            $balanceAmount = $purchaseAmount;

            $debitEntry = JournalEntry::query()
                ->where('reference_id', $purchase->id)
                ->where('reference_type', Purchase::class)
                ->where('credit', 0)
                ->first();
            $debitEntry->debit = $balanceAmount;
            $debitEntry->save();

            $creditEntry = JournalEntry::query()
                ->where('reference_id', $purchase->id)
                ->where('reference_type', Purchase::class)
                ->where('debit', 0)
                ->first();
            $creditEntry->credit = $balanceAmount;
            $creditEntry->save();

            $profitMargin = $inventoryService->getProfitMargin($purchasePrice, $purchaseDetail->sale_price);

            $purchaseDetail->price = $purchasePrice;
            $purchaseDetail->amount = $purchasePrice * $purchaseDetail->quantity;
            $purchaseDetail->profit_margin = $profitMargin;
            $purchaseDetail->save();

            $productInventoryPurchase = ProductInventoryPurchase::query()
                ->where('reference_id', $purchase->id)
                ->where('reference_type', Purchase::class)
                ->where('product_id', $product->id)
                ->first();

            $productInventoryPurchase->purchased_price = $purchasePrice;
            $productInventoryPurchase->save();

            $productInventoryOutflowDetail = ProductInventoryOutflowDetail::query()
                ->where('product_id', $product->id)
                ->where('product_inventory_purchase_id', $productInventoryPurchase->id)
                ->first();

            $productInventoryEntry = ProductInventoryEntry::query()
                ->where('reference_id', $productInventoryOutflowDetail->product_inventory_outflow_id)
                ->where('reference_type', ProductInventoryOutflow::class)
                ->where('product_id', $product->id)
                ->first();

            $productInventoryEntry->purchased_price = $purchasePrice;
            $productInventoryEntry->purchased_amount = $purchasePrice * $purchaseDetail->quantity;
            $productInventoryEntry->save();

            $account = Account::find($purchase->account_id);
            $account->purchases_amount = $purchaseAmount;
            $account->balance = $purchase->net_amount;
            $account->save();

            $productInventorySaleEntries = ProductInventoryEntry::query()
                ->where('reference_type', Sale::class)
                ->where('product_id', $product->id)
                ->where('product_inventory_entry_purchase_id', $productInventoryEntry->id)
                ->get();

            foreach ($productInventorySaleEntries as $productInventorySaleEntry) {
                $sale = Sale::find($productInventorySaleEntry->reference_id);

                $saleDetail = SaleDetail::query()
                    ->where('sale_id', $sale->id)
                    ->where('product_id', $product->id)
                    ->first();

                $saleDetail->purchase_amount = $saleDetail->quantity * $purchasePrice;
                $saleDetail->save();

                $sale->purchase_amount += $saleDetail->purchase_amount;
                $sale->save();
            }

            DB::commit();

            return response()->json([
                'message'   => 'Purchase price fixed successfully.',
                'data'      => [],
                'status'    => 'success'
            ], Response::HTTP_OK);
        }
    }
}
