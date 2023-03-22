<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductInventoryEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function parentProductInventoryEntry()
    {
        return $this->belongsTo(ProductInventoryEntry::class, 'product_inventory_entry_purchase_id');
    }

    public function scopeSearch($query, $term)
    {
        $term = "%$term%";

        $query->where(function ($query) use ($term) {
            $query->orWhereHas('product', function ($query) use ($term) {
                $query->where('name', 'like', $term);
            });
        });
    }
}
