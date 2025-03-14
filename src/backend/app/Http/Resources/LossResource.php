<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource; // illuminate/http ^10.0
use App\Models\Loss;
use Carbon\Carbon; // nesbot/carbon ^2.0

/**
 * API resource class that transforms Loss model instances into JSON responses for the API.
 * This resource is used in the Documents View feature to provide loss data for dropdown fields
 * in the document metadata panel, particularly for the Loss Sequence field that depends on
 * the selected Policy Number.
 */
class LossResource extends JsonResource
{
    /**
     * Default constructor for the LossResource
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
     * @return array The transformed resource array
     */
    public function toArray($request)
    {
        // Extract the Loss model from the resource
        $loss = $this->resource;

        // Format the loss data for API response
        return [
            'id' => $loss->id,
            'name' => $loss->name,
            'date' => $loss->date ? $loss->date->toDateString() : null,
            'formatted_date' => $loss->formatted_date,
            'description' => $loss->description,
            'display_name' => $loss->display_name,
            'status_id' => $loss->status_id,
            'loss_type_id' => $loss->loss_type_id ?? null,
            'sequence' => null, // Will be populated if necessary in controller
            'policy_info' => $this->withPolicyInfo($loss, $request),
            'claimant_count' => $this->withClaimantCount($loss, $request),
            'created_at' => $this->formatTimestamp($loss->created_at),
            'updated_at' => $this->formatTimestamp($loss->updated_at),
        ];
    }

    /**
     * Format a timestamp for display
     *
     * @param ?Carbon\Carbon $timestamp
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
     * Include policy information in the resource if requested
     *
     * @param \App\Models\Loss $loss
     * @param \Illuminate\Http\Request $request
     * @return array|null Policy information array or null
     */
    protected function withPolicyInfo(Loss $loss, $request)
    {
        if (!$request->has('with_policy')) {
            return null;
        }

        // Ensure the policy relationship is loaded
        if (!$loss->relationLoaded('policies')) {
            $loss->load('policies');
        }

        if ($loss->policies->isEmpty()) {
            return null;
        }

        $policy = $loss->policies->first();
        return [
            'id' => $policy->id,
            'number' => $policy->number,
            'formatted_number' => $policy->formatted_number,
            'display_name' => $policy->display_name,
        ];
    }

    /**
     * Include claimant count in the resource if requested
     *
     * @param \App\Models\Loss $loss
     * @param \Illuminate\Http\Request $request
     * @return int|null Claimant count or null
     */
    protected function withClaimantCount(Loss $loss, $request)
    {
        if (!$request->has('with_claimant_count')) {
            return null;
        }

        // Ensure the claimants relationship is loaded
        if (!$loss->relationLoaded('claimants')) {
            $loss->load('claimants');
        }

        return $loss->claimants->count();
    }
}