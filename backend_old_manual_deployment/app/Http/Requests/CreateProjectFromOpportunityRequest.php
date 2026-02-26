<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateProjectFromOpportunityRequest
 * 
 * Validates manual project creation from opportunities.
 * 
 * Differences from CreateProjectRequest:
 * - client is NOT validated (always taken from opportunity)
 * - end_date is optional (nullable)
 * - status includes full project lifecycle states
 * - project_lead_id is optional (nullable)
 * 
 * This supports multi-phase opportunities where multiple projects
 * can be manually created from a single opportunity.
 */
class CreateProjectFromOpportunityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by CheckPermission middleware
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
            'contract_value' => ['required', 'numeric', 'min:0'],
            'contract_currency' => ['required', 'string', 'in:USD,UGX'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:planned,active,on_hold,completed,cancelled'],
            'project_lead_id' => ['nullable', 'integer', 'exists:users,id'],
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
            'name.max' => 'Project name cannot exceed 255 characters',
            'contract_value.required' => 'Contract value is required',
            'contract_value.numeric' => 'Contract value must be a number',
            'contract_value.min' => 'Contract value must be positive',
            'contract_currency.required' => 'Contract currency is required',
            'contract_currency.in' => 'Currency must be USD or UGX',
            'start_date.required' => 'Start date is required',
            'start_date.date_format' => 'Start date must be in YYYY-MM-DD format',
            'end_date.date_format' => 'End date must be in YYYY-MM-DD format',
            'end_date.after_or_equal' => 'End date must be on or after start date',
            'status.in' => 'Status must be one of: planned, active, on_hold, completed, cancelled',
            'project_lead_id.integer' => 'Project lead ID must be an integer',
            'project_lead_id.exists' => 'Selected project lead does not exist',
        ];
    }
}
