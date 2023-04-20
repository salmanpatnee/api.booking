<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SaleCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'trade_name' => 'nullable',
            'phone' => ['required'],
            'email' => ['nullable'],

            'address' => 'nullable',


            'balance' => 'required',
        ]);

        $account = Account::query()
            ->where(function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->whereNotNull("phone")
                        ->where('phone', $request->phone);
                })->orWhere(
                    function ($query) use ($request) {
                        $query->whereNotNull("email")
                            ->where('email', $request->email);
                    }
                );
            })
            ->where('account_type', "customer")
            ->first();

        // $account = Account::whereNotNull("phone")
        //     ->whereNotNull("email")
        //     ->where(function ($query) use ($request) {
        //         $query->where('email', $request->email)
        //             ->orWhere('phone', $request->phone);
        //     })
        //     ->where('account_type', "customer")
        //     ->first();
        $message = "Customer fetched successfully.";

        if (!$account) {
            $data['account_type'] = 'customer';
            $account = Account::create($data);
            $message = "Customer created successfully.";
        }

        return (new AccountResource($account))
            ->additional([
                'message' => $message,
                'status' => 'success'
            ])->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
}
