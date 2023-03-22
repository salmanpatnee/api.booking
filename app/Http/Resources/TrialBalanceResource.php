<?php

namespace App\Http\Resources;

use App\Models\AccountHead;
use Illuminate\Http\Resources\Json\JsonResource;

class TrialBalanceResource extends JsonResource
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
            'id'     => $this->id,
            'name'   => $this->name,
            'debit'  => $this->accountTotalDebit,
            'credit' => $this->accountTotalCredit,
        ];
    }


    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = AccountHead::class;
}
