<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItemPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_list_details_id', 
        'part_id', 
        'quantity', 
        'cost', 
        'price', 
        'total'
    ];

    protected $casts = [
        'cost' => 'float',
        'price' => 'float',
        'total' => 'float',
    ];
    
    protected $with = ['part'];

    public function bookingItem()
    {
        return $this->belongsTo(BookingListDetails::class, 'booking_list_details_id');
    }

    public function part()
    {
        return $this->belongsTo(Part::class, 'part_id');
    }
    
}
