<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Opportunity Request
 * 
 * Validates data for updating existing opportunities.
 * All fields use 'sometimes' modifier for partial updates.
 */
class UpdateOpportunityRequest extends FormRequest
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
            'client' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:255',
            'estimated_value' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|in:UGX,USD',
            'probability' => 'sometimes|required|numeric|min:0|max:100',
            'stage' => 'sometimes|required|in:lead,qualified,proposal,negotiation,won,lost',
            'source' => 'sometimes|required|string|max:100',
            'owner' => 'sometimes|required|integer|exists:users,id',
            'expected_close_date' => 'sometimes|required|date',
            // BANT lead qualification fields (optional)
            'budget_confirmed' => 'sometimes|in:yes,no,unknown',
            'authority_level' => 'sometimes|in:decision_maker,influencer,user,unknown',
            'need_validation' => 'sometimes|in:critical,important,nice_to_have,unknown',
            'timeline_urgency' => 'sometimes|in:immediate,this_quarter,next_quarter,unclear',
            'strategic_fit' => 'sometimes|in:existing_client,referral,target_industry,cold_lead',
            'disqualification_reason' => 'sometimes|nullable|string',
            'last_contact_date' => 'sometimes|nullable|date',
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
        ];
    }
}
