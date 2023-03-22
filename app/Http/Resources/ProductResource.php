<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'category' => $this->category->name,
            'brand' => $this->brand_id ? $this->brand->name : null,
            'barcode' => $this->barcode,
            // 'sku' => $this->whenNotNull($this->sku),
            'name' => $this->name,
            'quantity' => $this->quantity,
            'quantity_threshold' => $this->quantity_threshold,
            'vat' => $this->whenNotNull($this->vat_amount),
            'default_purchase_price' => $this->whenNotNull($this->default_purchase_price),
            'default_selling_price' => $this->whenNotNull($this->default_selling_price),
            'discount_rate_cash' => $this->whenNotNull($this->discount_rate_cash),
            'discount_rate_card' => $this->whenNotNull($this->discount_rate_card),
            'discount_rate_shipment' => $this->whenNotNull($this->discount_rate_shipment),
            'uom_of_boxes' => $this->whenNotNull($this->uom_of_boxes),
            'uom_of_strips' => $this->whenNotNull($this->uom_of_strips),
        ];
    }
}
