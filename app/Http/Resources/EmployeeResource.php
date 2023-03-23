<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->when(!is_null($this->phone), $this->phone),
            'address' => $this->when(!is_null($this->address), $this->address),
            'joining_date' => $this->when(!is_null($this->joining_date), (new Carbon($this->joining_date))->format('Y-m-d'))
        ];
    }
}
