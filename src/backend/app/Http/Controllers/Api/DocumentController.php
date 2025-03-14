<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // Laravel 10.0+
use App\Models\Document; // Laravel 10.0+
use App\Http\Resources\DocumentResource; // Laravel 10.0+
use App\Services\DocumentManager; //
use App\Http\Requests\DocumentUpdateRequest; //
use App\Http\Requests\DocumentProcessRequest; //
use App\Http\Requests\DocumentTrashRequest; //
use Illuminate\Http\Request; // Laravel 10.0+
use Illuminate\Http\Response; // Laravel 10.0+
use Illuminate\Http\JsonResponse; // Laravel 10.0+
use Exception; // 8.2
use Illuminate\Support\Facades\Log; // Laravel 10.0+

/**
 * Controller responsible for handling document-related HTTP requests in the Documents View feature.
 * This controller provides endpoints for document retrieval, metadata updates, processing actions, and trash operations.
 */
class DocumentController extends Controller
{
    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * Constructor for the DocumentController
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * Retrieves a paginated list of documents with optional filtering
     *
     * @param Request $request
     * @return JsonResponse Paginated list of documents as JSON response
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract query parameters from the request (filters, pagination, sorting)
            $filters = $request->only([
                'status',
                'policy_id',
                'loss_id',
                'claimant_id',
                'producer_id',
                'search',
                'date_from',
                'date_to',
                'assigned_to_user',
                'assigned_to_group',
                'created_by',
                'updated_by',
            ]);
            $perPage = $request->integer('per_page', 15);
            $sortBy = $request->string('sort_by', 'created_at');
            $sortDirection = $request->string('sort_direction', 'desc');

            // Call documentManager->getDocuments with the extracted parameters
            $documents = $this->documentManager->getDocuments(
                $filters,
                $perPage,
                $sortBy,
                $sortDirection
            );

            // Transform the paginated results using DocumentResource collection
            $resource = DocumentResource::collection($documents);

            // Return JSON response with the transformed data
            return response()->json($resource);
        } catch (Exception $e) {
            // Handle exceptions and return appropriate error responses
            Log::error("Error retrieving documents: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to retrieve documents'], 500);
        }
    }

    /**
     * Retrieves a specific document by ID
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse Document data as JSON response
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            // Call documentManager->getDocument with the provided ID
            $document = $this->documentManager->getDocument($id);

            // If document not found, return 404 response
            if (!$document) {
                return response()->json(['error' => 'Document not found'], 404);
            }

            // Log document view action using documentManager->logDocumentView
            $userId = auth()->id();
            if ($userId) {
                $this->documentManager->logDocumentView($id, $userId);
            }

            // Transform the document using DocumentResource
            $resource = new DocumentResource($document);

            // Return JSON response with the transformed data
            return response()->json($resource);
        } catch (Exception $e) {
            // Handle exceptions and return appropriate error responses
            Log::error("Error retrieving document: " . $e->getMessage(), [
                'document_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to retrieve document'], 500);
        }
    }

    /**
     * Updates a document's metadata
     *
     * @param DocumentUpdateRequest $request
     * @param int $id
     * @return JsonResponse Updated document data as JSON response
     */
    public function update(DocumentUpdateRequest $request, int $id): JsonResponse
    {
        try {
            // Extract validated data from the request
            $validatedData = $request->validated();

            // Get the authenticated user ID
            $userId = auth()->id();

            // Call documentManager->updateDocument with the document ID, validated data, and user ID
            $document = $this->documentManager->updateDocument($id, $validatedData, $userId);

            // If update fails, return 422 response with error message
            if (!$document) {
                return response()->json(['error' => 'Failed to update document metadata'], 422);
            }

            // Transform the updated document using DocumentResource
            $resource = new DocumentResource($document);

            // Return JSON response with the transformed data
            return response()->json($resource);
        } catch (Exception $e) {
            // Handle exceptions and return appropriate error responses
            Log::error("Error updating document metadata: " . $e->getMessage(), [
                'document_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to update document metadata'], 500);
        }
    }

    /**
     * Marks a document as processed or unprocessed
     *
     * @param DocumentProcessRequest $request
     * @param int $id
     * @return JsonResponse Success or error response
     */
    public function process(DocumentProcessRequest $request, int $id): JsonResponse
    {
        try {
            // Extract process_state from the validated request data
            $processState = $request->validated()['process_state'];

            // Get the authenticated user ID
            $userId = auth()->id();

            // Call documentManager->processDocument with the document ID, process state, and user ID
            $document = $this->documentManager->processDocument($id, $processState, $userId);

            // If processing fails, return 422 response with error message
            if (!$document) {
                return response()->json(['error' => 'Failed to process document'], 422);
            }

            // Return JSON response with success message and updated document status
            return response()->json(['message' => 'Document processed successfully', 'is_processed' => $document->is_processed]);
        } catch (Exception $e) {
            // Handle exceptions and return appropriate error responses
            Log::error("Error processing document: " . $e->getMessage(), [
                'document_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to process document'], 500);
        }
    }

    /**
     * Moves a document to the trash
     *
     * @param DocumentTrashRequest $request
     * @param int $id
     * @return JsonResponse Success or error response
     */
    public function trash(DocumentTrashRequest $request, int $id): JsonResponse
    {
        try {
            // Get the authenticated user ID
            $userId = auth()->id();

            // Call documentManager->trashDocument with the document ID and user ID
            $success = $this->documentManager->trashDocument($id, $userId);

            // If trashing fails, return 422 response with error message
            if (!$success) {
                return response()->json(['error' => 'Failed to trash document'], 422);
            }

            // Return JSON response with success message
            return response()->json(['message' => 'Document moved to trash successfully']);
        } catch (Exception $e) {
            // Handle exceptions and return appropriate error responses
            Log::error("Error trashing document: " . $e->getMessage(), [
                'document_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to trash document'], 500);
        }
    }

    /**
     * Retrieves the history of actions performed on a document
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse Document history as JSON response
     */
    public function history(Request $request, int $id): JsonResponse
    {
        try {
            // Extract pagination parameters from the request
            $perPage = $request->integer('per_page', 10);
            $direction = $request->string('direction', 'desc');

            // Call documentManager->getDocumentHistory with the document ID and pagination parameters
            $history = $this->documentManager->getDocumentHistory($id, $perPage, $direction);

            // Return JSON response with the document history data
            return response()->json($history);
        } catch (Exception $e) {
            // Handle exceptions and return appropriate error responses
            Log::error("Error retrieving document history: " . $e->getMessage(), [
                'document_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to retrieve document history'], 500);
        }
    }

    /**
     * Retrieves the file associated with a document
     *
     * @param Request $request
     * @param int $id
     * @return Response File download response or error response
     */
    public function file(Request $request, int $id): Response
    {
        try {
            // Call documentManager->getDocument with the provided ID
            $document = $this->documentManager->getDocument($id);

            // If document not found, return 404 response
            if (!$document) {
                return response('Document not found', 404);
            }

            // Get the main file from the document
            $file = $document->main_file;

            // If file not found, return 404 response
            if (!$file) {
                return response('File not found', 404);
            }

            // Return file download response with appropriate headers
            return response()->file($file->full_path, [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="' . $file->name . '"'
            ]);
        } catch (Exception $e) {
            // Handle exceptions and return appropriate error responses
            Log::error("Error retrieving document file: " . $e->getMessage(), [
                'document_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response('Failed to retrieve document file', 500);
        }
    }
}