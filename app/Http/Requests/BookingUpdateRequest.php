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
            'device_model' => 'required|string',
            'imei' => 'required_if:device_type,==,Smartphones|string|min:15|max:15',
            'device_type' => 'required|string',
            'device_make' => 'required|string',
            'device_model' => 'required|string',
            'issue' => 'required|string',
            'issue_type' => 'required|string',
            'serial_no' => 'nullable',
            'estimated_cost' => 'required|numeric',
            'customer_comments' => 'nullable',
            'notes' => 'nullable',
            'charges' => 'required_if:status,complete',
            'status' => 'required|in:in progress,repaired,complete,can not be repaired,customer collected CBR,customer collected payment pending,shop property,awaiting customer response,awaiting parts',
        ];
    }
}
