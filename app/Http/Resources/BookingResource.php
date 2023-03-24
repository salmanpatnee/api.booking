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
            'booking_id' => $this->booking_id,
            'account' => $this->account,
            'employee' => $this->employee,
            'device_name' => $this->device_name,
            'model_no' => $this->model_no,
            'imei' => $this->imei,
            'issue' => $this->issue,
            'date' => $this->date,
            // 'products_count' => $this->products_count,
            'charges' => $this->charges,
            // 'purchase_amount' => $this->purchase_amount,
            'status' => $this->status,
        ];
    }
}
