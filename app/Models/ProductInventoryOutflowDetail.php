<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInventoryOutflowDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function productInventoryOutflow()
    {
        return $this->belongsTo(ProductInventoryOutflow::class);
    }

    public function productInventoryPurchase()
    {
        return $this->belongsTo(ProductInventoryPurchase::class);
    }
}
