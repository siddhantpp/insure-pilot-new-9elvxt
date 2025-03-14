<?php

namespace App\Http\Resources;

use App\Models\Claimant;
use Illuminate\Http\Resources\Json\JsonResource; // illuminate/http ^10.0
use Carbon\Carbon; // nesbot/carbon ^2.0

/**
 * API resource class that transforms Claimant model instances into JSON responses for the API.
 * This resource is used in the Documents View feature to provide claimant data for dropdown
 * fields and metadata display.
 */
class ClaimantResource extends JsonResource
{
    /**
     * Transform the resource into an array for API response
     *
     * @param \Illuminate\Http\Request $request
     * @return array The transformed resource array
     */
    public function toArray($request)
    {
        /** @var Claimant $claimant */
        $claimant = $this->resource;
        
        return [
            'id' => $claimant->id,
            'name' => $claimant->full_name,
            'display_name' => $claimant->display_name,
            'type' => [
                'id' => $claimant->claimant_type_id,
                'is_individual' => $claimant->isIndividual(),
                'is_business' => $claimant->isBusiness(),
                'label' => $this->getClaimantTypeLabel($claimant),
            ],
            // Include additional fields needed for document metadata
            'loss_id' => $claimant->loss_id,
            'policy_id' => $claimant->policy_id,
            // Include sequence number for dropdown display ordering
            'sequence' => (int) substr($claimant->display_name, 0, strpos($claimant->display_name, ' -')),
            'created_at' => $this->formatTimestamp($claimant->created_at),
            'updated_at' => $this->formatTimestamp($claimant->updated_at),
        ];
    }
    
    /**
     * Format a timestamp for display
     *
     * @param ?\Carbon\Carbon $timestamp
     * @return string|null Formatted timestamp string or null
     */
    private function formatTimestamp(?Carbon $timestamp)
    {
        if (!$timestamp) {
            return null;
        }
        
        return $timestamp->format(config('app.datetime_format', 'Y-m-d H:i:s'));
    }
    
    /**
     * Get a human-readable label for the claimant type
     * 
     * @param Claimant $claimant
     * @return string
     */
    private function getClaimantTypeLabel(Claimant $claimant)
    {
        if ($claimant->isIndividual()) {
            return 'Individual';
        } else if ($claimant->isBusiness()) {
            return 'Business';
        } else {
            return 'Other';
        }
    }
}