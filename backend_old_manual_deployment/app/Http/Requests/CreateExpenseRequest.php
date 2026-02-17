<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateExpenseRequest
 * 
 * Validates expense creation requests.
 * 
 * Rules enforced:
 * - Required fields validation
 * - Amount must be positive
 * - Valid expense type and status
 * - Date format validation
 * - Currency validation
 * - Optional project ID must exist
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 6
 */
class CreateExpenseRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'in:USD,UGX'],
            'type' => ['required', 'in:operational,project-related'],
            'status' => ['required', 'in:due,paid'],
            'due_date' => ['required', 'date', 'date_format:Y-m-d'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
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
            'description.required' => 'Expense description is required',
            'description.max' => 'Description must not exceed 500 characters',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be greater than 0',
            'currency.required' => 'Currency is required',
            'currency.in' => 'Currency must be USD or UGX',
            'type.required' => 'Expense type is required',
            'type.in' => 'Type must be operational or project-related',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be due or paid',
            'due_date.required' => 'Due date is required',
            'due_date.date_format' => 'Due date must be in YYYY-MM-DD format',
            'project_id.exists' => 'Selected project does not exist',
        ];
    }
}
