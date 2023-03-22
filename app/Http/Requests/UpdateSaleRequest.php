<?php

namespace App\Http\Requests;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
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

            // 'discount_type' => 'nullable|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric',
            // 'discount_rate' => 'nullable|numeric',

            'payment_method_id' => 'required|exists:payment_methods,id',
            'bank_account_id' => Rule::requiredIf($this->payment_method_id == PaymentMethod::BANK_ID),

            'status' => [
                'required',
                'in:ordered',
            ],

            'is_deliverable' => 'nullable|boolean',

            'shipping_details' => 'nullable',
            // 'shipping_address' => 'required_if:is_deliverable,true',
            'shipping_address' => 'nullable',
            'shipping_charges' => 'required_if:is_deliverable,true|numeric',
            // 'shipping_status' => 'required_if:is_deliverable,true|in:ordered,packed,shipped',


            'sale_details' => 'required|array',
            'sale_details.*.id'=>'nullable',
            'sale_details.*.product_id' => 'required|exists:products,id',
            'sale_details.*.original_price' => 'required|numeric',
            'sale_details.*.discount_rate' => 'nullable|numeric',
            'sale_details.*.price' => 'required|numeric',
            'sale_details.*.quantity' => 'required|numeric|min:1',


        ];
    }
}
