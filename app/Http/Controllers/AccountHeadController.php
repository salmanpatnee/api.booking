<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountHeadResource;
use App\Models\AccountHead;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class AccountHeadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accountHeads = AccountHead::orderBy('created_at', 'desc')->get();

        return AccountHeadResource::collection($accountHeads);
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
     * @param  \App\Models\AccountHead  $accountHead
     * @return \Illuminate\Http\Response
     */
    public function show(AccountHead $accountHead)
    {
        $accountHead->load('journalEntries');

        return new AccountHeadResource($accountHead);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AccountHead  $accountHead
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AccountHead $accountHead)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AccountHead  $accountHead
     * @return \Illuminate\Http\Response
     */
    public function destroy(AccountHead $accountHead)
    {
        //
    }
}
