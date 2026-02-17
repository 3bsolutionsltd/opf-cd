<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
            'client' => 'required|string|max:255',
            'contract_value' => 'required|numeric|min:0',
            'contract_currency' => 'required|in:UGX,USD',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:planned,active,on_hold,completed,cancelled',
            'project_lead_id' => 'nullable|integer|exists:users,id'
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Project name is required',
            'name.max' => 'Project name cannot exceed 255 characters',
            'client.required' => 'Client name is required',
            'client.max' => 'Client name cannot exceed 255 characters',
            'contract_value.required' => 'Contract value is required',
            'contract_value.numeric' => 'Contract value must be a number',
            'contract_value.min' => 'Contract value must be non-negative',
            'contract_currency.required' => 'Contract currency is required',
            'contract_currency.in' => 'Contract currency must be UGX or USD',
            'start_date.required' => 'Start date is required',
            'start_date.date' => 'Start date must be a valid date',
            'end_date.required' => 'End date is required',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'status.required' => 'Project status is required',
            'status.in' => 'Status must be: planned, active, on_hold, completed, or cancelled',
            'project_lead_id.exists' => 'Selected project lead does not exist'
        ];
    }
}

