<?php

namespace App\Http\Controllers;

use App\Models\BankOffer;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SaleBankOfferController extends Controller
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
     * @param  \App\Models\Sale  $sale
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Sale $sale, Request $request)
    {
        $data = $request->validate([
            'bank_id' => 'required',
            'bank_card_id' => 'required|exists:bank_cards,id'
        ]);

        $currentDate = now()->format('Y-m-d');

        /* ask for the multiple offers available */

        /* check whether offer exist for the provided bank */
        $bankOffer = BankOffer::select(
            'bank_offers.id',
            'bank_offers.bank_id',
            'bank_offers.start_date',
            'bank_offers.end_date',
            'bank_offers.amount_limit',
            'bank_offers.orders_limit',
            'bank_offers.discount_type',
            'bank_offers.discount_amount',
            'bank_offers.discount_percentage',
            'bank_offers.count',
            'bank_offers.created_by',
            'bank_card_bank_offer.bank_card_id'
        )
            ->leftJoin('bank_card_bank_offer', 'bank_offer_id', '=', 'bank_offers.id')
            ->where(DB::raw("DATE(`start_date`)"), '<=', $currentDate)
            ->where(DB::raw("DATE(`end_date`)"), '>=', $currentDate)
            ->where('bank_id', $data['bank_id'])
            ->where('bank_card_id', $data['bank_card_id'])
            ->first();

        if (!$bankOffer) {
            return response()->json(["message" => "No offer exist", "success" => "warning", "data" => null], Response::HTTP_NON_AUTHORITATIVE_INFORMATION);
        }

        /* apply discount */
        // $sale->bank_offer_id = $bankOffer->id;
        // $sale->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BankOffer  $bankOffer
     * @return \Illuminate\Http\Response
     */
    public function show(BankOffer $bankOffer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankOffer  $bankOffer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankOffer $bankOffer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankOffer  $bankOffer
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankOffer $bankOffer)
    {
        //
    }
}
