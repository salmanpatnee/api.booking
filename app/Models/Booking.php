<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function bookingDetails()
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function scopeIndexQuery($query)
    {
        $request = request();

        $term = request('search', '');
        $sortOrder = request('sortOrder', 'desc');
        $orderBy = request('orderBy', 'created_at');
        $query->search($term);

        if (!empty($request->id))
            $query->where('reference_id', $request->id)->orWhereHas('account', function ($query) use ($request) {
                $query->where('name', 'like', $request->id)->orWhere('phone', 'like', $request->id);
            });
        if (!empty($request->start_date))
            $query->where('date', '>=', $request->start_date);
        if (!empty($request->end_date))
            $query->where('date', '<=', $request->end_date);
        if (!empty($request->status))
            $query->where('status', $request->status);
        // else {
        //     $query->completed();
        // }

        $query->orderBy($orderBy, $sortOrder);
    }

    public function scopeSearch($query, $term)
    {
        $term = "%$term%";

        $query->where(function ($query) use ($term) {
            $query->where('reference_id', 'like', $term)
                ->orWhereHas('account', function ($query) use ($term) {
                    $query->where('name', 'like', $term)->orWhere('phone', 'like', $term);
                });
        });
    }

    public function scopeCompleted($query)
    {
        $query->where('status', '=', 'complete');
    }
}
