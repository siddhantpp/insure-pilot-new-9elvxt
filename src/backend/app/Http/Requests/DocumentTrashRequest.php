<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Http\FormRequest; // ^10.0

/**
 * Form request class that handles validation and authorization for document trash operations.
 * This class ensures that only authorized users can move documents to the trash,
 * implementing the necessary permission checks and validation rules.
 */
class DocumentTrashRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to trash the document.
     *
     * @return bool True if the user is authorized, false otherwise
     */
    public function authorize()
    {
        // Get the document ID from the route parameters
        $documentId = $this->route('document');
        
        // Find the document by ID
        $document = Document::find($documentId);
        
        // If document not found, return false
        if (!$document) {
            return false;
        }
        
        // Use the DocumentPolicy to check if the user can delete (trash) the document
        return (new DocumentPolicy())->delete($this->user(), $document);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array Array of validation rules
     */
    public function rules()
    {
        // Return an empty array as the trash operation doesn't require additional parameters
        // The operation is based solely on the document ID in the route
        return [];
    }

    /**
     * Get the custom validation messages for the request.
     *
     * @return array Array of custom validation messages
     */
    public function messages()
    {
        // Return an empty array as there are no validation rules that need custom messages
        return [];
    }
}