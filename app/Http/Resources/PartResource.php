<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PartResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'code' => $this->code,
            'cost' => $this->whenNotNull($this->cost, 0),
            'price' => $this->whenNotNull($this->price, 0),
            'quantity' => $this->quantity ? $this->quantity : 0,
            'units_sold' => $this->units_sold,
            'purchase_date' => $this->when(!is_null($this->purchase_date), (new Carbon($this->purchase_date))->format('Y-m-d'))
        ];
    }
}
