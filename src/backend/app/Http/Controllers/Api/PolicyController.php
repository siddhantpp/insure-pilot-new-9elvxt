<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PolicyResource;
use App\Http\Resources\LossResource;
use App\Models\Policy;
use App\Models\Loss;
use App\Services\PolicyManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * API controller responsible for handling policy-related endpoints in the Documents View feature.
 * This controller provides endpoints for retrieving policy data, policy options for dropdown fields,
 * and policy-related losses for the document metadata panel.
 */
class PolicyController extends Controller
{
    /**
     * The PolicyManager service instance.
     *
     * @var PolicyManager
     */
    protected $policyManager;

    /**
     * Constructor for the PolicyController
     *
     * @param PolicyManager $policyManager The policy manager service dependency
     */
    public function __construct(PolicyManager $policyManager)
    {
        $this->policyManager = $policyManager;
    }

    /**
     * Retrieves a paginated list of policies with optional filtering
     *
     * @param Request $request The HTTP request
     * @return JsonResponse JSON response with paginated policies
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract filters from request
            $filters = [
                'search' => $request->input('search'),
                'producer_id' => $request->input('producer_id'),
                'effective_date_from' => $request->input('effective_date_from'),
                'effective_date_to' => $request->input('effective_date_to'),
                'expiration_date_from' => $request->input('expiration_date_from'),
                'expiration_date_to' => $request->input('expiration_date_to'),
                'status_id' => $request->input('status_id'),
            ];

            // Extract pagination parameters
            $perPage = $request->input('per_page', 15);
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');

            // Get policies using the policy manager service
            $policies = $this->policyManager->getPolicies(
                $filters,
                $perPage,
                $sortBy,
                $sortDirection
            );

            // Transform and return the paginated results
            return response()->json(PolicyResource::collection($policies));
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Retrieves a specific policy by ID
     *
     * @param Request $request The HTTP request
     * @param int $id The policy ID
     * @return JsonResponse JSON response with policy details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            // Extract relations to load
            $with = $request->input('with', []);
            $relations = is_array($with) ? $with : explode(',', $with);

            // Get the policy with related data
            $policy = $this->policyManager->getPolicy($id, $relations);

            // Return 404 if policy not found
            if (!$policy) {
                return response()->json([
                    'message' => 'Policy not found'
                ], 404);
            }

            // Transform and return the policy
            return response()->json(new PolicyResource($policy));
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Retrieves policy options for dropdown selection with optional filtering
     *
     * @param Request $request The HTTP request
     * @return JsonResponse JSON response with policy options
     */
    public function options(Request $request): JsonResponse
    {
        try {
            // Extract request parameters
            $search = $request->input('search', '');
            $producerId = $request->input('producer_id');
            $limit = $request->input('limit', 50);

            // Get policy options from the service
            $options = $this->policyManager->getPolicyOptions($search, $producerId, $limit);

            // Return the formatted options
            return response()->json([
                'options' => $options
            ]);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Retrieves policies associated with a specific producer
     *
     * @param Request $request The HTTP request
     * @param int $producerId The producer ID
     * @return JsonResponse JSON response with producer's policies
     */
    public function producerPolicies(Request $request, int $producerId): JsonResponse
    {
        try {
            // Extract request parameters
            $search = $request->input('search', '');
            $limit = $request->input('limit', 50);

            // Get policies for this producer
            $policies = $this->policyManager->getPoliciesForProducer($producerId, $search, $limit);

            // Transform and return the policies
            return response()->json(PolicyResource::collection($policies));
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Retrieves losses associated with a specific policy
     *
     * @param Request $request The HTTP request
     * @param int $policyId The policy ID
     * @return JsonResponse JSON response with policy's losses
     */
    public function losses(Request $request, int $policyId): JsonResponse
    {
        try {
            // Get losses for this policy
            $losses = $this->policyManager->getLossesForPolicy($policyId);

            // If policy not found, return 404
            if ($losses === null) {
                return response()->json([
                    'message' => 'Policy not found'
                ], 404);
            }

            // Transform and return the losses
            return response()->json(LossResource::collection($losses));
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handles exceptions and returns appropriate error responses
     *
     * @param Exception $e The exception to handle
     * @return JsonResponse JSON response with error details
     */
    protected function handleException(Exception $e): JsonResponse
    {
        // Log the exception
        \Log::error('PolicyController error: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Determine appropriate HTTP status code
        $statusCode = 500; // Default to server error

        // Create error message
        $message = 'An error occurred while processing your request.';
        
        // In development, include more details
        if (config('app.debug')) {
            $message = $e->getMessage();
        }

        // Return error response
        return response()->json([
            'message' => $message
        ], $statusCode);
    }
}