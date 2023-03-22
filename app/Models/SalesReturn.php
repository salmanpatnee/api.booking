<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function salesReturnDetails()
    {
        return $this->hasMany(SalesReturnDetail::class);
    }

    public function scopeIndexQuery($query, $term)
    {
        $term       = request('search', '');
        // $sortOrder  = request('sortOrder', 'desc');
        // $orderBy    = request('orderBy', 'created_at');

        $query->select('id', 'sale_id', 'date', 'sale_amount_before_return', 'sale_amount_after_return', 'sale_return_amount', 'created_at')

            ->with([
                'sale' => function ($q) {
                    $q->select('id', 'account_id', 'date')
                        ->with(['account' => function ($q) {
                            $q->select('id', 'name');
                        }]);
                },
            ]);


        if (!empty(request('start_date')))
            $query->where('date', '>=', request('start_date'));
        if (!empty(request('end_date')))
            $query->where('date', '<=', request('end_date'));
        
            if($term){

                $query->where('id', '=', $term)->orWhere('sale_id', '=', $term);
            }
        $query->orderBy('date', 'desc');
    }
}
