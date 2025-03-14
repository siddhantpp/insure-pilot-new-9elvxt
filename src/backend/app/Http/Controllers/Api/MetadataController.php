<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\MetadataService;
use App\Http\Requests\DocumentUpdateRequest;
use App\Http\Resources\MetadataResource;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controller responsible for handling document metadata operations including retrieval, updates,
 * and dropdown options for the Documents View feature.
 */
class MetadataController extends Controller
{
    /**
     * The metadata service instance.
     *
     * @var MetadataService
     */
    protected $metadataService;

    /**
     * The audit logger instance.
     *
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * Constructor for the MetadataController
     *
     * @param MetadataService $metadataService
     * @param AuditLogger $auditLogger
     */
    public function __construct(MetadataService $metadataService, AuditLogger $auditLogger)
    {
        $this->metadataService = $metadataService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Retrieves metadata for a specific document
     *
     * @param Request $request
     * @param int $documentId
     * @return \Illuminate\Http\JsonResponse JSON response with document metadata
     */
    public function show(Request $request, int $documentId): JsonResponse
    {
        // Get the current authenticated user
        $user = Auth::user();

        // Find the document
        $document = Document::find($documentId);

        // If document not found, return 404 response
        if (!$document) {
            return response()->json([
                'message' => 'Document not found'
            ], 404);
        }

        // Log document view action using AuditLogger
        $this->auditLogger->logDocumentView($documentId, $user->id);

        // Load necessary relationships for the metadata display
        $document->load([
            'policy',
            'loss',
            'claimant',
            'producer',
            'users',
            'userGroups',
            'files',
            'createdBy',
            'updatedBy'
        ]);

        // Transform document using MetadataResource
        $resource = new MetadataResource($document);

        // Return JSON response with the transformed metadata
        return response()->json($resource);
    }

    /**
     * Updates metadata for a specific document
     *
     * @param DocumentUpdateRequest $request
     * @param int $documentId
     * @return \Illuminate\Http\JsonResponse JSON response with updated document metadata
     */
    public function update(DocumentUpdateRequest $request, int $documentId): JsonResponse
    {
        // Get the current authenticated user
        $user = Auth::user();

        // Get validated data from the request
        $validatedData = $request->validated();

        // Update document metadata using the MetadataService
        $updated = $this->metadataService->updateDocumentMetadata(
            $documentId,
            $validatedData,
            $user->id
        );

        // If update fails, return 422 response with error message
        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update document metadata'
            ], 422);
        }

        // Find the updated document
        $document = Document::find($documentId);

        // Load necessary relationships for the metadata display
        $document->load([
            'policy',
            'loss',
            'claimant',
            'producer',
            'users',
            'userGroups',
            'files',
            'createdBy',
            'updatedBy'
        ]);

        // Transform document using MetadataResource
        $resource = new MetadataResource($document);

        // Return JSON response with the transformed metadata
        return response()->json($resource);
    }

    /**
     * Retrieves policy options for dropdown selection
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse JSON response with policy options
     */
    public function getPolicyOptions(Request $request): JsonResponse
    {
        // Get search parameter from request
        $search = $request->input('search');
        
        // Get producer_id parameter from request
        $producerId = $request->input('producer_id');
        
        // Get limit parameter from request (default: 10)
        $limit = $request->input('limit', 10);

        // Retrieve policy options using the MetadataService
        $options = $this->metadataService->getPolicyOptions($search, $producerId, $limit);

        // Return JSON response with the policy options
        return response()->json($options);
    }

    /**
     * Retrieves loss options for a specific policy
     *
     * @param Request $request
     * @param int $policyId
     * @return \Illuminate\Http\JsonResponse JSON response with loss options
     */
    public function getLossOptions(Request $request, int $policyId): JsonResponse
    {
        // Get search parameter from request
        $search = $request->input('search');
        
        // Get limit parameter from request (default: 10)
        $limit = $request->input('limit', 10);

        // Retrieve loss options using the MetadataService
        $options = $this->metadataService->getLossOptions($policyId, $search, $limit);

        // Return JSON response with the loss options
        return response()->json($options);
    }

    /**
     * Retrieves claimant options for a specific loss
     *
     * @param Request $request
     * @param int $lossId
     * @return \Illuminate\Http\JsonResponse JSON response with claimant options
     */
    public function getClaimantOptions(Request $request, int $lossId): JsonResponse
    {
        // Get search parameter from request
        $search = $request->input('search');
        
        // Get limit parameter from request (default: 10)
        $limit = $request->input('limit', 10);

        // Retrieve claimant options using the MetadataService
        $options = $this->metadataService->getClaimantOptions($lossId, $search, $limit);

        // Return JSON response with the claimant options
        return response()->json($options);
    }

    /**
     * Retrieves producer options for dropdown selection
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse JSON response with producer options
     */
    public function getProducerOptions(Request $request): JsonResponse
    {
        // Get search parameter from request
        $search = $request->input('search');
        
        // Get limit parameter from request (default: 10)
        $limit = $request->input('limit', 10);

        // Retrieve producer options using the MetadataService
        $options = $this->metadataService->getProducerOptions($search, $limit);

        // Return JSON response with the producer options
        return response()->json($options);
    }

    /**
     * Retrieves user options for assignment dropdown
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse JSON response with user options
     */
    public function getUserOptions(Request $request): JsonResponse
    {
        // Get search parameter from request
        $search = $request->input('search');
        
        // Get limit parameter from request (default: 10)
        $limit = $request->input('limit', 10);

        // Retrieve user options using the MetadataService
        $options = $this->metadataService->getUserOptions($search, $limit);

        // Return JSON response with the user options
        return response()->json($options);
    }

    /**
     * Retrieves user group options for assignment dropdown
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse JSON response with user group options
     */
    public function getUserGroupOptions(Request $request): JsonResponse
    {
        // Get search parameter from request
        $search = $request->input('search');
        
        // Get limit parameter from request (default: 10)
        $limit = $request->input('limit', 10);

        // Retrieve user group options using the MetadataService
        $options = $this->metadataService->getUserGroupOptions($search, $limit);

        // Return JSON response with the user group options
        return response()->json($options);
    }
}