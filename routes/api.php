<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountHeadController;
use App\Http\Controllers\AdjustmentEntryController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\BankAccountController;
// use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\BankCardController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BankOfferController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DaySummaryController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\DummyPurchaseController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\FixPurchasePriceController;
use App\Http\Controllers\ImportOldDatabaseController;
use App\Http\Controllers\ImportOldInventoryReportController;
use App\Http\Controllers\ImportOldUomController;
use App\Http\Controllers\ImportProductsDiscountController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LossReportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductExchangeController;
use App\Http\Controllers\ProductInventoryEntryController;
use App\Http\Controllers\ProductStockDetailController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\PurchaseUpdatePurchaseDetailAmountController;
use App\Http\Controllers\PurchaseUpdateSupplierController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleBankOfferController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleCustomerController;
use App\Http\Controllers\SaleOrderedController;
use App\Http\Controllers\SaleShipmentController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\StockTransferCanttController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierLedgerController;
use App\Http\Controllers\TodaysReportController;
use App\Http\Controllers\TodaysSaleController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('dashboard', DashboardController::class);
    Route::apiResource('locations', LocationController::class)->only('index', 'store', 'show', 'update');

    Route::apiResource('account-heads', AccountHeadController::class)->only('index', 'show');
    Route::apiResource('accounts', AccountController::class);
    Route::get('/trial-balance', TrialBalanceController::class);

    Route::apiResource('expense-types', ExpenseTypeController::class);
    Route::apiResource('expenses', ExpenseController::class);

    Route::get('categories/import', [CategoryController::class, 'import']);
    Route::apiResource('categories', CategoryController::class);

    Route::get('brands/import', [BrandController::class, 'import']);
    Route::apiResource('brands', BrandController::class);

    Route::get('products/export', [ProductController::class, 'export']);
    Route::get('products/import', [ProductController::class, 'import']);
    Route::post('products/excel-import', [ProductController::class, 'excelImport']);
    Route::get('products/fix-quantity', [ProductController::class, 'fixQuantity']);
    Route::get('products/fix-purchase-price', FixPurchasePriceController::class);
    Route::apiResource('products', ProductController::class);

    Route::get('purchases/import', [PurchaseController::class, 'import']);
    Route::put('purchases/{purchase}/update-supplier', PurchaseUpdateSupplierController::class);
    Route::put('purchases/{purchase}/update-purchase-details-amount', PurchaseUpdatePurchaseDetailAmountController::class);
    Route::apiResource('purchases', PurchaseController::class);
    Route::apiResource('purchase-returns', PurchaseReturnController::class);

    Route::get('sales/export', [SaleController::class, 'export']);
    Route::get('sales/import', [SaleController::class, 'import']);
    Route::apiResource('sales', SaleController::class);
    Route::apiResource('sales-shipment', SaleShipmentController::class)->only("update");
    Route::apiResource('sales.bank-offers', SaleBankOfferController::class)->shallow()->only('store');
    Route::apiResource('sales-ordered', SaleOrderedController::class)->only('show');
    Route::apiResource('sales/customers', SaleCustomerController::class)->only('store');
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('bank-accounts', BankAccountController::class);

    Route::apiResource('purchase-orders', PurchaseOrderController::class);

    Route::apiResource('suppliers/{supplier}/ledger', SupplierLedgerController::class)->only(['index']);
    Route::get('suppliers/import', [SupplierController::class, 'import']);
    Route::apiResource('suppliers', SupplierController::class)->except(['destroy']);

    Route::get('customers/export', [CustomerController::class, 'export']);
    Route::get('customers/import', [CustomerController::class, 'import']);
    Route::apiResource('customers', CustomerController::class)->except(['destroy']);

    Route::get('sales-returns/export', [SalesReturnController::class, 'export']);
    Route::get('sales-returns/import', [SalesReturnController::class, 'import']);
    Route::apiResource('sales-returns', SalesReturnController::class);

    Route::apiResource('product-inventory-entries', ProductInventoryEntryController::class);

    Route::apiResource('discounts', DiscountController::class)->only('index', 'store');

    Route::apiResource('product-exchanges', ProductExchangeController::class)->only('index', 'store', 'show');

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('users', UserController::class);

    Route::apiResource('attendances', AttendanceController::class)->only('index', 'store');

    Route::apiResource('products/{product}/stock-details', ProductStockDetailController::class)->shallow('index');

    Route::apiResource('adjustment-entries', AdjustmentEntryController::class)->only('store');

    Route::apiResource('banks', BankController::class)->only('index');

    Route::apiResource('bank-cards', BankCardController::class)->only('index');

    Route::apiResource('bank-offers', BankOfferController::class)->only('index');


    // middleware('auth:api')->
    // Route::get('/cash-registers/me', [CashRegisterController::class, 'show']);
    Route::apiResource('cash-registers', CashRegisterController::class)->only('index', 'store', 'show', 'update');

    Route::apiResource('stock-transfers/cantt', StockTransferCanttController::class)->only('store');

    Route::get('/day-summary', [DaySummaryController::class, 'index']);

    Route::prefix("reports")->group(function () {
        Route::get('todays-report', [TodaysReportController::class, 'index']);

        Route::get('loss-report', [LossReportController::class, 'index']);
    });

    Route::get('todays-sales', [TodaysSaleController::class, 'index']);

    Route::get('/import-old-database', [ImportOldDatabaseController::class, 'index']);

    Route::get('/import-old-inventory-report', [ImportOldInventoryReportController::class, 'index']);

    Route::get('/dummy-purchase', [DummyPurchaseController::class, 'index']);

    Route::get('/import-old-uom', [ImportOldUomController::class, 'index']);

    Route::post('/import-products-discount', [ImportProductsDiscountController::class, 'index']);
});



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
