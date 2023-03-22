<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaySummaryController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->user()->id;
        $cashRegister = $cashRegister = CashRegister::where('user_id', $userId)
            ->whereNull('end_datetime')
            ->first();
        if (!$cashRegister) {
            return response()->json(["message" => "No register found", "errors" => []], 422);
        }
        $sales = Sale::select('payment_method_id', DB::raw("SUM(net_amount) as net_amount"))
            ->whereNotIn('status', ['draft', 'ordered'])
            ->where('date', $cashRegister->date)
            ->where('updated_by', $userId) //who receives cash
            ->groupBy('payment_method_id')
            ->get()
            ->pluck('net_amount', 'payment_method_id');

        $salesreturnAmount = SalesReturn::where('date', $cashRegister->date)
            ->where('created_by', $userId) //who pay cash
            ->sum('sale_return_amount');

        $expenseAmount = Expense::where('date', $cashRegister->date)
            ->where('created_by', $userId) //who pay cash
            ->sum('amount');

        $cardSalesAmount = $sales[PaymentMethod::BANK_ID] ?? 0;
        $cashSalesAmount = $sales[PaymentMethod::CASH_ID] ?? 0;
        $data = [
            'cash_in_hand' => $cashRegister->cash_in_hand,
            'debit' => $cashRegister->debit,
            'credit' => $cashRegister->credit,
            'balance' => $cashRegister->balance,

            'card_sales_amount' => $cardSalesAmount,
            'cash_sales_amount' => $cashSalesAmount,
            'total_sales_amount' => $cardSalesAmount + $cashSalesAmount,

            'total_sales_return_amount' => $salesreturnAmount,

            'total_expense_amount' => $expenseAmount,
        ];
        return response()->json(['data' => $data]);
    }
}
