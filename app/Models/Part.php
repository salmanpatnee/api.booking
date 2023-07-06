<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'code', 'cost', 'price', 'quantity', 'purchase_date', 'units_sold'];

    protected $casts = [
        'cost' => 'float',
        'price' => 'float',
    ];

    public function scopeSearch($query, $term)
    {
        $term = "%$term%";

        $query->where(function ($query) use ($term) {
            $query->where('name', 'like', $term)
            ->orWhere('code', 'like', $term);
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
