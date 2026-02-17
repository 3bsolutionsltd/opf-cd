<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Cash Transaction Request
 * 
 * Validates data for creating new cash transactions.
 */
class StoreCashTransactionRequest extends FormRequest
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
            'account_id' => 'required|integer|exists:accounts,id',
            'type' => 'required|in:inflow,outflow',
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required|in:UGX,USD',
            'source_type' => 'required|string|max:50',
            'source_id' => 'required|integer',
            'transaction_date' => 'required|date',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'account_id.required' => 'Account is required.',
            'account_id.integer' => 'Account must be a valid ID.',
            'account_id.exists' => 'Selected account does not exist.',
            'type.required' => 'Transaction type is required.',
            'type.in' => 'Transaction type must be either inflow or outflow.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.gt' => 'Amount must be greater than zero.',
            'currency.required' => 'Currency is required.',
            'currency.in' => 'Currency must be either UGX or USD.',
            'source_type.required' => 'Source type is required.',
            'source_type.string' => 'Source type must be text.',
            'source_type.max' => 'Source type cannot exceed 50 characters.',
            'source_id.required' => 'Source ID is required.',
            'source_id.integer' => 'Source ID must be a valid number.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
        ];
    }
}
