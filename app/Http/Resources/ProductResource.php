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
            // 'barcode' => $this->barcode,
            // 'sku' => $this->whenNotNull($this->sku),
            'name' => $this->name,
            'quantity' => $this->quantity,

            'default_purchase_price' => $this->whenNotNull($this->default_purchase_price),
            'default_selling_price' => $this->whenNotNull($this->default_selling_price),

        ];
    }
}
