<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class BookingListDetails extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'estimated_cost' => 'float',
    ];

    public function bookingList()
    {
        return $this->belongsTo(BookingList::class, 'booking_list_id');
    }

    public function bookingItemParts()
    {
        return $this->hasMany(BookingItemPart::class);
    }


    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function scopeIndexQuery($query)
    {
        $orderBy = request('sort_field', 'created_at');
        if (!in_array($orderBy, ['reference_id', 'status', 'device_name', 'issue', 'customer', 'trade_name', 'employee', 'phone', 'email'])) {
            $orderBy = 'created_at';
        }

        $sortOrder = request('sort_direction', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $filled = array_filter(request()->only([
            'reference_id', 'status', 'device_name', 'issue', 'customer', 'trade_name', 'employee', 'phone', 'email', 'search'
        ]));

        $term = request('search', '');
        $term = "%$term%";

        $query->where(function ($query) use ($term, $filled) {

            if (count($filled) > 0) {

                foreach ($filled as $column => $value) {

                    if ($column == 'reference_id' || $column == 'status') {
                        $query->where($column, $value);
                    }
                     
                    else if ($column == 'customer') {
                        $query->WhereHas('bookingList.account', function ($query) use ($value) {
                            $query->where('name', 'LIKE', '%' . $value . '%');
                        });
                    }
                    else if ($column == 'phone') {
                        $query->WhereHas('bookingList.account', function ($query) use ($value) {
                            $query->where('phone', $value);
                        });
                    }
                    else if ($column == 'email') {
                        $query->WhereHas('bookingList.account', function ($query) use ($value) {
                            $query->where('email', $value);
                        });
                    }
                    else if ($column == 'trade_name') {
                        $query->WhereHas('bookingList.account', function ($query) use ($value, $column) {
                            $query->where($column, 'LIKE', '%' . $value . '%');
                        });
                    }
                    else if ($column == 'employee') {
                        $query->WhereHas('employee', function ($query) use ($value, $column) {
                            $query->where('name', 'LIKE', '%' . $value . '%');
                        });
                    } 
                    else {
                        $query->where($column, 'LIKE', '%' . $value . '%');
                    }
                }
            }

            if (request()->filled('start_date')) {
                $query->where('date', '>=', request('start_date'));
            }

            if (request()->filled('end_date')) {
                $query->where('date', '<=', request('end_date'));
            }

            // if($request->filled('id')) {
            //     $query->where('reference_id', request('id'));
            // }
            // if($request->filled('status')) {
            //     $query->where('status', request('status'));
            // }
            // if($request->filled('device_name')) {
            //     // $query->where('device_name', request('device_name'));
            //     $query->where('device_name', 'like', "%".request('device_name')."%");
            // }
            // if($request->filled('fault')) {
            //     $query->where('issue', 'like', '%'.request('fault').'%');
            // }
        });

        // $request = request();

        // LIKE = %asdsad%
        // $query->search($term, $filled);

        // if (!empty($request->id))
        //     $query->where('reference_id', $request->id)->orWhereHas('bookingList.account', function ($query) use ($request) {
        //         $query->where('name', 'like', $request->id)->orWhere('phone', 'like', $request->id);
        //     });



        // if (!empty($request->status))
        //     $query->where('status', $request->status);


        $query->orderBy($orderBy, $sortOrder);
    }

    public function scopeSearch($query, $term)
    {

        $term = "%$term%";
        $request = request();

        $query->where(function ($query) use ($term, $request) {
            if ($request->filled('reference_id')) {
                $query->where('reference_id', request('id'));
            }
            if ($request->filled('status')) {
                $query->where('status', request('status'));
            }
            if ($request->filled('device_name')) {
                // $query->where('device_name', request('device_name'));
                $query->where('device_name', 'like', "%" . request('device_name') . "%");
            }
            if ($request->filled('fault')) {
                $query->where('issue', 'like', '%' . request('fault') . '%');
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
