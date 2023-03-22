<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalEntrySerialNumber;

class JournalEntryService
{
  public function getSerialNumber()
  {
    return JournalEntrySerialNumber::create();
  }

  public function recordEntry(JournalEntrySerialNumber $journalEntrySerialNumber, $accountHeadId, $forAccountHeadId, $debit, $credit, $date, $referenceType = null, $referenceId = null)
  {
    return JournalEntry::create([
      'journal_entry_serial_number_id' => $journalEntrySerialNumber->id,
      'account_head_id' => $accountHeadId,
      'for_account_head_id' => $forAccountHeadId,
      'debit' => $debit,
      'credit' => $credit,
      'date' => $date,

      'reference_type' => $referenceType,
      'reference_id' => $referenceId,
    ]);
  }
}
