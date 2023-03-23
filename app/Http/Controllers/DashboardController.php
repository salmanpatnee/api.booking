<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SalesReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        return [
            'total_customers' => Account::where('account_type', '=', 'customer')->count(),
            'total_suppliers' => Account::where('account_type', '=', 'supplier')->count(),
            'total_products' => Product::active()->count(),
            'todays_total_purchase_amount' => Purchase::whereDate('date', Carbon::today())->sum('net_amount'),
            'todays_total_purchase_return_amount' => PurchaseReturn::whereDate('date', Carbon::today())->sum('purchase_return_amount'),
            'todays_total_sales_amount' => Sale::completed()->whereDate('date', Carbon::today())->sum('net_amount'),
            'todays_total_sales_return_amount' => SalesReturn::whereDate('date', Carbon::today())->sum('sale_return_amount'),
            'todays_total_orders' => Sale::whereDate('date', Carbon::today())->count(),
            'todays_total_expenses' => Expense::whereDate('date', Carbon::today())->sum('amount'),
            // 'alert_quantity_products' => ProductResource::collection(Product::aleryQuantity()->paginate(10))
        ];
    }
}
