<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
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
            'reference_number' => 'required|unique:purchases,reference_number',

            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric',
            'discount_rate' => 'nullable|numeric',

            'tax_amount' => 'nullable|numeric',

            'paid_amount' => 'numeric',
            'payment_method_id' => 'required|exists:payment_methods,id',

            'status' => 'required|in:draft,ordered,received',

            'purchase_details'=>'required|array',
            'purchase_details.*.product_id' => 'required|exists:products,id',
            'purchase_details.*.price' => 'required|numeric',
            'purchase_details.*.quantity' => 'required|numeric',
            'purchase_details.*.amount' => 'required|numeric',
            'purchase_details.*.quantity_boxes' => 'nullable|numeric',
            'purchase_details.*.units_in_box' => 'nullable|numeric',
            // 'purchase_details.*.sale_price_box' => 'nullable|numeric',
            'purchase_details.*.quantity_strips' => 'nullable|numeric',
            'purchase_details.*.units_in_strip' => 'nullable|numeric',
            // 'purchase_details.*.sale_price_strip' => 'nullable|numeric',
            'purchase_details.*.sale_price' => 'required|numeric|gt:purchase_details.*.price',
            'purchase_details.*.expiry_date' => 'required|date|after_or_equal:'.now()->addMonths(1)->format("Y-m-d"),
        ];
    }
}
