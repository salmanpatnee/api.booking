<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // 'date'            => Carbon::parse($this->date)->format('d M Y'),
        return [
            'id'              => $this->id,
            'expense_type_id' => isset($this->expenseType) ? $this->expenseType->id : '',
            'expense_type'    => isset($this->expenseType) ? $this->expenseType->name : '',
            'date'            => $this->date,
            'description'     => $this->description,
            'amount'          => $this->amount
        ];
    }
}
