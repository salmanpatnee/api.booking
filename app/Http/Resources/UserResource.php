<?php

namespace App\Http\Resources;

use App\Models\CashRegister;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Get register entry which end_datetime is null and user_id == current user id
        return [
            'id' => $this->id, 
            'name' => $this->name, 
            'email' => $this->email, 
            'location_id' => $this->location_id, 
            'location' => $this->location, 
            'role' => $this->roles->pluck('name'), 
            'permissions' => $this->getPermissionsViaRoles()->pluck('name'), 
            'cash_registers' => CashRegister::where('user_id', '=', $this->id)->whereNull('end_datetime')->first()
        ];
    }
}
