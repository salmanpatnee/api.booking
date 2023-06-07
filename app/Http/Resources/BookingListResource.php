<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id, 
            'reference_id' => $this->reference_id, 
            'date' => $this->date,  
            'account' => new AccountResource($this->account),
            'booking_list_details' => BookingItemResource::collection($this->bookingListDetails), 
            'booking_items_count' => count($this->bookingListDetails)
        ];
    }
}
