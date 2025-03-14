<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Document;
use App\Models\Policy;
use App\Models\Loss;
use App\Models\Claimant;
use App\Models\Producer;

/**
 * API resource class that transforms document metadata into standardized JSON responses for the API.
 * This resource handles the formatting of document metadata fields including policy, loss, claimant, 
 * producer, and assignment information.
 */
class MetadataResource extends JsonResource
{
    /**
     * Transform the resource into an array for API response
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array The transformed metadata array
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
            'date_received' => $document->date_received ? $document->date_received->format('Y-m-d') : null,
            'signature_required' => $document->signature_required,
            'status_id' => $document->status_id,
            'is_processed' => $document->is_processed,
            'is_trashed' => $document->is_trashed,
            'file_url' => $document->file_url,
            
            // Metadata fields
            'policy_id' => $document->policy_id,
            'policy_number' => $document->policy_number,
            'policy_display_name' => $this->formatPolicyInfo($document)['policy_display_name'],
            
            'loss_id' => $document->loss_id,
            'loss_sequence' => $document->loss_sequence,
            'loss_display_name' => $this->formatLossInfo($document)['loss_display_name'],
            
            'claimant_id' => $document->claimant_id,
            'claimant_name' => $document->claimant_name,
            'claimant_display_name' => $this->formatClaimantInfo($document)['claimant_display_name'],
            
            'producer_id' => $document->producer_id,
            'producer_number' => $document->producer_number,
            'producer_display_name' => $this->formatProducerInfo($document)['producer_display_name'],
            
            'assigned_to' => $document->assigned_to,
            'assigned_to_id' => $this->formatAssignmentInfo($document)['assigned_to_id'],
            'assigned_to_type' => $this->formatAssignmentInfo($document)['assigned_to_type'],
            
            // Audit information
            'created_at' => $document->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $document->updated_at->format('Y-m-d H:i:s'),
            'created_by' => $document->created_by,
            'updated_by' => $document->updated_by,
            'created_by_name' => $document->createdBy ? $document->createdBy->username : null,
            'updated_by_name' => $document->updatedBy ? $document->updatedBy->username : null,
        ];
    }
    
    /**
     * Format policy information for the document
     *
     * @param  \App\Models\Document  $document
     * @return array Formatted policy information
     */
    protected function formatPolicyInfo(Document $document)
    {
        if (!$document->relationLoaded('policy')) {
            $document->load('policy');
        }
        
        if (!$document->policy) {
            return [
                'policy_id' => null,
                'policy_number' => null,
                'policy_display_name' => null,
            ];
        }
        
        return [
            'policy_id' => $document->policy->id,
            'policy_number' => $document->policy_number,
            'policy_display_name' => $document->policy->display_name,
        ];
    }
    
    /**
     * Format loss information for the document
     *
     * @param  \App\Models\Document  $document
     * @return array Formatted loss information
     */
    protected function formatLossInfo(Document $document)
    {
        if (!$document->relationLoaded('loss')) {
            $document->load('loss');
        }
        
        if (!$document->loss) {
            return [
                'loss_id' => null,
                'loss_sequence' => null,
                'loss_display_name' => null,
            ];
        }
        
        return [
            'loss_id' => $document->loss->id,
            'loss_sequence' => $document->loss_sequence,
            'loss_display_name' => $document->loss->display_name,
        ];
    }
    
    /**
     * Format claimant information for the document
     *
     * @param  \App\Models\Document  $document
     * @return array Formatted claimant information
     */
    protected function formatClaimantInfo(Document $document)
    {
        if (!$document->relationLoaded('claimant')) {
            $document->load('claimant');
        }
        
        if (!$document->claimant) {
            return [
                'claimant_id' => null,
                'claimant_name' => null,
                'claimant_display_name' => null,
            ];
        }
        
        return [
            'claimant_id' => $document->claimant->id,
            'claimant_name' => $document->claimant_name,
            'claimant_display_name' => $document->claimant->display_name,
        ];
    }
    
    /**
     * Format producer information for the document
     *
     * @param  \App\Models\Document  $document
     * @return array Formatted producer information
     */
    protected function formatProducerInfo(Document $document)
    {
        if (!$document->relationLoaded('producer')) {
            $document->load('producer');
        }
        
        if (!$document->producer) {
            return [
                'producer_id' => null,
                'producer_number' => null,
                'producer_display_name' => null,
            ];
        }
        
        return [
            'producer_id' => $document->producer->id,
            'producer_number' => $document->producer_number,
            'producer_display_name' => $document->producer->display_name,
        ];
    }
    
    /**
     * Format assignment information for the document
     *
     * @param  \App\Models\Document  $document
     * @return array Formatted assignment information
     */
    protected function formatAssignmentInfo(Document $document)
    {
        if (!$document->relationLoaded('users')) {
            $document->load('users');
        }
        
        if (!$document->relationLoaded('userGroups')) {
            $document->load('userGroups');
        }
        
        // Determine if the document is assigned to users or groups
        $assignedToUsers = $document->users->isNotEmpty();
        $assignedToGroups = $document->userGroups->isNotEmpty();
        
        if (!$assignedToUsers && !$assignedToGroups) {
            return [
                'assigned_to' => null,
                'assigned_to_id' => null,
                'assigned_to_type' => null,
            ];
        }
        
        // Prioritize user assignments over group assignments
        if ($assignedToUsers) {
            $user = $document->users->first();
            return [
                'assigned_to' => $document->assigned_to,
                'assigned_to_id' => $user->id,
                'assigned_to_type' => 'user',
            ];
        } else {
            $group = $document->userGroups->first();
            return [
                'assigned_to' => $document->assigned_to,
                'assigned_to_id' => $group->id,
                'assigned_to_type' => 'group',
            ];
        }
    }
}