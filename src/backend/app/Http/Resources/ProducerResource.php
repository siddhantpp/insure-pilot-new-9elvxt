<?php

namespace App\Http\Resources;

use App\Models\Producer;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource; // ^10.0
use Carbon\Carbon; // ^2.0

class ProducerResource extends JsonResource
{
    /**
     * Transform the resource into an array for API response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array The transformed producer array
     */
    public function toArray($request)
    {
        /** @var Producer $producer */
        $producer = $this->resource;

        return [
            'id' => $producer->id,
            'number' => $producer->number,
            'name' => $producer->name,
            'description' => $producer->description,
            'producer_type_id' => $producer->producer_type_id,
            'producer_code_id' => $producer->producer_code_id,
            'status_id' => $producer->status_id,
            'signature_required' => $producer->signature_required,
            
            // Related counts
            ...$this->withRelatedCounts($producer),
            
            // User information
            'created_by' => $this->formatUserInfo($producer->createdBy),
            'updated_by' => $this->formatUserInfo($producer->updatedBy),
            
            // Timestamps
            'created_at' => $this->formatTimestamp($producer->created_at),
            'updated_at' => $this->formatTimestamp($producer->updated_at),
            
            // Display name for dropdown presentation
            'display_name' => $producer->getDisplayNameAttribute(),
        ];
    }

    /**
     * Format a timestamp for display.
     *
     * @param  ?\Carbon\Carbon  $timestamp
     * @return string|null Formatted timestamp string or null
     */
    protected function formatTimestamp(?Carbon $timestamp)
    {
        if ($timestamp) {
            return $timestamp->format('Y-m-d H:i:s');
        }

        return null;
    }

    /**
     * Format user information for display.
     *
     * @param  ?\App\Models\User  $user
     * @return array|null Formatted user information or null if no user exists
     */
    protected function formatUserInfo(?User $user)
    {
        if ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
            ];
        }

        return null;
    }

    /**
     * Include related entity counts in the resource.
     *
     * @param  \App\Models\Producer  $producer
     * @return array Array with related entity counts
     */
    protected function withRelatedCounts(Producer $producer)
    {
        return [
            'policies_count' => $producer->getRelatedPoliciesCountAttribute(),
            'documents_count' => $producer->getRelatedDocumentsCountAttribute(),
        ];
    }
}