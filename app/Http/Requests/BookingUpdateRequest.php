<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'device_name' => 'required|string',
            'model_no' => 'required|string',
            'imei' => 'required|string|min:15|max:15',
            'issue' => 'required|string',
            'estimated_cost' => 'required|numeric', 
            'charges' => 'required_if:status,complete|numeric',
            'status' => 'required|in:in progress,repaired,complete,can not be repaired,customer collected CBR,customer collected payment pending,shop property,awaiting customer response,awaiting parts',
        ];
    }
}
