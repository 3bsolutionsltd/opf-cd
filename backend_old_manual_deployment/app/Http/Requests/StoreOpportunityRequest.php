<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Opportunity Request
 * 
 * Validates data for creating new opportunities.
 */
class StoreOpportunityRequest extends FormRequest
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
            'client' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'estimated_value' => 'required|numeric|min:0',
            'currency' => 'required|string|in:UGX,USD',
            'probability' => 'required|numeric|min:0|max:100',
            'stage' => 'required|in:lead,qualified,proposal,negotiation,won,lost',
            'source' => 'required|string|max:100',
            'owner' => 'required|integer|exists:users,id',
            'expected_close_date' => 'required|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'client.required' => 'Client name is required.',
            'client.string' => 'Client name must be text.',
            'client.max' => 'Client name cannot exceed 255 characters.',
            'description.required' => 'Description is required.',
            'description.string' => 'Description must be text.',
            'description.max' => 'Description cannot exceed 255 characters.',
            'estimated_value.required' => 'Estimated value is required.',
            'estimated_value.numeric' => 'Estimated value must be a number.',
            'estimated_value.min' => 'Estimated value cannot be negative.',
            'probability.required' => 'Probability is required.',
            'probability.numeric' => 'Probability must be a number.',
            'probability.min' => 'Probability must be at least 0%.',
            'probability.max' => 'Probability cannot exceed 100%.',
            'stage.required' => 'Stage is required.',
            'stage.in' => 'Stage must be one of: lead, qualified, proposal, negotiation, won, or lost.',
            'source.required' => 'Source is required.',
            'source.string' => 'Source must be text.',
            'source.max' => 'Source cannot exceed 100 characters.',
            'owner.required' => 'Owner is required.',
            'owner.integer' => 'Owner must be a valid user ID.',
            'owner.exists' => 'Selected owner does not exist.',
            'expected_close_date.required' => 'Expected close date is required.',
            'expected_close_date.date' => 'Expected close date must be a valid date.',
            'expected_close_date.after_or_equal' => 'Expected close date cannot be in the past.',
        ];
    }
}
