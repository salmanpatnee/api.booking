<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingStoreRequest extends FormRequest
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
            'account_id' => 'required|exists:accounts,id',
            'employee_id' => 'nullable|exists:employees,id',
            'device_name' => 'nullable|string',
            'imei' => 'nullable|string|min:15|max:15',
            'device_type' => 'nullable|string',
            'device_make' => 'nullable|string',
            'device_model' => 'nullable|string',
            'issue' => 'nullable|string',
            'issue_type' => 'nullable|string',
            'date' => 'required|date',
            'estimated_delivery_date' => 'nullable|date',
            'serial_no' => 'nullable',
            'customer_comments' => 'nullable',
            'notes' => 'nullable',
            'estimated_cost' => 'nullable|numeric',
            // 'status' => 'required|in:draft,inprocess,completed, customer collected - Payment pending, customer collected - CBR, cannot repaired',

        ];
    }
}
