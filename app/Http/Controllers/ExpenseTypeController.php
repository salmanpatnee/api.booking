<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExpenseTypeResource;
use App\Models\ExpenseType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ExpenseTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /*
        TODO: Cache
        */

        $sortOrder = request('sortOrder', 'desc');
        $orderBy   = request('orderBy', 'created_at');

        $expenseTypes = ExpenseType::orderBy($orderBy, $sortOrder)
            ->get();

        return ExpenseTypeResource::collection($expenseTypes);
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
            'name' => 'required|string|unique:expense_types,name'
        ]);

        $expenseType = ExpenseType::create($attributes);

        return (new ExpenseTypeResource($expenseType))
            ->additional([
                'message' => 'Expense type added',
                'status' => 'success'
            ])->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ExpenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function show(ExpenseType $expenseType)
    {
        return new ExpenseTypeResource($expenseType);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ExpenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ExpenseType $expenseType)
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', Rule::unique('expense_types', 'name')->ignore($expenseType->id)]
        ]);

        $expenseType->update($attributes);

        return (new ExpenseTypeResource($expenseType))
            ->additional([
                'message' => 'Expense type updated.',
                'status'  => 'success'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ExpenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExpenseType $expenseType)
    {
        $expenseType->forceDelete();

        return response([
            'message' => 'Expense type deleted.',
            'status'  => 'success'
        ], Response::HTTP_OK);
    }
}
