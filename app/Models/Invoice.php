<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no', 
        'client_name', 
        'client_email', 
        'description', 
        'vat', 
        'total', 
        'net_total', 
        'date', 
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vat' => 'float',
        'total' => 'float',
        'net_total' => 'float',
        'date' => 'date',
    ];

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value / 100, 
            set: fn ($value) => $value * 100, 
        );
    }
}
