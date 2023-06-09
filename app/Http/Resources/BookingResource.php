<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'account' => $this->account,
            'employee' => $this->employee,
            'device_name' => $this->device_name,
            'imei' => $this->imei,
            'device_type' => $this->device_type,
            'device_make' => $this->device_make,
            'device_model' => $this->device_model,
            'issue' => $this->issue,
            'issue_type' => $this->issue_type,
            'date' => $this->date,
            'delivered_date' => $this->delivered_date,
            'estimated_delivery_date' => $this->estimated_delivery_date,
            'serial_no' => $this->serial_no,
            'customer_comments' => $this->customer_comments,
            'notes' => $this->notes,
            // 'products_count' => $this->products_count,
            'estimated_cost' => $this->estimated_cost,
            'charges' => $this->charges,
            'purchase_amount' => $this->purchase_amount,
            'status' => $this->status,
            'booking_details' => $this->bookingDetails ? $this->bookingDetails : [], 
            'qr_code' => $this->qr_code
        ];
    }
}
