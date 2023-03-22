<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankOffer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ["id"];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * The bank cards that belong to the bank offer.
     */
    public function bankCards()
    {
        return $this->belongsToMany(BankCard::class);
    }
}
