<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashRegisterEntry;

class CashRegisterService
{
  public function saveEntry(int $cashRegisterId, string $description, float $cashRegisterBalance, string $referenceType = null, int $referenceId = null, float $debit = 0, float $credit = 0)
  {
    $balance = $cashRegisterBalance + $debit - $credit;
    return CashRegisterEntry::create([
      'cash_register_id' => $cashRegisterId,
      'description' => $description,
      'reference_type' => $referenceType,
      'reference_id' => $referenceId,
      'debit' => $debit,
      'credit' => $credit,
      'balance' => $balance,
    ]);
  }

  public function updateBalance(CashRegister $cashRegister, float $debit = 0, float $credit = 0)
  {
    $cashRegisterBalance = $cashRegister->balance + $debit - $credit;
    $cashRegister->debit += $debit;
    $cashRegister->credit += $credit;
    $cashRegister->balance = $cashRegisterBalance;
    $cashRegister->save();
  }

  public function recalculateBalance(CashRegister $cashRegister)
  {
    $cashRegisterDebit = 0;
    $cashRegisterCredit = 0;
    $cashRegisterBalance = 0;
    $cashRegisterEntries = CashRegisterEntry::query()
    ->where('cash_register_id', $cashRegister->id)
    ->orderBy('id', 'ASC')
    ->get();
    foreach ($cashRegisterEntries as $cashRegisterEntry) {
      $cashRegisterBalance = $cashRegisterBalance + $cashRegisterEntry->debit - $cashRegisterEntry->credit;
      $cashRegisterEntry->balance = $cashRegisterBalance;
      $cashRegisterEntry->save();
      $cashRegisterDebit += $cashRegisterEntry->debit;
      $cashRegisterCredit += $cashRegisterEntry->credit;
    }
    $cashRegister->debit = $cashRegisterDebit;
    $cashRegister->credit = $cashRegisterCredit;
    $cashRegister->balance = $cashRegisterBalance;
    $cashRegister->save();
  }
}
