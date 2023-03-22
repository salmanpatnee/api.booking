<?php

namespace App\Http\Controllers;

use App\Models\BankOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankOfferController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate = request('paginate', 10);
        $term     = request('search', '');

        // search($term)->
        $currentDate = now()->format('Y-m-d');

        $bankOffers = BankOffer::select(
            'id',
            'bank_id',
            'start_date',
            'end_date',
            'amount_limit',
            'orders_limit',
            'discount_type',
            'discount_amount',
            'discount_percentage',
            'count',
            'created_by',
            DB::raw("CASE WHEN '{$currentDate}' between `start_date` and `end_date` THEN 1 ELSE 0 END AS is_active")
        )
            ->with([
                'bank' => function ($q) {
                    $q->select('id', 'name');
                },
            ]);

        if (!empty($request->bank_id))
            $bankOffers->where('bank_id', $request->bank_id);

        $bankOffers = $bankOffers->orderBy('id', 'asc')
            ->paginate($paginate);

        return response()->json(['data' => $bankOffers]);
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
