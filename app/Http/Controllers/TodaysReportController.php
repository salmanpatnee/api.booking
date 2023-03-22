<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TodaysReportController extends Controller
{
    public function index()
    {
        $cashRegister = CashRegister::orderBy('date', 'desc')->first();
        $paginate = request('paginate', 20);

        $sales = Sale::completed()->where('date', $cashRegister->date)->with('account');

        $sales = $sales->paginate($paginate);

        $query = DB::table("sales")
            ->select(
                DB::raw("SUM(`net_amount`) as total_net_amount"),
                DB::raw("SUM(`purchase_amount`) as total_purchase_amount"),
                DB::raw("(SUM(`net_amount`)-SUM(`purchase_amount`)-SUM(`shipping_charges`)) as total_profit_amount")
            )
            ->where('date', $cashRegister->date)->where('status', '=', 'completed')->orWhere('status', '=', 'final')->get()
            ->first();

        $data = [
            'sales' =>  $sales,
            'total_net_amount' => $query->total_net_amount,
            'total_purchase_amount' => $query->total_purchase_amount,
            'total_profit_amount' => $query->total_profit_amount,
        ];
        return response()->json(['data' => $data]);
    }
}
