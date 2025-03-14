<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // Laravel 10.0+
use App\Models\Document; // Laravel 10.0+
use App\Http\Requests\DocumentProcessRequest; // Laravel 10.0+
use App\Http\Requests\DocumentTrashRequest; // Laravel 10.0+
use App\Services\DocumentManager; // Custom Document Manager Service
use App\Services\NotificationService; // Custom Notification Service
use Illuminate\Http\JsonResponse; // Laravel 10.0+
use Illuminate\Http\Request; // Laravel 10.0+
use Illuminate\Http\Response; // Laravel 10.0+
use Illuminate\Support\Facades\Log; // Laravel 10.0+

/**
 * Controller responsible for handling document action operations such as processing documents and trashing documents.
 * This controller serves as the entry point for document workflow state changes in the Documents View feature.
 */
class DocumentActionController extends Controller
{
    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Constructor for the DocumentActionController
     *
     * @param DocumentManager $documentManager
     * @param NotificationService $notificationService
     */
    public function __construct(DocumentManager $documentManager, NotificationService $notificationService)
    {
        // Initialize the controller with DocumentManager and NotificationService dependencies
        $this->documentManager = $documentManager;
        $this->notificationService = $notificationService;
    }

    /**
     * Processes a document by marking it as processed or unprocessed
     *
     * @param DocumentProcessRequest $request
     * @param int $id
     * @return JsonResponse JSON response with the result of the operation
     */
    public function process(DocumentProcessRequest $request, int $id): JsonResponse
    {
        // Extract process_state from the validated request data
        $processState = $request->validated('process_state');

        // Get the authenticated user ID
        $userId = auth()->id();

        // Call documentManager->processDocument with document ID, process state, and user ID
        $document = $this->documentManager->processDocument($id, $processState, $userId);

        // If operation fails, return error response with appropriate message
        if (!$document) {
            Log::error("Failed to process document", ['document_id' => $id, 'process_state' => $processState, 'user_id' => $userId]);
            return response()->json(['message' => 'Failed to process document.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Send notification based on process state (processed or unprocessed)
        if ($processState) {
            $this->notificationService->sendDocumentProcessedNotification($id, $userId);
        } else {
            $this->notificationService->sendDocumentUnprocessedNotification($id, $userId);
        }

        // Return success response with appropriate message and updated document status
        return response()->json([
            'message' => 'Document processed successfully.',
            'document' => $document
        ], Response::HTTP_OK);
    }

    /**
     * Moves a document to the trash
     *
     * @param DocumentTrashRequest $request
     * @param int $id
     * @return JsonResponse JSON response with the result of the operation
     */
    public function trash(DocumentTrashRequest $request, int $id): JsonResponse
    {
        // Get the authenticated user ID
        $userId = auth()->id();

        // Call documentManager->trashDocument with document ID and user ID
        $trashed = $this->documentManager->trashDocument($id, $userId);

        // If operation fails, return error response with appropriate message
        if (!$trashed) {
            Log::error("Failed to trash document", ['document_id' => $id, 'user_id' => $userId]);
            return response()->json(['message' => 'Failed to trash document.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Send notification about document being trashed
        $this->notificationService->sendDocumentTrashedNotification($id, $userId);

        // Return success response with appropriate message
        return response()->json(['message' => 'Document moved to trash successfully.'], Response::HTTP_OK);
    }
}