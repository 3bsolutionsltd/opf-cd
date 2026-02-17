<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateAccountRequest
 * 
 * Validates account creation requests.
 * 
 * Rules enforced:
 * - Required fields validation
 * - Valid account type
 * - Opening balance must be non-negative
 * - Currency validation
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class CreateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:bank,mobile_money,cash'],
            'currency' => ['required', 'string', 'in:USD,UGX'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Account name is required',
            'type.required' => 'Account type is required',
            'type.in' => 'Type must be one of: bank, mobile_money, cash',
            'currency.required' => 'Currency is required',
            'currency.in' => 'Currency must be USD or UGX',
            'opening_balance.required' => 'Opening balance is required',
            'opening_balance.min' => 'Opening balance must be non-negative',
        ];
    }
}
