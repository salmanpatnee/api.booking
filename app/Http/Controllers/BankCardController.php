<?php

namespace App\Http\Controllers;

use App\Models\BankCard;
use Illuminate\Http\Request;

class BankCardController extends Controller
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

        $bankCards = BankCard::search($term)->select('id', 'name', 'bank_id')
            ->with([
                'bank' => function ($q) {
                    $q->select('id', 'name');
                },
            ]);

        if (!empty($request->bank_id))
            $bankCards->where('bank_id', $request->bank_id);

        $bankCards = $bankCards->orderBy('name', 'asc')
            ->paginate($paginate);
        return response()->json(['data' => $bankCards]);
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
     * @param  \App\Models\BankCard  $bankCard
     * @return \Illuminate\Http\Response
     */
    public function show(BankCard $bankCard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankCard  $bankCard
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankCard $bankCard)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankCard  $bankCard
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankCard $bankCard)
    {
        //
    }
}
