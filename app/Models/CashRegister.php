<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = ["id"];

    public function cashRegisterEntries()
    {
        return $this->hasMany(CashRegisterEntry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
