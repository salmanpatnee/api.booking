<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
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
            'booking_list_id' => $this->bookingList->id, 
            'booking_list_reference_id' => $this->bookingList->reference_id, 
            'reference_id' => $this->reference_id, 
            'device_name' => $this->device_name, 
            'imei' => $this->imei, 
            'device_type' => $this->device_type, 
            'device_make' => $this->device_make, 
            'device_model' => $this->device_model, 
            'issue' => $this->issue, 
            'issue_type' => $this->issue_type, 
            'estimated_delivery_date' => $this->estimated_delivery_date, 
            'serial_no' => $this->serial_no, 
            'customer_comments' => $this->customer_comments, 
            'notes' => $this->notes, 
            'estimated_cost' => $this->estimated_cost, 
            'charges' => $this->charges, 
            'purchase_amount' => $this->purchase_amount, 
            'status' => $this->status, 
            'employee_id' => $this->employee ? $this->employee->id : "", 
            'employee' => new EmployeeResource($this->whenNotNull($this->employee)), 
            'account' => new AccountResource($this->whenNotNull($this->bookingList->account)), 
            'date' => $this->date, 
            'parts' => $this->bookingItemParts ? BookingItemPartResource::collection($this->bookingItemParts) : []
        ];
    }
}
