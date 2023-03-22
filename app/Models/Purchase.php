<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'discount_amount' => 'double',
    ];

    protected $guarded = ['id'];

    const PURCHASE_STATUS_FINAL = "final";

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function scopeSearch($query, $term)
    {
        $term = "%$term%";

        $query->where(function ($query) use ($term) {
            $query->where('reference_number', 'like', $term)
                ->orWhereHas('account', function ($query) use ($term) {
                    $query->where('name', 'like', $term);
                });
        });
    }
}
