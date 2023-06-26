<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'client_name',
        'phone',
        'notes',
        'tax_amount',
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
        'tax_amount' => 'float',
        'total' => 'float',
        'net_total' => 'float',
        'date' => 'date',
    ];

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItems::class);
    }

    // protected function total(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => $value / 100,
    //         set: fn ($value) => $value * 100,
    //     );
    // }

    public function scopeSearch($query, $term)
    {
        $invoieNo = $term;

        $term = "%$term%";

        $query->where(function ($query) use ($term, $invoieNo) {
            $query->where('invoice_no', $invoieNo)
                ->orWhere('client_name', 'like', $term)
                ->orWhere('phone', 'like', $term);
        });
    }
}
