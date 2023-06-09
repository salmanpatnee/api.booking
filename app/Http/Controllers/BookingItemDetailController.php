<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingItemResource;
use App\Models\BookingListDetails;
use Illuminate\Http\Request;

class BookingItemDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate  = request('paginate', 30);
        $search  = request('search', '');
        $bookingItems = [];

        $totalEstimatedCost = BookingListDetails::indexQuery()->sum('estimated_cost');

        // $bookingItems = BookingListDetails::query()->indexQuery()->paginate($paginate);

        $bookingItems = BookingListDetails::indexQuery()->paginate($paginate);
        
        return BookingItemResource::collection($bookingItems)->additional([
            'meta' => [
                'totalEstimatedCost' => round($totalEstimatedCost, 2)
            ]
        ]);

        // $bookingItems = BookingListDetails::with('bookingList')->paginate($paginate);
        // return BookingItemResource::collection($bookingItems);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BookingListDetails  $BookingListDetails
     * @return \Illuminate\Http\Response
     */
    public function show(BookingListDetails $BookingListDetails)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BookingListDetails  $BookingListDetails
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BookingListDetails $BookingListDetails)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BookingListDetails  $BookingListDetails
     * @return \Illuminate\Http\Response
     */
    public function destroy(BookingListDetails $BookingListDetails)
    {
        //
    }
}
