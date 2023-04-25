<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingStoreRequest;
use App\Http\Requests\BookingUpdateRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginate  = request('paginate', 30);
        $bookings = [];

        if(request('for') == 'sales'){
            $bookings = Booking::query()->indexQuery()->completed()->paginate($paginate);
        } else {

            $bookings = Booking::query()->indexQuery()->paginate($paginate);
        }

        return BookingResource::collection($bookings);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BookingStoreRequest $request)
    {
        $attributes = $request->all();
        // $attributes['reference_id'] = (int)(uniqid(mt_rand(1000, 9000), true));
        $reference_id = str_pad(mt_rand(1,999999), 6, '0', STR_PAD_LEFT);
        $attributes['reference_id'] = $reference_id;

        $booking = Booking::create($attributes);

        return response()->json([
            'message'   => 'Booking created successfully.',
            'data'      =>  new BookingResource($booking),
            'status'    => 'success'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Booking $booking, Request $request)
    {
        if ($request->for == 'print') {
            $base64String = "data:image/png;base64, " . base64_encode(QrCode::format('png')->size(100)->generate($booking->reference_id));
            $booking->qr_code = $base64String;
            $booking->load([
                'account' => function ($q) {
                    $q->select('id', 'name', 'phone');
                },
            ]);
            return response()->json(['data' => $booking]);
            // $booking->qr_code = "";
            // return new BookingResource($booking);
        }


        return new BookingResource($booking);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  
     * \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(BookingUpdateRequest $request, Booking $booking)
    {
        $attributes = $request->all();

        $bookingDetails = $booking->bookingDetails;
        $products = [];
        $bookingDetailData = [];
        $errorMessage = null;

        foreach ($request->booking_details as $i => $requestBookingDetail) {
            $products[$i] = Product::find($requestBookingDetail['product_id']);


            /* available quantity = ordered quantity + product quantity */
            $availableQuantity = $products[$i]->quantity;


            if (array_key_exists("id", $requestBookingDetail)) {
                $saleDetail = $bookingDetails->where('id', $requestBookingDetail['id'])->first();
                $availableQuantity += $saleDetail->quantity;
            }


            /* quantity validation */
            if ($requestBookingDetail['quantity'] > $availableQuantity) {
                $errors[] = ['booking_details.' . $i . '.quantity' => ["The selected sale_details.{$i}.quantity can not be greater than {$products[$i]->quantity}"]];
                if (!$errorMessage) {
                    $errorMessage = "The selected booking_details.{$i}.quantity can not be greater than {$products[$i]->quantity}";
                }
            }


            $bookingDetailAmount = $requestBookingDetail['quantity'] * $requestBookingDetail['price'];

            // $quantityCount += $requestBookingDetail['quantity'];
            // $grossAmount += $requestBookingDetail['quantity'] * $requestBookingDetail['original_price'];

            $bookingDetailData[] = [
                'id' => $requestBookingDetail['id'] ?? null,
                'product_id' => $requestBookingDetail['product_id'],
                'price' => $requestBookingDetail['price'],
                'quantity' => $requestBookingDetail['quantity'],
                'amount' => $bookingDetailAmount,
                'product' => $products[$i],
            ];
        }

        unset($i);

        if (!empty($errors)) {
            return response()->json(["message" => $errorMessage, "errors" => $errors], 422);
        }

        $inventoryService = new InventoryService();
        $saleDetailIds = $bookingDetails->pluck('id', 'id');
        $purchaseAmount = 0;

        DB::beginTransaction();

        foreach ($bookingDetailData as $saleDetailEntry) {
            $saleDetailEntry['booking_id'] = $booking->id;

            /* check if old entry exist */
            if ($saleDetailEntry['id']) {
                $saleDetail = $bookingDetails->where('id', $saleDetailEntry['id'])->first();
                /* check if entry is change */
                if ($saleDetail->quantity != $saleDetailEntry['quantity'] || $saleDetail->price != $saleDetailEntry['price']) {
                    /* reverse inventory */
                    $inventoryService->reverseInventoryFromHolder(1, $booking->id, $saleDetail->product_id);
                    $inventoryService->updateProductQuantityOnSalesReturn($saleDetailEntry['product'], $saleDetail->quantity);

                    /* store inventory */
                    $saleDetailPurchaseAmount = $inventoryService->updateInventoryOnSale($booking->id, $booking->date, $saleDetailEntry['product_id'], $saleDetailEntry['price'], $saleDetailEntry['quantity'], 0);
                    $inventoryService->updateProductQuantityOnSale($saleDetailEntry['product'], $saleDetailEntry['quantity']);

                    /* update sale detail */
                    $saleDetailEntry['purchase_amount'] = $saleDetailPurchaseAmount;
                    $saleDetail->update($saleDetailEntry);

                    $purchaseAmount += $saleDetailPurchaseAmount;
                } else {
                    /* move quantity from holder to sold */
                    $inventoryService->updateInventoryFromHolder(1, $booking->id, $booking->date, $saleDetailEntry['product_id']);

                    $purchaseAmount += $saleDetail->purchase_amount;
                }
                $saleDetailIds->forget($saleDetail->id);
            } else {
                $saleDetailPurchaseAmount = $inventoryService->updateInventoryOnSale($booking->id, $booking->date, $saleDetailEntry['product_id'], $saleDetailEntry['price'], $saleDetailEntry['quantity'], 0);
                $inventoryService->updateProductQuantityOnSale($saleDetailEntry['product'], $saleDetailEntry['quantity']);
                $saleDetailEntry['purchase_amount'] = $saleDetailPurchaseAmount;
                $saleDetail = BookingDetail::create($saleDetailEntry);

                $purchaseAmount += $saleDetailPurchaseAmount;
            }
        }

        foreach ($saleDetailIds as $saleDetailId) {
            $saleDetail = $bookingDetails->where('id', $saleDetailId)->first();

            $product = $saleDetail->product;

            $inventoryService->reverseInventoryFromHolder(1, $booking->id, $saleDetail->product_id, $saleDetail->quantity);
            $inventoryService->updateProductQuantityOnSalesReturn($product, $saleDetail->quantity);

            $saleDetail = $bookingDetails->where('id', $saleDetailId)->first();
            $saleDetail->delete();
        }

        $attributes['purchase_amount'] = $purchaseAmount;

        if($attributes['status'] == 'complete'){
            $attributes['delivered_date'] = date('Y-m-d');
        }

        $booking->update($attributes);

        DB::commit();

        return response()->json([
            'message'   => 'Booking updated successfully.',
            'data'      =>  new BookingResource($booking),
            'status'    => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
