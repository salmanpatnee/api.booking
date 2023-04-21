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
            'employee_id' => 'required|exists:employees,id',
            'device_name' => 'required|string',
            'imei' => 'required|string|min:15|max:15',
            'device_type' => 'required|string',
            'device_make' => 'required|string',
            'device_model' => 'required|string',
            'issue' => 'required|string',
            'issue_type' => 'required|string',
            'date' => 'required|date',
            'serial_no' => 'nullable',
            'customer_comments' => 'nullable',
            'notes' => 'nullable',
            'estimated_cost' => 'required|numeric',
            // 'status' => 'required|in:draft,inprocess,completed, customer collected - Payment pending, customer collected - CBR, cannot repaired',

        ];
    }
}
