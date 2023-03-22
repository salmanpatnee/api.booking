<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate  = request('paginate', 10);
        $term      = request('search', '');
        $type      = request('type', 'customer');
        $sortOrder = request('sortOrder', 'desc');
        $orderBy   = request('orderBy', 'created_at');

        $payments = Payment::search($term)
            ->select('id', 'date', 'account_id', 'purchase_id', 'amount')
            ->with(['account' => function ($q) {
                $q->select('id', 'name');
            }])
            ->orderBy($orderBy, $sortOrder)
            ->paginate($paginate);

        return response()->json(['data' => $payments]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'purchase_id' => 'required|exists:purchases,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric',
            'description' => 'nullable',
        ]);
        $attributes['created_by'] = auth()->user()->id;

        $account = Account::find($attributes['account_id']);
        $purchase = Purchase::find($attributes['purchase_id']);

        DB::beginTransaction();
        $payment = Payment::create($attributes);

        /* payable debit(supplier) cash credit */        

        /* Decrease balance of account */
        $account->balance = $account->balance - $payment->amount;
        $account->save();

        /* check status of purchase */
        if ($purchase->net_amount == ($purchase->paid_amount + $payment->amount)) {
            $purchase->payment_status = 'paid';
        } else {
        }
        /* Increase paid amount */
        $purchase->paid_amount += $payment->amount;
        $purchase->save();

        DB::commit();

        return response()->json([
            'message'   => "Amount of {$payment->amount} successfully paid to {$account->name} on purchase {$purchase->id} .",
            'data'      => $payment,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
