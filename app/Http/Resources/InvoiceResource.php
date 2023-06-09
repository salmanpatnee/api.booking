<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'invoice_no' => $this->invoice_no, 
            'client_name' => $this->client_name, 
            'phone' => $this->phone, 
            'notes' => $this->notes, 
            'tax_amount' => $this->tax_amount, 
            'total' => $this->total, 
            'net_total' => $this->net_total, 
            'date' => $this->date, 
            'invoice_items' => InvoiceItemResource::collection($this->invoiceItems)
        ];
    }
}
