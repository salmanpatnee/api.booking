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



        $paginate  = request('paginate', 50);
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
        $attributes = $request->all();

        foreach ($attributes['booking_list_details'] as $bookingItemDetail) {

            $bookingDetail = BookingListDetails::find($bookingItemDetail['id']);

            $bookingDetail->update([
                'employee_id' => $bookingItemDetail['employee_id'],
                'device_name' => $bookingItemDetail['device_name'],
                'imei' => $bookingItemDetail['imei'],
                'device_type' => $bookingItemDetail['device_type'],
                'device_make' => $bookingItemDetail['device_make'],
                'device_model' => $bookingItemDetail['device_model'],
                'issue' => $bookingItemDetail['issue'],
                'issue_type' => $bookingItemDetail['issue_type'],
                'estimated_delivery_date' => $bookingItemDetail['estimated_delivery_date'],
                'delivered_date' => isset($bookingItemDetail['delivered_date']) ? $bookingItemDetail['delivered_date'] : null,
                'serial_no' => $bookingItemDetail['serial_no'],
                'customer_comments' => $bookingItemDetail['customer_comments'],
                'notes' => $bookingItemDetail['notes'],
                'estimated_cost' => $bookingItemDetail['estimated_cost'],
                'charges' => $bookingItemDetail['charges'],
                'status' => $bookingItemDetail['status'],
            ]);

            /*
            unset($bookingItemDetail['id']);
            unset($bookingItemDetail['account']);
            if (isset($bookingItemDetail['employee'])) {
                $bookingItemDetail['employee_id'] = $bookingItemDetail['employee']['id'];
                unset($bookingItemDetail['employee']);
            } else {
                $bookingItemDetail['employee_id'] = null;
            }

            $bookingList->bookingListDetails()->update($bookingItemDetail);
            */
        }

        return response()->json([
            'message'   => 'Booking updated successfully.',
            'status'    => 'success'
        ], Response::HTTP_OK);
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
