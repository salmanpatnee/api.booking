<?php

namespace Database\Seeders;

use App\Models\AccountHead;
use Illuminate\Database\Seeder;

class AccountHeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $accountHeads = ['Cash', 'Bank', 'Capital', 'Purchase', 'Sale', 'Account Payable', 'Account Receivable', 'Expense', 'Purchase Return', 'Sales Return'];

        foreach ($accountHeads as $accountHead) {
            AccountHead::create([
                'name' => $accountHead
            ]);
        }
    }
}
