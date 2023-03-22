<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (range(1, 300) as $i) {

            Account::create([
                'name' => 'Supplier ' . $i,
                'email' => 'supplier' . $i . '@gmail.com',
                'phone' => '923219876543' . $i,
                // 'company' => 'Supplier ' . $i . ' Company',
                'balance' => 0,
                'account_type' => 'supplier',
            ]);
        }
        foreach (range(1, 300) as $i) {

            Account::create([
                'name' => 'Customer ' . $i,
                'email' => 'customr' . $i . '@gmail.com',
                'phone' => '923219876543' . $i,
                // 'company' => 'Supplier ' . $i . ' Company',
                'balance' => 0,
                'account_type' => 'customer',
            ]);
        }
        /* Supplier without balance */


        /* Supplier with balance */
        // DB::beginTransaction();
        // $account = Account::create([
        //     'name' => 'Second supplier',
        //     'balance'=>'10000',
        //     'account_type'=>'supplier',
        // ]);
        // $journalEntries = [
        //     [
        //         'serial_no' => 3,
        //         'account_head_id' => '',
        //         'for_account_head_id' => '',
        //         'debit' => 0,
        //         'credit' => '',
        //     ],
        //     [
        //         'serial_no' => 3,
        //         'account_head_id' => '',
        //         'for_account_head_id' => '',
        //         'debit' => 0,
        //         'credit' => '',
        //     ],
        // ];
        // foreach ($journalEntries as $journalEntry) {
        //     JournalEntry::create([
        //         'serial_no' => $journalEntry['serial_no'],
        //         'account_head_id' => $journalEntry['account_head_id'],
        //         'for_account_head_id' => $journalEntry['for_account_head_id'],
        //         'debit' => $journalEntry['debit'],
        //         'credit' => $journalEntry['credit'],
        //     ]);
        // }
        // DB::commit();

        /* Customer without balance */
        Account::create([
            'name' => 'Walk-in Customer',
            'email' => 'firstcustomer@gmail.com',
            'phone' => '923211234568',
            'company' => 'Walk-in Customer',
            'balance' => 0,
            'account_type' => 'customer',
        ]);

        /* Customer with balance */
        // DB::beginTransaction();
        // $account = Account::create([
        //     'name' => 'Second customer',
        //     'balance'=>5000,
        //     'account_type'=>'customer',
        // ]);
        // $journalEntries = [
        //     [
        //         'serial_no' => 4,
        //         'account_head_id' => '',
        //         'for_account_head_id' => '',
        //         'debit' => 0,
        //         'credit' => '',
        //     ],
        //     [
        //         'serial_no' => 4,
        //         'account_head_id' => '',
        //         'for_account_head_id' => '',
        //         'debit' => 0,
        //         'credit' => '',
        //     ],
        // ];
        // foreach ($journalEntries as $journalEntry) {
        //     JournalEntry::create([
        //         'serial_no' => $journalEntry['serial_no'],
        //         'account_head_id' => $journalEntry['account_head_id'],
        //         'for_account_head_id' => $journalEntry['for_account_head_id'],
        //         'debit' => $journalEntry['debit'],
        //         'credit' => $journalEntry['credit'],
        //     ]);
        // }
        // DB::commit();
    }
}
