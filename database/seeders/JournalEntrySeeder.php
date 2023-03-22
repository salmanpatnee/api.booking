<?php

namespace Database\Seeders;

use App\Models\AccountHead;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalEntrySerialNumber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JournalEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /* Capital entry */
        $capitalEntries = [
            [
                // 'serial_no' => 1,
                'account_head_id' => AccountHead::CASH_ID,
                'for_account_head_id' => AccountHead::CAPITAL_ID,
                'debit' => 100000,
                'credit' => 0,
                'date' => '2022-01-01',
                'reference_type' => null,
                'reference_id' => null,
            ],
            [
                // 'serial_no' => 1,
                'account_head_id' => AccountHead::CAPITAL_ID,
                'for_account_head_id' => AccountHead::CASH_ID,
                'debit' => 0,
                'credit' => 100000,
                'date' => '2022-01-01',
                'reference_type' => null,
                'reference_id' => null,
            ],
        ];
        DB::beginTransaction();
        $journalEntrySerialNumber = JournalEntrySerialNumber::create();
        foreach ($capitalEntries as $capitalEntry) {
            JournalEntry::create([
                'journal_entry_serial_number_id' => $journalEntrySerialNumber->id,
                'account_head_id' => $capitalEntry['account_head_id'],
                'for_account_head_id' => $capitalEntry['for_account_head_id'],
                'debit' => $capitalEntry['debit'],
                'credit' => $capitalEntry['credit'],
                'date' => $capitalEntry['date'],

                'reference_type' => $capitalEntry['reference_type'],
                'reference_id' => $capitalEntry['reference_id'],
            ]);
        }
        DB::commit();

        /* Add new expense entry */
        DB::beginTransaction();
        $expense = Expense::create([
            'expense_type_id' => 1,
            'payment_method_id' => 1,
            'amount' => 20000,
            'date' => '2022-01-02'
        ]);
        $expenseEntries = [
            [
                // 'serial_no' => 2,
                'account_head_id' => AccountHead::EXPENSE_ID,
                'for_account_head_id' => $expense->payment_method_id,
                'debit' => $expense->amount,
                'credit' => 0,
                'date' => $expense['date'],

                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
            ],
            [
                // 'serial_no' => 2,
                'account_head_id' => $expense->payment_method_id,
                'for_account_head_id' => AccountHead::EXPENSE_ID,
                'debit' => 0,
                'credit' => $expense->amount,
                'date' => $expense['date'],

                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
            ],
        ];
        $journalEntrySerialNumber = JournalEntrySerialNumber::create();
        foreach ($expenseEntries as $expenseEntry) {
            JournalEntry::create([
                'journal_entry_serial_number_id' => $journalEntrySerialNumber->id,
                'account_head_id' => $expenseEntry['account_head_id'],
                'for_account_head_id' => $expenseEntry['for_account_head_id'],
                'debit' => $expenseEntry['debit'],
                'credit' => $expenseEntry['credit'],
                'date' => $expenseEntry['date'],

                'reference_type' => $expenseEntry['reference_type'],
                'reference_id' => $expenseEntry['reference_id'],
            ]);
        }
        DB::commit();
    }
}
