<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\CashRegisterEntry;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CashRegisterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate = request('paginate', 10);
        // $term     = request('search', '');
        // $sortOrder     = request('sortOrder', 'desc');
        // $orderBy       = request('orderBy', 'created_at');        

        $cashRegisters = CashRegister::select(
            'id',
            'user_id',
            'date',
            'created_at as start_datetime',
            'end_datetime',
            'cash_in_hand',
            'cash_in_hand_at_end',
            DB::raw("(select sum(`net_amount`) from sales where `updated_by` = cash_registers.user_id and `date` = cash_registers.date and payment_method_id = " . PaymentMethod::CASH_ID . ") as total_cash_sales"),
            DB::raw("(select sum(`net_amount`) from sales where `updated_by` = cash_registers.user_id and `date` = cash_registers.date and payment_method_id = " . PaymentMethod::BANK_ID . ") as total_card_sales"),
            DB::raw("(select sum(`sale_return_amount`) from `sales_returns` where `created_by` = cash_registers.user_id and `date` = cash_registers.date) as total_sales_returns_amount"),
            DB::raw("(select sum(`amount`) from expenses where `created_by` = cash_registers.user_id and `date` = cash_registers.date) as total_expenses"),
            DB::raw("(select count(*) from sales where `updated_by` = cash_registers.user_id and `date` = cash_registers.date) as sales_count"),
        );

        if (!empty(request('start_date'))) $cashRegisters->where('date', '>=', request('start_date'));
        if (!empty(request('end_date'))) $cashRegisters->where('date', '<=', request('end_date'));

        $cashRegisters->with([
            'user' => function ($q) {
                $q->select('id', 'name');
            },
        ])
            ->orderBy('date', 'desc');
        $cashRegisters = $cashRegisters->paginate($paginate);
        return response()->json(['data' => $cashRegisters]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $userId = auth()->user()->id;

        $cashRegister = CashRegister::where('user_id', $userId)
            ->whereNull('end_datetime')
            ->first();
        if ($cashRegister) {
            return response()->json(["message" => "You have unclosed register", "errors" => []], 422);
        }
        $data = $request->validate([
            'cash_in_hand' => 'required|numeric',
            'date' => ['required', Rule::unique('cash_registers')->where(fn ($query) => $query->where('user_id', $userId))]
        ]);
        $data['date'] = now()->format('Y-m-d');
        $data['user_id'] = $userId;
        $data['debit'] = $request->cash_in_hand;
        $data['credit'] = 0;
        $data['balance'] = $request->cash_in_hand;
        $cashRegister = CashRegister::create($data);

        $cashRegisterService = new CashRegisterService();
        $cashRegisterService->saveEntry(
            cashRegisterId: $cashRegister->id,
            description: "Initial Entry",
            cashRegisterBalance: 0,
            debit: $cashRegister->balance
        );

        return response()->json([
            'message'   => 'Cash register opened successfully.',
            'data'      => $cashRegister,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if ($id == "me") {
            $userId = auth()->user()->id;
            $cashRegister = $cashRegister = CashRegister::where('user_id', $userId)
                ->whereNull('end_datetime')
                ->first();
            if (!$cashRegister) {
                return response()->json(["message" => "No register found", "errors" => []], 422);
            }
            return response()->json(['data' => $cashRegister]);
        }
        $cashRegister = CashRegister::find($id);
        $cashRegister->load(['cashRegisterEntries:id,cash_register_id,description,debit,credit,balance', 'user']);

        return response()->json(['data' => $cashRegister]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        if ($id == "me") {
            $userId = auth()->user()->id;
            $cashRegister = $cashRegister = CashRegister::where('user_id', $userId)
                ->whereNull('end_datetime')
                ->first();
            if (!$cashRegister) {
                return response()->json(["message" => "No register found", "errors" => []], 422);
            }
            $cashRegister->end_datetime = now();
            $cashRegister->cash_in_hand_at_end = $request->cash_in_hand_at_end;
            $cashRegister->save();

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

            return response()->json([
                'message'   => 'Cash register closed successfully.',
                'data'      => $data,
                'status'    => 'success'
            ], Response::HTTP_OK);
        }

        $data = $request->validate([
            'cash_in_hand' => 'required|numeric',
        ]);

        $cashRegister = CashRegister::find($id);
        
        $cashRegister->cash_in_hand = $request->cash_in_hand;        
        $cashRegister->debit = $request->cash_in_hand;
        $cashRegister->credit = 0;
        $cashRegister->balance = $request->cash_in_hand;

        $cashRegisterService = new CashRegisterService();

        DB::beginTransaction();
        $cashRegister->save();

        $cashRegisterBalance = $cashRegister->cash_in_hand;

        $cashRegisterInitialEntry = CashRegisterEntry::query()
            ->where('cash_register_id', $cashRegister->id)
            ->where('description', 'Initial Entry')
            ->first();
        $cashRegisterInitialEntry->debit = $cashRegisterBalance;
        $cashRegisterInitialEntry->balance = $cashRegisterBalance;
        $cashRegisterInitialEntry->save();

        foreach ($cashRegister->cashRegisterEntries->where('id', '!=', $cashRegisterInitialEntry->id) as $cashRegisterEntry) {
            // dd($cashRegisterEntry);
            $cashRegisterEntry->balance = $cashRegister->balance + $cashRegisterEntry->debit - $cashRegisterEntry->credit;
            $cashRegisterEntry->save();

            $cashRegisterService->updateBalance($cashRegister, debit: $cashRegisterEntry->debit, credit: $cashRegisterEntry->credit);
        }

        DB::commit();

        return response()->json([
            'message'   => 'Cash register updated successfully.',
            'data'      => $cashRegister,
            'status'    => 'success'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function destroy(CashRegister $cashRegister)
    {
        //
    }
}
