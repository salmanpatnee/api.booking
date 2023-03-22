<?php

namespace App\Http\Requests;

use App\Models\AccountHead;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseStoreRequest extends FormRequest
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
            'expense_type_id'   => 'required|exists:expense_types,id',
            'payment_method_id' => 'required|in:' . AccountHead::CASH_ID . ',' . AccountHead::BANK_ID,
            'date'              => 'required|date',
            'description'       => 'nullable',
            'amount'            => 'required|numeric',
        ];
    }

    public function messages()
    {
        return [
            'expense_type_id.required'  => 'Expense type is required.',
            'amount.required'           => 'Amount is required.'
        ];
    }
}
