<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
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
            'date' => $this->date, 
            'reference_number' => $this->reference_number, 
            'account_id' => $this->account_id, 
            'products_count' => $this->products_count, 
            'gross_amount' => $this->gross_amount, 
            'discount_type' => $this->discount_type, 
            'discount_rate' => $this->discount_rate, 
            'discount_amount' => $this->discount_amount, 
            'net_amount' => $this->net_amount, 
            'paid_amount' => $this->paid_amount, 
            'status' => $this->status, 
            'payment_status' => $this->payment_status, 
            'purchase_order_id' => $this->purchase_order_id, 
            'created_by' => $this->created_by, 
            'updated_by' => $this->updated_by, 
            'created_at' => $this->created_at, 
            'updated_at' => $this->updated_at, 
            'deleted_at' => $this->deleted_at, 
            'account' => $this->account

        ];

        // {"id":2,"date":"2023-02-03","reference_number":"10002","account_id":296,"products_count":4,"gross_amount":"5900.00","discount_type":null,"discount_rate":null,"discount_amount":0,"net_amount":"5900.00","paid_amount":"5500.00","status":"ordered","payment_status":"due","purchase_order_id":null,"created_by":1,"updated_by":null,"created_at":"2023-02-03T06:47:40.000000Z","updated_at":"2023-02-03T06:51:55.000000Z","deleted_at":null,"account":{"id":296,"name":"Supplier 296"}}
    }
}
