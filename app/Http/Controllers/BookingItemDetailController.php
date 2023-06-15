<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingItemResource;
use App\Http\Resources\BookingListResource;
use App\Models\Account;
use App\Models\BookingList;
use App\Models\BookingListDetails;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function show(BookingListDetails $booking_item)
    {
        if (request('for') == 'print') {
            return new BookingItemResource($booking_item);
        }

        $bookingList = BookingList::findOrFail($booking_item->booking_list_id);

        return new BookingListResource($bookingList);
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

    public function sendMessage()
    {
        $attributes = request()->all();

        $basic  = new \Vonage\Client\Credentials\Basic(env('VONAGE_KEY'), env('VONAGE_SECRET'));
        $client = new \Vonage\Client($basic);

        $response = $client->sms()->send(
            new \Vonage\SMS\Message\SMS($attributes['phone'], "iCrack", $attributes['message'])
        );

        $message = $response->current();

        if ($message->getStatus() == 0) {
            return response()->json([
                'message'   => 'Message sent.',
                'status'    => 'success'
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'message'   => 'Message sending failed. ' . $message->getStatus(),
                'status'    => 'error'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
