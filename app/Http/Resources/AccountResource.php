<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'phone' => $this->phone,
            'company' => $this->company,
            'account_type' => $this->account_type,
            'balance' => $this->balance,
            'purchases_amount' => $this->when($this->account_type === 'supplier', $this->purchases_amount),
            'purchases_count' => $this->when($this->account_type === 'supplier', $this->purchases_count),
            'sales_amount' => $this->when($this->account_type === 'customer', $this->sales_amount),
            'sales_count' => $this->when($this->account_type === 'customer', $this->sales_count),
        ];
    }
}
