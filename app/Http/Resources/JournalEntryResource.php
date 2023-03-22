<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
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
            'id'          => $this->id,
            'date'        => Carbon::parse($this->date)->format('d M Y'),
            'description' => $this->forAccountHead->name,
            'debit'       => $this->debit,
            'credit'      => $this->credit,

        ];
    }
}
