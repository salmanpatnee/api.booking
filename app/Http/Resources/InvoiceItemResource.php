<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
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
            'item' => $this->item, 
            'amount' => $this->amount, 
            'qty' => $this->qty, 
            'vat' => $this->vat, 
            'sub_total' => $this->sub_total, 
            'net_total' => $this->net_total, 
        ];
    }
}
