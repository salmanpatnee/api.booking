<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\AccountStoreRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $term     = request('search', '');

        $accounts = Account::search($term)
            ->whereIn('account_type', ['customer', 'both'])
            ->limit(10)
            ->get();

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
                'message' => 'Customer created successfully.',
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
    public function show(Account $customer)
    {
        $customer->load(['sales' => function ($q) {
            $q->select('id', 'date', 'account_id', 'products_count', 'net_amount')
                ->orderBy('date', 'desc');
        }]);
        return response()->json(['data' => $customer]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Account $customer)
    {
        $customer->update($request->all());

        return (new AccountResource($customer))
            ->additional([
                'message' => 'Customer updated successfully.',
                'status' => 'success'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function export(Request $request)
    {
        return Excel::download(new CustomersExport($request), 'customers.xlsx');
    }

    public function import(Request $request)
    {
        // $excludedEmails = ["haseeb@gmail.com", "salmanabdulghani.cis@gmail.com", "rabiasiddiqui235@gmail.com"];
        // $excludedPhones = ["03343039795"];
        /* customers with same phone or email or not re insert  */

        DB::connection("mysql2")->table("customer_information")
            ->select("customer_id", "customer_name", "customer_mobile", "customer_email", "create_date")
            ->where(function ($query) {
                $query->whereNotNull('customer_mobile')
                    ->where('customer_mobile', "!=", "");
            })
            ->orWhere(function ($query) {
                $query->whereNotNull('customer_email')
                    ->where('customer_email', "!=", "");
            })
            ->orderBy('customer_id')->chunk(1000, function ($oldCustomers, $i) {
                foreach ($oldCustomers as $oldCustomer) {



                    $account = Account::where(function ($query) use ($oldCustomer) {
                        $query->where('email', $oldCustomer->customer_email)
                            ->orWhere('phone', $oldCustomer->customer_mobile);
                    })
                        ->where('account_type', "customer")
                        ->first();

                    if (!$account) {
                        Account::create([
                            'name' => $oldCustomer->customer_name,
                            'email' => !empty($oldCustomer->customer_email) ? $oldCustomer->customer_email : null,

                            'company' => null,

                            'phone' => !empty($oldCustomer->customer_mobile) ? $oldCustomer->customer_mobile : null,

                            'balance' => 0,

                            'account_type' => 'customer',
                            'created_at' => $oldCustomer->create_date,
                            'updated_at' => null,

                            'ref_id' => $oldCustomer->customer_id,
                        ]);
                    }
                }
            });
    }
}
