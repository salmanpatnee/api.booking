<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SaleShipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $salesShipment)
    {
        $sale = $salesShipment;
        $userId = auth()->user()->id;

        $cashRegister = CashRegister::where('user_id', $userId)
            ->whereNull('end_datetime')
            ->first();
        if (!$cashRegister) {
            return response()->json(["message" => "No register found", "errors" => []], 422);
        }

        DB::beginTransaction();

        $sale->status = "completed";
        $sale->shipping_status = "delivered";
        $sale->save();


        if ($sale->payment_method_id == PaymentMethod::CASH_ID) {
            $cashRegisterService = new CashRegisterService();

            $cashRegisterService->saveEntry(
                cashRegisterId: $cashRegister->id,
                description: "Sale",
                cashRegisterBalance: $cashRegister->balance,
                referenceType: Sale::class,
                referenceId: $sale->id,
                debit: $sale->net_amount
            );

            $cashRegisterService->updateBalance($cashRegister, debit: $sale->net_amount);
        }

        DB::commit();

        return response()->json([
            'message'   => 'Sale delivered successfully.',
            'data'      => $sale,
            'status'    => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sale $sale)
    {
        //
    }
}
