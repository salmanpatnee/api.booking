<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeSearch($query, $term)
    {
        $term = "%$term%";

        $query->where(function ($query) use ($term) {
            $query->where('id', 'like', $term)
                ->orWhereHas('account', function ($query) use ($term) {
                    $query->where('name', 'like', $term)->orWhere('phone', 'like', $term);
                });
        });
    }
}
