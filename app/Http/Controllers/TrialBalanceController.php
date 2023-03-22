<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrialBalanceCollection;
use App\Http\Resources\TrialBalanceResource;
use App\Models\AccountHead;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialBalanceController extends Controller
{
    public function __invoke()
    {
        return TrialBalanceResource::collection(AccountHead::all());
    }
}
