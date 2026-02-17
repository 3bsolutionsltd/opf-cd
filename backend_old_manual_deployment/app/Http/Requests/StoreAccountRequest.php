<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Account Request
 * 
 * Validates data for creating new accounts.
 */
class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,mobile_money,cash',
            'currency' => 'required|in:UGX,USD',
            'opening_balance' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Account name is required.',
            'name.string' => 'Account name must be text.',
            'name.max' => 'Account name cannot exceed 255 characters.',
            'type.required' => 'Account type is required.',
            'type.in' => 'Account type must be one of: bank, mobile_money, or cash.',
            'currency.required' => 'Currency is required.',
            'currency.in' => 'Currency must be either UGX or USD.',
            'opening_balance.required' => 'Opening balance is required.',
            'opening_balance.numeric' => 'Opening balance must be a number.',
            'opening_balance.min' => 'Opening balance cannot be negative.',
        ];
    }
}
