<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseStoreRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\AccountHead;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Services\CashRegisterService;
use App\Services\JournalEntryService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ExpenseController extends Controller
{
    private $journalEntryService;

    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate   = request('paginate', 10);

        $expenses = Expense::indexQuery()
            ->paginate($paginate);

        return ExpenseResource::collection($expenses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ExpenseStoreRequest $request)
    {
        $userId = auth()->user()->id;
        DB::beginTransaction();

        $data = $request->all();
        $data['created_by'] = $userId;

        if ($request->by == "cashier") {
            $cashRegister = CashRegister::where('user_id', $userId)
                ->whereNull('end_datetime')
                ->first();
            if (!$cashRegister) {
                return response()->json(["message" => "No register found", "errors" => []], 422);
            }
            $data['date'] = $cashRegister['date'];
        }

        $expense = Expense::create($data);

        $serialNumber = $this->journalEntryService->getSerialNumber();

        $this->journalEntryService->recordEntry(
            $serialNumber,
            AccountHead::EXPENSE_ID,
            $expense->payment_method_id,
            $expense->amount,
            0,
            $expense['date'],
            Expense::class,
            $expense->id
        );

        $this->journalEntryService->recordEntry(
            $serialNumber,
            $expense->payment_method_id,
            AccountHead::EXPENSE_ID,
            0,
            $expense->amount,
            $expense['date'],
            Expense::class,
            $expense->id
        );

        if (isset($cashRegister)) {
            $cashRegisterService = new CashRegisterService();

            $cashRegisterService->saveEntry(
                cashRegisterId: $cashRegister->id,
                description: "Expense",
                cashRegisterBalance: $cashRegister->balance,
                referenceType: Expense::class,
                referenceId: $expense->id,
                credit: $expense->amount
            );

            $cashRegisterService->updateBalance($cashRegister, credit: $expense->amount);
        }

        DB::commit();

        return (new ExpenseResource($expense))
            ->additional([
                'message' => 'Expense added.',
                'status' => 'success'
            ])->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function show(Expense $expense)
    {
        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function update(ExpenseStoreRequest $request, Expense $expense)
    {
        //new amount - old amount
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function destroy(Expense $expense)
    {
        //
    }
}
