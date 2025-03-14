<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProducerResource;
use App\Services\ProducerManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API controller responsible for handling producer-related endpoints in the Documents View feature.
 * This controller provides endpoints for retrieving producer data, searching producers, and
 * generating producer view URLs for contextual navigation.
 */
class ProducerController extends Controller
{
    /**
     * The ProducerManager instance.
     *
     * @var ProducerManager
     */
    protected $producerManager;

    /**
     * Constructor for the ProducerController
     *
     * @param ProducerManager $producerManager
     */
    public function __construct(ProducerManager $producerManager)
    {
        $this->producerManager = $producerManager;
    }

    /**
     * Retrieves a paginated list of producers with optional filtering
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Collection of producer resources
     */
    public function index(Request $request)
    {
        // Extract parameters from the request
        $perPage = $request->input('perPage', 15);
        $sortBy = $request->input('sortBy');
        $sortDirection = $request->input('sortDirection');
        $filters = $request->input('filters', []);

        // Get paginated producers using the manager
        $producers = $this->producerManager->getProducers($perPage, $sortBy, $sortDirection, $filters);

        // Return transformed collection
        return ProducerResource::collection($producers);
    }

    /**
     * Retrieves a specific producer by ID
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse Producer data or error response
     */
    public function show(Request $request, int $id)
    {
        // Extract optional relations parameter
        $relations = $request->input('relations', []);

        // Get producer by ID with optional relations
        $producer = $this->producerManager->getProducer($id, $relations);

        // Return 404 if producer not found
        if (!$producer) {
            return response()->json([
                'success' => false,
                'message' => 'Producer not found',
            ], 404);
        }

        // Return producer resource
        return response()->json([
            'success' => true,
            'data' => new ProducerResource($producer),
        ]);
    }

    /**
     * Searches for producers based on query parameters
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Collection of matching producer resources
     */
    public function search(Request $request)
    {
        // Extract search parameters
        $search = $request->input('search', '');
        $withPolicies = $request->input('withPolicies', false);
        $limit = $request->input('limit', 100);

        // Get producer options using the manager
        $options = $this->producerManager->getProducerOptions($search, $withPolicies, $limit);

        // Return options array
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }

    /**
     * Retrieves the URL for the producer view page
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse URL or error response
     */
    public function getUrl(int $id)
    {
        // Get producer URL from the manager
        $url = $this->producerManager->getProducerUrl($id);

        // Return 404 if producer not found or URL generation fails
        if (!$url) {
            return response()->json([
                'success' => false,
                'message' => 'Producer not found or URL generation failed',
            ], 404);
        }

        // Return URL in JSON response
        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }
}