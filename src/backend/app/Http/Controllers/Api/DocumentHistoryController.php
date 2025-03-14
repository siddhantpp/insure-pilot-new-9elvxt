<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ActionType;
use App\Http\Resources\DocumentHistoryResource;
use App\Services\AuditLogger;
use App\Models\MapDocumentAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Controller responsible for handling document history-related HTTP requests in the Documents View feature.
 * This controller provides endpoints for retrieving document action history, last edited information,
 * and filtering history by action type.
 */
class DocumentHistoryController extends Controller
{
    /**
     * The audit logger service.
     *
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * Constructor for the DocumentHistoryController
     *
     * @param AuditLogger $auditLogger The audit logger service
     */
    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Retrieves the history of actions performed on a document
     *
     * @param Request $request The HTTP request
     * @param int $id The document ID
     * @return JsonResponse Document history as JSON response
     */
    public function index(Request $request, int $id): JsonResponse
    {
        try {
            // Validate that the document exists
            $document = $this->validateDocument($id);
            if (!$document) {
                return response()->json(['error' => 'Document not found'], 404);
            }

            // Extract pagination parameters from the request
            $perPage = $request->input('perPage', 10);
            $direction = $request->input('direction', 'desc');

            // Get document history from the audit logger
            $history = $this->auditLogger->getDocumentHistory($id, $perPage, $direction);

            // Transform the data using the DocumentHistoryResource
            return response()->json([
                'data' => DocumentHistoryResource::collection($history),
                'meta' => [
                    'current_page' => $history->currentPage(),
                    'from' => $history->firstItem(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'to' => $history->lastItem(),
                    'total' => $history->total(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error retrieving document history', [
                'error' => $e->getMessage(),
                'document_id' => $id,
            ]);
            
            return response()->json(['error' => 'Failed to retrieve document history'], 500);
        }
    }

    /**
     * Retrieves information about the last edit made to a document
     *
     * @param Request $request The HTTP request
     * @param int $id The document ID
     * @return JsonResponse Last edited information as JSON response
     */
    public function lastEdited(Request $request, int $id): JsonResponse
    {
        try {
            // Validate that the document exists
            $document = $this->validateDocument($id);
            if (!$document) {
                return response()->json(['error' => 'Document not found'], 404);
            }

            // Get the last action performed on the document
            $lastAction = $this->auditLogger->getLastDocumentAction($id);
            
            // If no actions found, return empty response
            if (!$lastAction) {
                return response()->json(['data' => null]);
            }

            // Return the last action data
            return response()->json(['data' => $lastAction]);
        } catch (Exception $e) {
            Log::error('Error retrieving last edited information', [
                'error' => $e->getMessage(),
                'document_id' => $id,
            ]);
            
            return response()->json(['error' => 'Failed to retrieve last edited information'], 500);
        }
    }

    /**
     * Filters document history by action type
     *
     * @param Request $request The HTTP request
     * @param int $id The document ID
     * @return JsonResponse Filtered document history as JSON response
     */
    public function filterByActionType(Request $request, int $id): JsonResponse
    {
        try {
            // Validate that the document exists
            $document = $this->validateDocument($id);
            if (!$document) {
                return response()->json(['error' => 'Document not found'], 404);
            }

            // Extract action type ID and pagination parameters from the request
            $actionTypeId = $request->input('action_type_id');
            if (!$actionTypeId) {
                return response()->json(['error' => 'Action type ID is required'], 400);
            }

            $perPage = $request->input('perPage', 10);
            $direction = $request->input('direction', 'desc');

            // Query MapDocumentAction with specified filters
            $filteredHistory = MapDocumentAction::forDocument($id)
                ->withActionType($actionTypeId)
                ->chronological($direction)
                ->paginate($perPage);

            // Transform the data using the DocumentHistoryResource
            return response()->json([
                'data' => DocumentHistoryResource::collection($filteredHistory),
                'meta' => [
                    'current_page' => $filteredHistory->currentPage(),
                    'from' => $filteredHistory->firstItem(),
                    'last_page' => $filteredHistory->lastPage(),
                    'per_page' => $filteredHistory->perPage(),
                    'to' => $filteredHistory->lastItem(),
                    'total' => $filteredHistory->total(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error filtering document history by action type', [
                'error' => $e->getMessage(),
                'document_id' => $id,
                'action_type_id' => $request->input('action_type_id'),
            ]);
            
            return response()->json(['error' => 'Failed to filter document history'], 500);
        }
    }

    /**
     * Retrieves a list of action types used in a document's history
     *
     * @param Request $request The HTTP request
     * @param int $id The document ID
     * @return JsonResponse List of action types as JSON response
     */
    public function getActionTypes(Request $request, int $id): JsonResponse
    {
        try {
            // Validate that the document exists
            $document = $this->validateDocument($id);
            if (!$document) {
                return response()->json(['error' => 'Document not found'], 404);
            }

            // Get all action types
            $actionTypes = ActionType::all();
            
            // Format the action types data
            $formattedActionTypes = $actionTypes->map(function ($actionType) use ($id) {
                // Count how many actions of this type exist for the document
                $count = MapDocumentAction::forDocument($id)
                    ->join('action', 'map_document_action.action_id', '=', 'action.id')
                    ->where('action.action_type_id', $actionType->id)
                    ->count();
                
                return [
                    'id' => $actionType->id,
                    'name' => $actionType->name,
                    'description' => $actionType->description,
                    'count' => $count
                ];
            });

            return response()->json(['data' => $formattedActionTypes]);
        } catch (Exception $e) {
            Log::error('Error retrieving action types', [
                'error' => $e->getMessage(),
                'document_id' => $id,
            ]);
            
            return response()->json(['error' => 'Failed to retrieve action types'], 500);
        }
    }

    /**
     * Validates that a document exists and is accessible
     *
     * @param int $id The document ID
     * @return ?\\App\\Models\\Document The document if found, null otherwise
     */
    protected function validateDocument(int $id): ?Document
    {
        return Document::find($id);
    }
}