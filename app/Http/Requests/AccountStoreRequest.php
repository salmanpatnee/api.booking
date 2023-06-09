<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountStoreRequest extends FormRequest
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
            'name' => 'required',
            'email' => ['nullable', Rule::unique('accounts')->where(fn ($query) => $query->where('account_type', $this->account_type))->ignore($this->id)],

            'trade_name' => 'nullable',

            'phone' => ['nullable', Rule::unique('accounts')->where(fn ($query) => $query->where('account_type', $this->account_type))->ignore($this->id)],
            'address' => 'nullable',
            'balance' => 'required',

            'account_type' => 'required|in:supplier,customer,both'
        ];
    }
}
