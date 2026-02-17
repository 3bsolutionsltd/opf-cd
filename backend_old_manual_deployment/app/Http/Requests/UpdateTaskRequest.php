<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
            'category' => 'sometimes|nullable|string|max:100',
            'weight' => 'sometimes|required|numeric|min:0|max:100',
            'progress' => 'sometimes|nullable|numeric|min:0|max:100',
            'status' => 'sometimes|nullable|in:todo,in_progress,done',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'start_date' => 'sometimes|nullable|date',
            'due_date' => 'sometimes|nullable|date|after_or_equal:start_date',
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
            'name.required' => 'Task name is required.',
            'name.max' => 'Task name cannot exceed 255 characters.',
            'weight.required' => 'Task weight is required.',
            'weight.numeric' => 'Task weight must be a number.',
            'weight.min' => 'Task weight cannot be negative.',
            'weight.max' => 'Task weight cannot exceed 100.',
            'progress.numeric' => 'Progress must be a number.',
            'progress.min' => 'Progress cannot be negative.',
            'progress.max' => 'Progress cannot exceed 100.',
            'status.in' => 'Status must be one of: todo, in_progress, done.',
            'assigned_to.exists' => 'Assigned user does not exist.',
            'due_date.after_or_equal' => 'Due date must be after or equal to start date.',
        ];
    }
}

