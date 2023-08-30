<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemPartResource extends JsonResource
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
            'part_id' => $this->part_id, 
            'product_name' => $this->part->name, 
            'stock' => $this->part->quantity, 
            'quantity' => $this->quantity, 
            'cost' => $this->cost, 
            'price' => $this->price, 
            'total' => $this->total, 
        ];
    }
}
