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
            'employee_id' => 'nullable|exists:employees,id',
            'device_name' => 'nullable|string',
            'device_model' => 'nullable|string',
            'imei' => 'nullable|string|min:15|max:15',
            'device_type' => 'nullable|string',
            'device_make' => 'nullable|string',
            'device_model' => 'nullable|string',
            'issue' => 'nullable|string',
            'issue_type' => 'nullable|string',
            'serial_no' => 'nullable',
            'estimated_cost' => 'nullable|numeric',
            'customer_comments' => 'nullable',
            'notes' => 'nullable',
            'charges' => 'nullable',
            'status' => 'nullable|in:in progress,repaired,complete,can not be repaired,customer collected CBR,customer collected payment pending,shop property,awaiting customer response,awaiting parts',
        ];
    }
}
