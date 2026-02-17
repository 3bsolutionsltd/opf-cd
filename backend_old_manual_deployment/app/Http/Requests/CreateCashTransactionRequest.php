<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateCashTransactionRequest
 * 
 * Validates cash transaction creation requests.
 * 
 * Rules enforced:
 * - Required fields validation
 * - Amount must be positive
 * - Valid transaction type
 * - Account must exist
 * - Currency validation
 * - Date format validation
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class CreateCashTransactionRequest extends FormRequest
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
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'transaction_date' => ['required', 'date', 'date_format:Y-m-d'],
            'type' => ['required', 'in:inflow,outflow'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'in:USD,UGX'],
            'description' => ['required', 'string', 'max:500'],
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
            'account_id.required' => 'Account is required',
            'account_id.exists' => 'Selected account does not exist',
            'transaction_date.required' => 'Transaction date is required',
            'transaction_date.date_format' => 'Transaction date must be in YYYY-MM-DD format',
            'type.required' => 'Transaction type is required',
            'type.in' => 'Type must be inflow or outflow',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be greater than 0',
            'currency.required' => 'Currency is required',
            'currency.in' => 'Currency must be USD or UGX',
            'description.required' => 'Description is required',
            'description.max' => 'Description must not exceed 500 characters',
        ];
    }
}
