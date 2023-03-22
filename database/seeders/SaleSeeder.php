<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Services\InventoryService;
use App\Services\JournalEntryService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sales = [
            [
                'date' => '2022-01-11',
                'account_id' => 2,
                'reference_number' => '111111111',
                'sale_details' => [
                    [
                        "product_id" => 2,
                        "price" => 30,
                        "quantity" => 25
                    ]
                ]
            ],
            [
                'date' => '2022-01-13',
                'account_id' => 2,
                'reference_number' => '111111112',
                'sale_details' => [
                    [
                        "product_id" => 3,
                        "price" => 24,
                        "quantity" => 100
                    ]
                ]
            ],
            [
                'date' => '2022-01-14',
                'account_id' => 2,
                'reference_number' => '111111113',
                'sale_details' => [
                    [
                        "product_id" => 1,
                        "price" => 9,
                        "quantity" => 100
                    ]
                ]
            ]
        ];

        foreach ($sales as $i => $entry) {
            $locationId = 1;

            $saleData = Arr::only($entry, [
                'date',
                'account_id',
                'reference_number'
            ]);

            $productsCount = 0;
            $saleAmount = 0;
            $products = [];

            $saleDetailData = [];

            foreach ($entry['sale_details'] as $i => $requestSaleDetail) {
                $products[$i] = Product::find($requestSaleDetail['product_id']);

                /* quantity validation */
                if ($requestSaleDetail['quantity'] > $products[$i]->quantity) {
                    $errors[] = ['sale_details.' . $i . '.quantity' => ["The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}"]];
                }

                $saleDetailAmount = $requestSaleDetail['quantity'] * $requestSaleDetail['price'];

                $productsCount += 1;
                $saleAmount += $saleDetailAmount;

                $saleDetailData[] = [
                    'product_id' => $requestSaleDetail['product_id'],
                    'price' => $requestSaleDetail['price'],
                    'quantity' => $requestSaleDetail['quantity'],
                    'amount' => $saleDetailAmount
                ];
            }

            if (!empty($errors)) {
                return response()->json(["message" => "The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}", "errors" => $errors], 422);
            }

            $saleData['gross_amount'] = $saleAmount;
            $saleData['net_amount'] = $saleAmount;
            $saleData['products_count'] = $productsCount;
            $saleData['location_id'] = $locationId;
            $saleData['payment_method_id'] = 1; //cash id will be dynamic
            $saleData['created_by'] = 1; //created_by will be dynamic

            $journalEntryService = new JournalEntryService();
            $inventoryService = new InventoryService();

            DB::beginTransaction();

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
                $saleDetail = SaleDetail::create($saleDetailEntry);

                $inventoryService->updateInventoryOnSale($sale->id, $sale->date, $saleDetail['product_id'], $saleDetail['quantity']);
                $inventoryService->updateProductQuantityOnSale($products[$i], $saleDetail['quantity']);
            }

            $account = Account::find($sale->account_id);
            $account->sales_amount += $saleAmount;
            $account->sales_count += 1;
            $account->save();

            DB::commit();
        }
    }
}
