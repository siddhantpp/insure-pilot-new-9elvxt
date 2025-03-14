<?php

namespace App\Http\Requests;

use App\Policies\DocumentPolicy;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest; // Laravel Framework ^10.0

/**
 * Form request class that handles validation and authorization for document processing actions.
 * This class ensures that only authorized users can mark documents as processed or unprocessed,
 * and validates the process state parameter.
 */
class DocumentProcessRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to process the document
     *
     * @return bool True if the user is authorized, false otherwise
     */
    public function authorize()
    {
        // Get the document ID from the route parameters
        $documentId = $this->route('id');
        
        // Find the document by ID
        $document = Document::find($documentId);
        
        // If document not found, return false
        if (!$document) {
            return false;
        }
        
        // Use the DocumentPolicy to check if the user can process the document
        return (new DocumentPolicy())->process($this->user(), $document);
    }

    /**
     * Gets the validation rules that apply to the request
     *
     * @return array Array of validation rules
     */
    public function rules()
    {
        return [
            'process_state' => 'required|boolean',
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
            'process_state.required' => 'The process state is required.',
            'process_state.boolean' => 'The process state must be true or false.',
        ];
    }

    /**
     * Prepares the data for validation by converting string boolean values to actual booleans
     *
     * @return void No return value
     */
    protected function prepareForValidation()
    {
        // If process_state is a string ('true' or 'false'), convert it to a boolean value
        if ($this->has('process_state') && is_string($this->process_state)) {
            $this->merge([
                'process_state' => $this->process_state === 'true',
            ]);
        }
    }
}