<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
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
            'date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',

            // 'discount_type' => 'nullable|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric',
            // 'discount_rate' => 'nullable|numeric',

            'payment_method_id' => 'required|exists:payment_methods,id',            

            'status' => [
                'required',
                'in:draft,ordered',
            ],

            // 'status' => [
            //     'required',
            //     Rule::when(Request::isMethod("post"), ['in:draft,ordered']),
            //     Rule::when(Request::isMethod("patch"), ['required|in:ordered']),
            // ],

            'is_deliverable' => 'nullable|boolean',

            'shipping_details' => 'nullable',
            'shipping_address' => 'required_if:is_deliverable,true',
            'shipping_charges' => 'required_if:is_deliverable,true|numeric',
            // 'shipping_status' => 'required_if:is_deliverable,true|in:ordered,packed,shipped',

            'paid_amount' => 'nullable|numeric',
            'returned_amount' => 'nullable|numeric',

            'sale_details' => 'required|array',
            'sale_details.*.product_id' => 'required|exists:products,id',
            'sale_details.*.stock' => 'required|numeric',
            'sale_details.*.original_price' => 'required|numeric',
            'sale_details.*.discount_rate' => 'nullable|numeric',
            'sale_details.*.price' => 'required|numeric',
            'sale_details.*.quantity' => 'required|numeric|lte:sale_details.*.stock',


        ];
    }
}
