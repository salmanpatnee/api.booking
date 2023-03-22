<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductInventoryOutflow;
use App\Models\ProductInventoryOutflowDetail;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DummyPurchaseController extends Controller
{
    public function index()
    {
        ini_set('max_execution_time', 900); // 900 (seconds) = 15 Minutes
        $supplier = Account::find(Account::OLD_PHARMA_SUPPLIER_ID);

        $purchaseData = [
            'date' => "2022-01-01",

            'account_id' => $supplier->id,
            'reference_number' => "DUMMY-1",
            'paid_amount' => 0,
            'discount_type' => null,
            'discount_amount' => null,
            'discount_percentage' => null,
            'status' => 'received',

            'remarks' => "Initial Inventory",

            'ref_id' => null,
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

        $deletedProducts = [1355698764, "CGYYGY", "271522160", "8587591147", "6221155046581", "2742678133"];

        // $tempVariable = [396, 975, 7482, 7901, 9109];

        Product::select(
            "id",
            "name",
            "category_id",
            "brand_id",
            "quantity",
            "default_purchase_price",
            "default_selling_price",
            "ref_id"
        )
            ->with([
                'category' => function ($q) {
                    $q->select('id', 'name');
                },
                'brand' => function ($q) {
                    $q->select('id', 'name');
                },
            ])
            ->whereNotIn('ref_id', $deletedProducts)
            // ->whereNotIn('id', $tempVariable)

            ->orderBy('id', 'ASC')
            ->chunk(500, function ($products) use (
                &$productsCount,
                &$quantityCount,
                &$grossAmount,
                &$inventoryService,
                &$purchaseDetailData,
                &$purchaseOrderDetailsQuantitySent,
            ) {
                foreach ($products as $product) {

                    $productInventory = DB::table('opharma_products_inventory')
                        ->where('product_id', $product->ref_id)
                        ->first();
                    if (!$productInventory) dd($product, $productInventory);

                    if ($productInventory->available_quantity == 0) continue;

                    $purchaseDetailAmount = $productInventory->default_purchase_price * $productInventory->available_quantity;

                    $productsCount += 1;
                    $quantityCount += $productInventory->available_quantity;
                    $grossAmount += $purchaseDetailAmount;

                    $expiryDate = null;

                    // dd($oldPurchaseDetail, var_dump($oldPurchaseDetail->unit_cost), var_dump($oldPurchaseDetail->retail_per_unit_price));
                    $purchasePrice = null;
                    if (!empty($productInventory->default_purchase_price)) {
                        $purchasePrice = $productInventory->default_purchase_price;
                    } else if (!empty($product->default_purchase_price)) {
                        $purchasePrice = $product->default_purchase_price;
                    } else {
                        $purchasePrice = $productInventory->default_selling_price * .80;
                    }

                    $salePrice = null;
                    if (!empty($productInventory->default_selling_price)) {
                        $salePrice = $productInventory->default_selling_price;
                    } else if (!empty($product->default_selling_price)) {
                        $salePrice = $product->default_selling_price;
                    } else {
                        continue;
                    }

                    // if (empty($productInventory->default_selling_price)) dd($product, $productInventory);
                    $purchaseDetailData[] = $inventoryService->getPurchaseDetailArray(
                        $product->id,
                        $purchasePrice,
                        $productInventory->available_quantity,
                        $purchaseDetailAmount,
                        null,
                        null,
                        null,
                        null,
                        $productInventory->available_quantity,
                        $salePrice,
                        $expiryDate
                    );

                    $purchaseOrderDetailsQuantitySent[$product->id] = $productInventory->available_quantity;
                }
            });

        $purchaseAmount = $grossAmount;



        $purchaseData['gross_amount'] = $grossAmount;
        $purchaseData['net_amount'] = $purchaseAmount;
        $purchaseData['products_count'] = $productsCount;
        $purchaseData['created_by'] = 1; //will be dynamic
        $purchaseData['payment_status'] = 'due';

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
                $productInventoryEntry['initial_quantity']
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

        DB::commit();
    }
}
