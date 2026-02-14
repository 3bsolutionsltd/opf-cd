<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateOpportunityRequest
 * 
 * Validates opportunity creation requests.
 * 
 * Rules enforced:
 * - Required fields validation
 * - Value must be positive
 * - Close probability 0-100
 * - Valid stage
 * - Currency validation
 * - Date format validation
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class CreateOpportunityRequest extends FormRequest
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
            'stage' => ['required', 'in:lead,qualified,proposal,negotiation,closed-won,closed-lost'],
            'value' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'in:USD,UGX'],
            'close_probability' => ['required', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['required', 'date', 'date_format:Y-m-d'],
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
            'name.required' => 'Opportunity name is required',
            'client.required' => 'Client name is required',
            'stage.required' => 'Stage is required',
            'stage.in' => 'Stage must be one of: lead, qualified, proposal, negotiation, closed-won, closed-lost',
            'value.required' => 'Value is required',
            'value.min' => 'Value must be positive',
            'currency.required' => 'Currency is required',
            'currency.in' => 'Currency must be USD or UGX',
            'close_probability.required' => 'Close probability is required',
            'close_probability.min' => 'Close probability must be between 0 and 100',
            'close_probability.max' => 'Close probability must be between 0 and 100',
            'expected_close_date.required' => 'Expected close date is required',
            'expected_close_date.date_format' => 'Expected close date must be in YYYY-MM-DD format',
        ];
    }
}
