<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Record Payment Request
 * 
 * Validates payment recording data when marking a milestone as paid.
 * Ensures account_id and transaction_date are provided.
 */
class RecordPaymentRequest extends FormRequest
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
            'transaction_date' => 'required|date|date_format:Y-m-d',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_id.required' => 'Account is required to record payment.',
            'account_id.integer' => 'Invalid account ID.',
            'account_id.exists' => 'The selected account does not exist.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
            'transaction_date.date_format' => 'Transaction date must be in YYYY-MM-DD format.',
        ];
    }
}
