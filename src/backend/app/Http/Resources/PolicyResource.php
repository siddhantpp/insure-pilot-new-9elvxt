<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource; // illuminate/http ^10.0
use App\Models\Policy;
use App\Models\PolicyPrefix;

/**
 * API resource class that transforms Policy model instances into standardized JSON responses for the Documents View feature.
 * This resource handles the formatting of policy data for dropdown selection and display.
 */
class PolicyResource extends JsonResource
{
    /**
     * Flag to determine if related counts should be included.
     *
     * @var bool
     */
    protected $includeRelatedCounts = false;

    /**
     * Flag to determine if additional details should be included.
     *
     * @var bool
     */
    protected $includeDetails = false;

    /**
     * Transform the resource into an array for API response
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array The transformed policy data array
     */
    public function toArray($request)
    {
        /** @var Policy $policy */
        $policy = $this->resource;

        $result = [
            'id' => $policy->id,
            'formatted_number' => $policy->getFormattedNumberAttribute(),
            'display_name' => $policy->getDisplayNameAttribute(),
            'number' => $policy->number,
        ];

        // Include policy prefix information if available
        if ($policy->relationLoaded('policyPrefix') || $policy->policyPrefix) {
            $result['policy_prefix'] = [
                'id' => $policy->policyPrefix->id,
                'name' => $policy->policyPrefix->name,
            ];
        }

        // Include basic date information
        $result['effective_date'] = $policy->formatted_effective_date;
        $result['expiration_date'] = $policy->formatted_expiration_date;
        $result['status_id'] = $policy->status_id;

        // Include related document count if requested
        if ($this->includeRelatedCounts) {
            $result['documents_count'] = $policy->related_documents_count;
        }

        // Include additional details if requested
        if ($this->includeDetails) {
            $result = array_merge($result, [
                'inception_date' => $policy->inception_date ? $policy->inception_date->format(config('app.date_format', 'm/d/Y')) : null,
                'renewal_date' => $policy->renewal_date ? $policy->renewal_date->format(config('app.date_format', 'm/d/Y')) : null,
                'description' => $policy->description,
                'created_at' => $policy->created_at ? $policy->created_at->toISOString() : null,
                'updated_at' => $policy->updated_at ? $policy->updated_at->toISOString() : null,
            ]);
        }

        return $result;
    }

    /**
     * Include related entity counts in the resource
     *
     * @return PolicyResource The resource instance with related counts
     */
    public function withRelatedCounts()
    {
        $this->includeRelatedCounts = true;
        return $this;
    }

    /**
     * Include additional policy details in the resource
     *
     * @return PolicyResource The resource instance with additional details
     */
    public function withDetails()
    {
        $this->includeDetails = true;
        return $this;
    }
}