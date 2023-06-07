<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingListDetails extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function bookingList()
    {
        return $this->belongsTo(BookingList::class, 'booking_list_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function scopeIndexQuery($query)
    {
        $request = request();

        $term = request('search', '');
        $sortOrder = request('sortOrder', 'desc');
        $orderBy = request('orderBy', 'created_at');
        $query->search($term);

        if (!empty($request->id))
            $query->where('reference_id', $request->id)->orWhereHas('bookingList.account', function ($query) use ($request) {
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
        $request = request();

        $query->where(function ($query) use ($term, $request) {
            if($request->filled('id')) {
                $query->where('reference_id', request('id'));
            }
            if($request->filled('status')) {
                $query->where('status', request('status'));
            }
            if($request->filled('device_name')) {
                // $query->where('device_name', request('device_name'));
                $query->where('device_name', 'like', "%".request('device_name')."%");
            }
            if($request->filled('fault')) {
                $query->where('issue', 'like', '%'.request('fault').'%');
            }
            // $query->when($request->has('id'), function($query) use ($request){
            //     $query->where('reference_id', '=', request('id'))
            //     ->when($request->has('status'), function ($query) use ($request){
            //         $query->where('status', '=', request('status'));
            //     })
            //     ->when($request->has('device_name'), function ($query) use ($request) {
            //         $query->where($request->has('device_name'), '=',request('device_name'));
            //     })
            //     ->when($request->has('fault'), function ($query) use ($request) {
            //         $query->where('fault', '=',request('fault'));
            //     });
            // });
            // ->whereHas('bookingList.account', function ($query) use ($term) {
            //     $query->where('name', 'like', $term)->where('phone', 'like', $term);
            // });
        });
    }

    public function scopeCompleted($query)
    {
        $query->where('status', '=', 'complete');
    }
}
