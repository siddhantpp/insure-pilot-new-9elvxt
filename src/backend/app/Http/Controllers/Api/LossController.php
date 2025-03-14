<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loss;
use App\Models\Policy;
use App\Http\Resources\LossResource;
use App\Services\LossManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * API controller responsible for handling loss-related requests in the Documents View feature.
 * This controller provides endpoints for retrieving loss data for dropdown fields,
 * particularly for the dynamic dropdown controls where Loss Sequence options are
 * filtered based on the selected Policy Number.
 */
class LossController extends Controller
{
    /**
     * The loss manager service instance.
     *
     * @var \App\Services\LossManager
     */
    protected $lossManager;

    /**
     * Constructor for the LossController
     *
     * @param \App\Services\LossManager $lossManager
     * @return void
     */
    public function __construct(LossManager $lossManager)
    {
        parent::__construct();
        $this->lossManager = $lossManager;
    }

    /**
     * Retrieve a paginated list of all active losses
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\ResourceCollection Collection of LossResource objects
     */
    public function index(Request $request): ResourceCollection
    {
        // Extract pagination parameters from request
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Extract filter parameters
        $filters = [];
        
        if ($request->has('policy_id')) {
            $filters['policy_id'] = (int) $request->input('policy_id');
        }
        
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }
        
        if ($request->has('status_id')) {
            $filters['status_id'] = (int) $request->input('status_id');
        }
        
        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }
        
        // Get losses using the loss manager
        $losses = $this->lossManager->getLosses(
            $filters,
            $perPage,
            $sortBy,
            $sortDirection
        );
        
        // Transform the losses using LossResource
        return LossResource::collection($losses);
    }

    /**
     * Retrieve a specific loss by ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse JSON response with loss data or error
     */
    public function show(int $id): JsonResponse
    {
        $loss = $this->lossManager->getLoss($id, ['policies', 'claimants']);
        
        if (!$loss) {
            return response()->json([
                'message' => 'Loss not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json(new LossResource($loss));
    }

    /**
     * Retrieve losses associated with a specific policy for dropdown selection
     *
     * @param \Illuminate\Http\Request $request
     * @param int $policyId
     * @return \Illuminate\Http\JsonResponse JSON response with losses for the policy or error
     */
    public function forPolicy(Request $request, int $policyId): JsonResponse
    {
        // Verify that the policy exists and is active
        $policy = Policy::find($policyId);
        
        if (!$policy) {
            return response()->json([
                'message' => 'Policy not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Verify that the policy is active (status_id = 1)
        if ($policy->status_id !== 1) {
            return response()->json([
                'message' => 'Policy is not active'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Get search term if provided
        $search = $request->input('search', '');
        
        // Get limit parameter if provided
        $limit = (int) $request->input('limit', 10);
        
        // Retrieve losses for the policy using the loss manager
        $losses = $this->lossManager->getLossesForPolicy($policyId, $search, $limit);
        
        return response()->json(LossResource::collection($losses));
    }

    /**
     * Search for losses by name or other attributes
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\ResourceCollection Collection of LossResource objects
     */
    public function search(Request $request): ResourceCollection
    {
        // Extract search term
        $search = $request->input('search', '');
        
        // Extract pagination parameters
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Create filter array with search parameter
        $filters = [
            'search' => $search
        ];
        
        // Get losses using the loss manager
        $losses = $this->lossManager->getLosses(
            $filters,
            $perPage,
            $sortBy,
            $sortDirection
        );
        
        // Transform the losses using LossResource
        return LossResource::collection($losses);
    }
}