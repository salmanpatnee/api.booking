<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalesReturn;
use Illuminate\Http\Request;

class TodaysSaleController extends Controller
{
    public function index(Request $request)
    {
        $paginate = request('paginate', 20);
        $term = request('search', '');

        if (!empty($request->start_date) && !empty($request->end_date)) {
            $sales = Sale::search($term)->completed()->where('date', $request->start_date)->with('account');

            $salesAmount = $sales->sum('net_amount');

            $sales = $sales->paginate($paginate);

            $salesReturnAmount = SalesReturn::where('date', $request->start_date)->sum('sale_return_amount');

            $data = [
                'sales' =>  $sales,
                'total_sales_amount' => (float)$salesAmount,
                'total_sales_return_amount' => (float)$salesReturnAmount,
            ];
            return response()->json(['data' => $data]);
        }
    }
}
