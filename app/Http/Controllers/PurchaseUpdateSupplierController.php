<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payment;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PurchaseUpdateSupplierController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Purchase $purchase, Request $request)
    {
        $userId = auth()->user()->id;
        $oldAccountId = $purchase->account_id;

        $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        DB::beginTransaction();

        $purchase->updated_by = $userId;
        $purchase->account_id = $request->account_id;
        $purchase->save();

        /* Add payment entry if paid amount > 0 */
        if ($purchase->paid_amount > 0) {
            $payments = Payment::where('purchase_id', $purchase->id)->get();
            foreach ($payments as $payment) {
                $payment->account_id = $request->account_id;
                $payment->save();
            }

            /* update journal entries if required */
        }

        /* Add payable entry if amount is not full paid  */
        if ($purchase->paid_amount != $purchase->net_amount) {
            $balanceAmount = $purchase->net_amount - $purchase->paid_amount;
            /* update journal entries if required */
        }

        $oldAccount =  Account::find($oldAccountId);
        $oldAccount->purchases_amount -= $purchase->net_amount;
        $oldAccount->purchases_count -= 1;

        $account = Account::find($purchase->account_id);
        $account->purchases_amount += $purchase->net_amount;
        $account->purchases_count += 1;

        /* check if account has balance */
        if ($purchase->payment_status == 'due') {
            /* same as balance amount but for safety I use 2 different variables */
            $remainingAmount = $purchase->net_amount - $purchase->paid_amount;

            $oldAccount->balance = $oldAccount->balance - $remainingAmount;
            $account->balance = $account->balance + $remainingAmount;
        }

        $oldAccount->save();
        $account->save();

        DB::commit();

        return response()->json([
            'message'   => 'Purchase supplier updated successfully.',
            'data'      => $purchase,
            'status'    => 'success'
        ], Response::HTTP_OK);
    }
}
