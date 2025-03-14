<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource; // ^10.0
use Carbon\Carbon; // ^2.0
use App\Models\Document;
use App\Models\File;
use App\Models\User;
use App\Http\Resources\MetadataResource;

/**
 * API resource class that transforms document data into JSON responses for the API.
 * This resource is a core component of the Documents View feature, providing standardized
 * document representations including metadata, file information, and relationships to other entities.
 */
class DocumentResource extends JsonResource
{
    /**
     * Default constructor for the DocumentResource
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array for API response
     *
     * @param \Illuminate\Http\Request $request
     * @return array The transformed document array
     */
    public function toArray($request)
    {
        /** @var Document $document */
        $document = $this->resource;
        
        return [
            // Basic document information
            'id' => $document->id,
            'name' => $document->name,
            'description' => $document->description,
            'status_id' => $document->status_id,
            'is_processed' => $document->getIsProcessedAttribute(),
            'is_trashed' => $document->getIsTrashedAttribute(),
            
            // File information
            'file' => $this->formatFileInfo($document),
            
            // Metadata
            'metadata' => $this->withMetadata($document),
            
            // User information
            'created_by' => $this->formatUserInfo($document->createdBy),
            'updated_by' => $this->formatUserInfo($document->updatedBy),
            
            // Timestamps
            'created_at' => $this->formatTimestamp($document->created_at),
            'updated_at' => $this->formatTimestamp($document->updated_at),
        ];
    }
    
    /**
     * Format file information for the document
     *
     * @param \App\Models\Document $document
     * @return array|null Formatted file information or null if no file exists
     */
    protected function formatFileInfo(Document $document)
    {
        $file = $document->getMainFileAttribute();
        
        if (!$file) {
            return null;
        }
        
        return [
            'id' => $file->id,
            'name' => $file->name,
            'url' => $file->getUrlAttribute(),
            'size' => $file->size,
            'formatted_size' => $file->getFormattedSizeAttribute(),
            'mime_type' => $file->mime_type,
            'extension' => $file->getFileExtensionAttribute(),
        ];
    }
    
    /**
     * Format a timestamp for display
     *
     * @param ?\Carbon\Carbon $timestamp
     * @return string|null Formatted timestamp string or null
     */
    protected function formatTimestamp(?Carbon $timestamp)
    {
        if (!$timestamp) {
            return null;
        }
        
        return $timestamp->format(config('app.datetime_format', 'Y-m-d H:i:s'));
    }
    
    /**
     * Format user information for display
     *
     * @param ?\App\Models\User $user
     * @return array|null Formatted user information or null if no user exists
     */
    protected function formatUserInfo(?User $user)
    {
        if (!$user) {
            return null;
        }
        
        return [
            'id' => $user->id,
            'username' => $user->username,
        ];
    }
    
    /**
     * Include document metadata in the resource
     *
     * @param \App\Models\Document $document
     * @return array Document metadata array
     */
    protected function withMetadata(Document $document)
    {
        return (new MetadataResource($document))->toArray(request());
    }
}