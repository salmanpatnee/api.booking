<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingListResource;
use App\Models\BookingList;
use App\Models\BookingListDetails;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        // if (request('for') == 'sales') {
        //     $bookings = Booking::query()->indexQuery()->completed()->paginate($paginate);
        // } else {

        //     $totalEstimatedCost = Booking::query()->indexQuery()->sum('estimated_cost');
        //     $bookings = Booking::query()->indexQuery()->paginate($paginate);
        // }


        // return BookingResource::collection($bookings)->additional([
        //     'meta' => [
        //         'totalEstimatedCost' => round($totalEstimatedCost, 2)
        //     ]
        // ]);

        

        $paginate  = request('paginate',50);
        $bookingLists = [];


        $totalEstimatedCost = BookingListDetails::query()->indexQuery()->sum('estimated_cost');
        $bookingLists = BookingList::query()->indexQuery()->paginate($paginate);

        return BookingListResource::collection($bookingLists)->additional([
            'meta' => [
                'totalEstimatedCost' => round($totalEstimatedCost, 2)
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->all();

        $reference_id = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $attributes['reference_id'] = $reference_id;

        $bookingListData = [
            'reference_id' => $reference_id,
            'account_id' => $attributes['account_id'],
            'date' =>  $attributes['date']
        ];

        $booking = BookingList::create($bookingListData);

        foreach ($attributes['booking_item_details'] as $bookingItemDetail) {
            unset($bookingItemDetail['id']);
            $reference_id = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $bookingItemDetail['reference_id'] =  $reference_id;
            $bookingItemDetail['date'] =  $attributes['date'];

            $booking->bookingListDetails()->create($bookingItemDetail);
        }

        return response()->json([
            'message'   => 'Booking created successfully.',
            'data'      =>  $booking,
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BookingList  $bookingList
     * @return \Illuminate\Http\Response
     */
    public function show(BookingList $bookingList, Request $request)
    {
        // $base64String = "data:image/png;base64, " . base64_encode(QrCode::format('png')->size(100)->generate($booking->reference_id));
        // $booking->qr_code = $base64String;

        if ($request->for == 'print') {
            $bookingList->load([
                'account' => function ($q) {
                    $q->select('id', 'name', 'phone', 'trade_name');
                },
            ]);
            return new BookingListResource($bookingList);
            // return response()->json(['data' => $bookingList]);
            // $booking->qr_code = "";
            // return new BookingResource($booking);
        }

        return new BookingListResource($bookingList);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BookingList  $bookingList
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BookingList $bookingList)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BookingList  $bookingList
     * @return \Illuminate\Http\Response
     */
    public function destroy(BookingList $bookingList)
    {
        //
    }
}
