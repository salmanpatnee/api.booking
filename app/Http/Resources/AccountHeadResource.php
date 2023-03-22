<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountHeadResource extends JsonResource
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
            'journal_entries' => JournalEntryResource::collection($this->whenLoaded('journalEntries')),
            'total' => [
                'debit' => $this->accountTotalDebit,
                'credit' => $this->accountTotalCredit,
            ]
        ];
    }
}
