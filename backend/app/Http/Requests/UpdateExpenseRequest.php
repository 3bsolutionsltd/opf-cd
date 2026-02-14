<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:100',
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|in:UGX,USD',
            'type' => 'sometimes|required|in:recurring,one_off',
            'frequency' => 'nullable|required_if:type,recurring|in:monthly,quarterly,annual',
            'status' => 'sometimes|nullable|in:due,paid',
            'project_id' => 'sometimes|nullable|exists:projects,id',
            'due_date' => 'sometimes|required|date',
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
            'name.required' => 'Expense name is required.',
            'name.max' => 'Expense name cannot exceed 255 characters.',
            'category.required' => 'Category is required.',
            'category.max' => 'Category cannot exceed 100 characters.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount cannot be negative.',
            'currency.required' => 'Currency is required.',
            'currency.in' => 'Currency must be one of: UGX, USD.',
            'type.required' => 'Expense type is required.',
            'type.in' => 'Type must be one of: recurring, one_off.',
            'frequency.required_if' => 'Frequency is required for recurring expenses.',
            'frequency.in' => 'Frequency must be one of: monthly, quarterly, annual.',
            'status.in' => 'Status must be one of: due, paid.',
            'project_id.exists' => 'Selected project does not exist.',
            'due_date.required' => 'Due date is required.',
            'due_date.date' => 'Due date must be a valid date.',
        ];
    }
}
