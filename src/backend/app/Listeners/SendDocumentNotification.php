<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log; // ^10.0
use App\Services\NotificationService;
use App\Events\DocumentCreated;
use App\Events\DocumentUpdated;
use App\Events\DocumentProcessed;
use App\Events\DocumentTrashed;

/**
 * Event listener responsible for sending notifications to relevant users when document-related events occur in the system.
 * This listener handles events such as document creation, updates, processing status changes, and document trashing,
 * ensuring users are promptly informed about important document activities.
 */
class SendDocumentNotification
{
    /**
     * The notification service instance.
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Creates a new SendDocumentNotification listener instance
     *
     * @param NotificationService $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the DocumentCreated event by sending a document creation notification
     *
     * @param DocumentCreated $event
     * @return void
     */
    public function handleDocumentCreated(DocumentCreated $event)
    {
        try {
            $documentId = $event->document->id;
            $userId = $event->userId;
            
            $this->notificationService->sendDocumentCreatedNotification($documentId, $userId);
            
            Log::info("Document created notification handled for document {$documentId} by user {$userId}");
        } catch (\Exception $e) {
            Log::error("Error handling document created notification: " . $e->getMessage(), [
                'event' => get_class($event),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId ?? null,
                'exception' => $e
            ]);
        }
    }

    /**
     * Handle the DocumentUpdated event by sending a document update notification
     *
     * @param DocumentUpdated $event
     * @return void
     */
    public function handleDocumentUpdated(DocumentUpdated $event)
    {
        try {
            $documentId = $event->document->id;
            $userId = $event->userId;
            $changes = $event->changes;
            
            $this->notificationService->sendDocumentUpdatedNotification($documentId, $userId, $changes);
            
            Log::info("Document updated notification handled for document {$documentId} by user {$userId}");
        } catch (\Exception $e) {
            Log::error("Error handling document updated notification: " . $e->getMessage(), [
                'event' => get_class($event),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId ?? null,
                'changes' => $event->changes ?? [],
                'exception' => $e
            ]);
        }
    }

    /**
     * Handle the DocumentProcessed event by sending a document process or unprocess notification
     *
     * @param DocumentProcessed $event
     * @return void
     */
    public function handleDocumentProcessed(DocumentProcessed $event)
    {
        try {
            $documentId = $event->document->id;
            $userId = $event->userId;
            $isProcessed = $event->isProcessed;
            
            if ($isProcessed) {
                $this->notificationService->sendDocumentProcessedNotification($documentId, $userId);
                Log::info("Document processed notification handled for document {$documentId} by user {$userId}");
            } else {
                $this->notificationService->sendDocumentUnprocessedNotification($documentId, $userId);
                Log::info("Document unprocessed notification handled for document {$documentId} by user {$userId}");
            }
        } catch (\Exception $e) {
            Log::error("Error handling document processed notification: " . $e->getMessage(), [
                'event' => get_class($event),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId ?? null,
                'is_processed' => $event->isProcessed ?? null,
                'exception' => $e
            ]);
        }
    }

    /**
     * Handle the DocumentTrashed event by sending a document trash notification
     *
     * @param DocumentTrashed $event
     * @return void
     */
    public function handleDocumentTrashed(DocumentTrashed $event)
    {
        try {
            $documentId = $event->document->id;
            $userId = $event->userId;
            
            $this->notificationService->sendDocumentTrashedNotification($documentId, $userId);
            
            Log::info("Document trashed notification handled for document {$documentId} by user {$userId}");
        } catch (\Exception $e) {
            Log::error("Error handling document trashed notification: " . $e->getMessage(), [
                'event' => get_class($event),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId ?? null,
                'exception' => $e
            ]);
        }
    }
}