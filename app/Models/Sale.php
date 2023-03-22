<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'is_deliverable' => 'boolean',
    ];

    protected $guarded = ['id'];

    const SALE_STATUS_ORDERED = "ordered";
    const SALE_STATUS_COMPLETED = "completed";
    const SALE_STATUS_RETURNED = "returned";
    const SALE_STATUS_FINAL = "final";

    const SHIPPING_STATUS_ORDERED = "ordered";
    const SHIPPING_STATUS_DELIVERED = "delivered";

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    /**
     * Get all of the post's comments.
     */
    public function productInventoryEntries(): MorphMany
    {
        return $this->morphMany(ProductInventoryEntry::class, 'reference');
    }

    public function scopeCompleted($query)
    {
        $query->whereIn('status', [self::SALE_STATUS_COMPLETED, self::SALE_STATUS_RETURNED, self::SALE_STATUS_FINAL]);
    }

    public function scopeIndexQuery($query)
    {
        $request = request();

        $term = request('search', '');
        $sortOrder = request('sortOrder', 'desc');
        $orderBy = request('orderBy', 'created_at');
        $query->search($term)->with(['account' => function ($q) {
            $q->select('id', 'name');
        }]);
        if (!empty($request->id))
            $query->where('id', $request->id);
        if (!empty($request->start_date))
            $query->where('date', '>=', $request->start_date);
        if (!empty($request->end_date))
            $query->where('date', '<=', $request->end_date);
        if (!empty($request->status))
            $query->where('status', $request->status);
        else {
            $query->completed();
        }

        $query->orderBy($orderBy, $sortOrder);
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
