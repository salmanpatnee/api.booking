<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierLedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Account $supplier)
    {
        // Hide not in range values
        // Or calculate balance then show range values
        $sortOrder = request('sortOrder', 'desc');
        $orderBy   = request('orderBy', 'created_at');

        // return Payment::where("account_id", $supplier->id)->get();
        $balance = 0;

        $purchases = Purchase::select('id', 'date', DB::raw("'Purchase' as description"), DB::raw("'' as debit"), 'net_amount as credit', 'created_at', DB::raw("1 as rank"))
            ->where("account_id", $supplier->id);
        $payments = Payment::select('id', 'date', DB::raw("'Payment' as description"), 'amount as debit', DB::raw("'' as credit"), 'created_at', DB::raw("2 as rank"))
            ->where("account_id", $supplier->id);
        $purchaseReturns = PurchaseReturn::select(
            'id',
            'date',
            DB::raw("'Purchase Return' as description"),
            'purchase_return_amount as debit',
            DB::raw("'' as credit"),
            'created_at',
            DB::raw("3 as rank")
        )
            ->where("account_id", $supplier->id);
        $receipts = Receipt::select(
            'id',
            'date',
            DB::raw("'Receipt' as description"),
            DB::raw("'' as debit"),
            'amount as credit',
            'created_at',
            DB::raw("4 as rank")
        )
            ->where("account_id", $supplier->id);

        $entries = $purchases->union($payments)->union($purchaseReturns)->union($receipts)->orderBy('date', 'asc')->orderBy('created_at', 'asc')->orderBy('rank', 'asc')->get();

        for ($i = 0; $i < $entries->count(); $i++) {
            switch ($entries[$i]->description) {
                case 'Purchase':
                    $balance += $entries[$i]->credit;
                    break;

                case 'Payment':
                    $balance -= $entries[$i]->debit;
                    break;

                case 'Purchase Return':
                    $balance -= $entries[$i]->debit;
                    break;

                case 'Receipt':
                    $balance += $entries[$i]->credit;
                    break;
            }
            $entries[$i]->balance = $balance;
        }

        return $entries;
    }
}
