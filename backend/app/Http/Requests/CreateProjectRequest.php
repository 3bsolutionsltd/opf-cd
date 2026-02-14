<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateProjectRequest
 * 
 * Validates project creation requests.
 * 
 * Rules enforced:
 * - Required fields validation
 * - Data type validation
 * - Date format validation
 * - Contract value must be positive
 * - Currency code validation
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class CreateProjectRequest extends FormRequest
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
            'client' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,completed,on-hold'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'in:USD,UGX'],
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
            'name.required' => 'Project name is required',
            'client.required' => 'Client name is required',
            'status.in' => 'Status must be one of: active, completed, on-hold',
            'start_date.required' => 'Start date is required',
            'start_date.date_format' => 'Start date must be in YYYY-MM-DD format',
            'end_date.required' => 'End date is required',
            'end_date.date_format' => 'End date must be in YYYY-MM-DD format',
            'end_date.after_or_equal' => 'End date must be on or after start date',
            'contract_value.required' => 'Contract value is required',
            'contract_value.min' => 'Contract value must be positive',
            'currency.required' => 'Currency is required',
            'currency.in' => 'Currency must be USD or UGX',
        ];
    }
}
