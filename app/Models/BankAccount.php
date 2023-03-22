<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    public function scopeActive($query)
    {
        $query->where(function ($query) {
            $query->where('active', '=', 1);
        });
    }
    use HasFactory;
}
