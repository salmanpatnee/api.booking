<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountStoreRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
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
        $sortOrder = request('sortOrder', 'desc');
        $orderBy   = request('orderBy', 'created_at');

        $accounts = Account::search($term)
            ->whereIn('account_type', ['supplier', 'both'])
            ->orderBy($orderBy, $sortOrder)
            ->paginate($paginate);

    return AccountResource::collection($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AccountStoreRequest $request)
    {
        $account = Account::create($request->all());

        return (new AccountResource($account))
            ->additional([
                'message' => 'Supplier created successfully.',
                'status' => 'success'
            ])->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function show(Account $supplier)
    {
        $supplier->load(['purchases' => function ($q) {
            $q->select('id', 'date', 'account_id', 'products_count', 'net_amount', 'reference_number')
                ->orderBy('date', 'desc');
        }]);

        return response()->json(['data' => $supplier]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function update(AccountStoreRequest $request, Account $supplier)
    {
        $supplier->update($request->all());

        return (new AccountResource($supplier))
            ->additional([
                'message' => 'Supplier updated successfully.',
                'status' => 'success'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        //
    }

    public function import()
    {
        $oldSuppliers = DB::connection("mysql2")->table("supplier_information")
            ->select("supplier_id", "supplier_name", "address", "emailnumber")
            ->orderBy('supplier_id')
            ->get();

        foreach ($oldSuppliers as $oldSupplier) {

            // balance from acc_coa 

            $account = Account::where("ref_id", $oldSupplier->supplier_id)
                ->where('account_type', "supplier")
                ->first();

            if (!$account) {
                Account::create([
                    'name' => $oldSupplier->supplier_name,
                    'email' => !empty($oldSupplier->emailnumber) ? $oldSupplier->emailnumber : null,

                    'company' => null,

                    'phone' => null,

                    'balance' => 0,

                    'account_type' => 'supplier',
                    'created_at' => now(),
                    'updated_at' => now(),

                    'address' => !empty($oldSupplier->address) ? $oldSupplier->address : null,
                    'ref_id' => $oldSupplier->supplier_id
                ]);
            }
        }
    }
}
