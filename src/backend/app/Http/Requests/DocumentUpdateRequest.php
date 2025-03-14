<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Document;
use App\Policies\DocumentPolicy;

/**
 * Form request class that handles validation and authorization for document metadata updates.
 * This class ensures that only authorized users can update document metadata and validates
 * the input data according to business rules and field dependencies.
 */
class DocumentUpdateRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to update the document
     *
     * @return bool True if the user is authorized, false otherwise
     */
    public function authorize()
    {
        $documentId = $this->route('document');
        $document = Document::find($documentId);
        
        if (!$document) {
            return false;
        }
        
        return (new DocumentPolicy())->update($this->user(), $document);
    }
    
    /**
     * Gets the validation rules that apply to the request
     *
     * @return array Array of validation rules
     */
    public function rules()
    {
        return [
            'policy_id' => 'nullable|exists:policy,id',
            'loss_id' => 'nullable|exists:loss,id|required_with:claimant_id',
            'claimant_id' => 'nullable|exists:claimant,id',
            'producer_id' => 'nullable|exists:producer,id',
            'description' => 'nullable|string|max:255',
            'assigned_user_id' => 'nullable|exists:user,id',
            'assigned_group_id' => 'nullable|exists:user_group,id',
            'signature_required' => 'nullable|boolean',
        ];
    }
    
    /**
     * Gets the custom validation messages for the request
     *
     * @return array Array of custom validation messages
     */
    public function messages()
    {
        return [
            'policy_id.exists' => 'The selected policy does not exist.',
            'loss_id.exists' => 'The selected loss does not exist.',
            'loss_id.required_with' => 'A loss must be selected when a claimant is specified.',
            'claimant_id.exists' => 'The selected claimant does not exist.',
            'producer_id.exists' => 'The selected producer does not exist.',
            'description.max' => 'The document description may not be greater than 255 characters.',
            'assigned_user_id.exists' => 'The selected user does not exist.',
            'assigned_group_id.exists' => 'The selected user group does not exist.',
        ];
    }
    
    /**
     * Prepares the data for validation by handling null values and type conversions
     *
     * @return void No return value
     */
    public function prepareForValidation()
    {
        // Convert empty string values to null for nullable fields
        $this->merge(collect($this->all())->map(function ($value, $key) {
            // Handle nullable fields
            if (in_array($key, [
                'policy_id', 'loss_id', 'claimant_id', 'producer_id', 
                'assigned_user_id', 'assigned_group_id'
            ]) && $value === '') {
                return null;
            }
            
            // Ensure ID fields are properly cast to integers when present
            if (in_array($key, [
                'policy_id', 'loss_id', 'claimant_id', 'producer_id', 
                'assigned_user_id', 'assigned_group_id'
            ]) && $value !== null) {
                return (int) $value;
            }
            
            // Handle special cases for boolean values
            if ($key === 'signature_required' && is_string($value)) {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
            
            return $value;
        })->all());
    }
    
    /**
     * Adds additional validation rules after initial validation passes
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void No return value
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that loss_id belongs to the selected policy_id when both are present
            if ($this->filled('loss_id') && $this->filled('policy_id')) {
                $lossExists = \DB::table('map_policy_loss')
                    ->where('policy_id', $this->policy_id)
                    ->where('loss_id', $this->loss_id)
                    ->exists();
                    
                if (!$lossExists) {
                    $validator->errors()->add(
                        'loss_id', 'The selected loss must be associated with the selected policy.'
                    );
                }
            }
            
            // Validate that claimant_id belongs to the selected loss_id when both are present
            if ($this->filled('claimant_id') && $this->filled('loss_id')) {
                $claimantExists = \DB::table('map_loss_claimant')
                    ->where('loss_id', $this->loss_id)
                    ->where('claimant_id', $this->claimant_id)
                    ->exists();
                    
                if (!$claimantExists) {
                    $validator->errors()->add(
                        'claimant_id', 'The selected claimant must be associated with the selected loss.'
                    );
                }
            }
            
            // Validate that only one of assigned_user_id or assigned_group_id is provided if any
            if ($this->filled('assigned_user_id') && $this->filled('assigned_group_id')) {
                $validator->errors()->add(
                    'assigned_to', 'A document can only be assigned to either a user or a group, not both.'
                );
            }
        });
    }
}