<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;


    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function productInventoryEntries()
    {
        return $this->hasMany(ProductInventoryEntry::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function scopeSearch($query, $term)
    {
        $term = "%$term%";

        $query->where(function ($query) use ($term) {
            $query->where('name', 'like', $term)
                ->orWhereHas('category', function ($query) use ($term) {
                    $query->where('name', 'like', $term);
                });
        });
    }
    public function scopeActive($query)
    {
        $query->where(function ($query) {
            $query->where('is_active', '=', 1);
        });
    }
    public function scopeAleryQuantity($query)
    {
        $query->where(function ($query) {
            $query->whereColumn('quantity', '<=', 'quantity_threshold');
        });
    }
}
