<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductInventoryPurchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function productInventoryOutflowDetail()
    {
        return $this->hasOne(ProductInventoryOutflowDetail::class);
    }
}
