<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductInventoryEntry;
use App\Models\ProductInventoryOutflow;
use App\Models\ProductInventoryOutflowDetail;
use App\Models\ProductInventoryPurchase;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $purchases = [
            //paid purchase without discount
            [
                'date' => '2022-01-03',
                'reference_number' => '10000',
                'account_id' => '1',
                'paid_amount' => '',
                'status' => 'final',
                'payment_status' => 'paid',
                'created_by' => 1,
                'purchase_details' => [
                    [
                        'product_id' => '3',
                        'price' => '20',
                        'quantity' => '100',
                        'sale_price' => 30
                    ]
                ],

            ],
            //due purchase with fixed discount
            [
                'date' => '2022-01-05',
                'reference_number' => '10001',
                'account_id' => '1',
                'discount_type' => 'fixed',
                'discount_amount' => '50',
                'discount_rate' => '50',
                'paid_amount' => '',
                'status' => 'received',
                'payment_status' => 'due',
                'created_by' => 1,
                'purchase_details' => [
                    [
                        'product_id' => '2',
                        'price' => '25',
                        'quantity' => '50',
                        'sale_price' => 32
                    ]
                ],
            ],
            //due purchase with percentage discount
            [
                'date' => '2022-01-07',
                'reference_number' => '10002',
                'account_id' => '1',
                'discount_type' => 'percentage',
                'discount_amount' => 400,
                'discount_rate' => 4,
                'paid_amount' => '',
                'status' => 'received',
                'payment_status' => 'due',
                'created_by' => 1,
                'purchase_details' => [
                    [
                        'product_id' => '1',
                        'price' => '10',
                        'quantity' => '1000',
                        'sale_price' => 15
                    ]
                ],
            ],
        ];
        foreach ($purchases as $i => $entry) {
            $purchaseData = Arr::only($entry, [
                'date',
                'reference_number',
                'account_id',
                'discount_type',
                'discount_amount',
                'discount_percentage',
                'paid_amount',
                'payment_method_id',
                'status',
                'payment_status',
                'created_by',
            ]);



            $productsCount = 0;
            $quantityCount = 0;
            $grossAmount = 0;
            $discountAmount = 0;
            $purchaseAmount = 0;
            $purchaseDetailData = [];
            $productInventoryPurchases = []; // used for outflow inventory
            $productInventoryOutflowDetails = []; // used for increasing inventory

            $inventoryService = new InventoryService();

            foreach ($entry['purchase_details'] as $requestPurchaseDetail) {
                $purchaseDetailAmount = $requestPurchaseDetail['quantity'] * $requestPurchaseDetail['price'];

                $productsCount += 1;
                $quantityCount += $requestPurchaseDetail['quantity'];
                $grossAmount += $purchaseDetailAmount;

                $expiryDate = now()->addMonths(6);

                $purchaseDetailData[] = $inventoryService->getPurchaseDetailArray(
                    $requestPurchaseDetail['product_id'],
                    $requestPurchaseDetail['price'],
                    $requestPurchaseDetail['quantity'],
                    $purchaseDetailAmount,
                    null,
                    null,
                    null,
                    null,
                    $requestPurchaseDetail['quantity'],
                    $requestPurchaseDetail['sale_price'],
                    $expiryDate
                );
            }

            // calculate discount amount

            if (isset($purchaseData['discount_type'])) {
                $discountAmount = $purchaseData['discount_amount'];
            }
            $purchaseAmount = $grossAmount - $discountAmount;


            $purchaseData['gross_amount'] = $grossAmount;
            $purchaseData['net_amount'] = $purchaseAmount;
            $purchaseData['products_count'] = $productsCount;

            // paid amount scenarios
            if ($i == 0) {
                $purchaseData['paid_amount'] = $purchaseAmount;
            } else if ($i == 1) {
                $purchaseData['paid_amount'] = $purchaseAmount / 2;
            } else {
                $purchaseData['paid_amount'] = 0;
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
                    'payment_method_id' => '1', //cash will be dynamic
                    'amount' => $purchase->paid_amount,
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
                $balanceAmount = $purchaseAmount - $purchase->paid_amount;

                /* Purchase Debit & Payable Credit */

                $journalEntrySerialNumber = $journalEntryService->getSerialNumber();

                $journalEntryService->recordEntry(
                    $journalEntrySerialNumber,
                    AccountHead::PURCHASE_ID,
                    AccountHead::ACCOUNT_PAYABLE_ID, //cash will be dynamic
                    $balanceAmount,
                    0,
                    $purchase['date'],
                    Purchase::class,
                    $purchase->id
                );
                $journalEntryService->recordEntry(
                    $journalEntrySerialNumber,
                    AccountHead::ACCOUNT_PAYABLE_ID, //cash will be dynamic
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
                $purchaseDetail = PurchaseDetail::create($purchaseDetailEntry);

                /* purchased inventory for centralized db */
                $productInventoryPurchased = ProductInventoryPurchase::create([
                    'product_id' => $purchaseDetail['product_id'],
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'purchased_price' => $purchaseDetail['price'],
                    'purchased_quantity' => $purchaseDetail['quantity'],
                    'available_quantity' => $purchaseDetail['quantity'],
                    'expiry_date' => $purchaseDetail['expiry_date']
                ]);

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
                $productInventoryEntry = $inventoryService->storeInventoryEntryOnPurchase($productInventoryOutflow->date, $productInventoryOutflowDetail['product_id'], $productInventoryOutflowDetail['product_inventory_outflow_id'], ProductInventoryOutflow::class, $productInventoryOutflowDetail->productInventoryPurchase->purchased_price, $productInventoryOutflowDetail['quantity'], $productInventoryOutflowDetail->productInventoryPurchase->expiry_date);

                /* Update product quantity */
                $product = Product::find($productInventoryOutflowDetail['product_id']);
                $inventoryService->updateProductPriceQuantityOnPurchase($product, $purchaseDetail->sale_price, $productInventoryEntry['initial_quantity']);
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

            DB::commit();
        }
    }
}
