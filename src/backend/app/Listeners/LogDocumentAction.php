<?php

namespace App\Listeners;

use App\Events\DocumentCreated;
use App\Events\DocumentUpdated;
use App\Events\DocumentProcessed;
use App\Events\DocumentTrashed;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;

/**
 * Event listener that logs document actions to the audit trail when document-related events occur in the system.
 * This listener handles various document events and delegates to the AuditLogger service to create appropriate audit records.
 */
class LogDocumentAction
{
    /**
     * The audit logger service instance.
     *
     * @var \App\Services\AuditLogger
     */
    protected $auditLogger;

    /**
     * Creates a new LogDocumentAction listener instance
     *
     * @param \App\Services\AuditLogger $auditLogger
     * @return void
     */
    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the DocumentCreated event by logging a document creation action
     *
     * @param \App\Events\DocumentCreated $event
     * @return void
     */
    public function handleDocumentCreated(DocumentCreated $event)
    {
        try {
            $documentId = $event->document->id;
            $userId = $event->userId;
            
            $this->auditLogger->logDocumentAction($documentId, $userId, 'create', 'Document created');
        } catch (\Exception $e) {
            Log::error('Error logging document creation action', [
                'error' => $e->getMessage(),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId ?? null
            ]);
        }
    }

    /**
     * Handle the DocumentUpdated event by logging a document update action
     *
     * @param \App\Events\DocumentUpdated $event
     * @return void
     */
    public function handleDocumentUpdated(DocumentUpdated $event)
    {
        try {
            $documentId = $event->document->id;
            $userId = $event->userId;
            $changes = $event->changes;
            
            $this->auditLogger->logDocumentEdit($documentId, $userId, $changes);
        } catch (\Exception $e) {
            Log::error('Error logging document update action', [
                'error' => $e->getMessage(),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId ?? null,
                'changes' => $event->changes ?? []
            ]);
        }
    }

    /**
     * Handle the DocumentProcessed event by logging a document process or unprocess action
     *
     * @param \App\Events\DocumentProcessed $event
     * @return void
     */
    public function handleDocumentProcessed(DocumentProcessed $event)
    {
        try {
            $documentId = $event->document->id;
            $userId = $event->userId;
            $isProcessed = $event->isProcessed;
            
            if ($isProcessed) {
                $this->auditLogger->logDocumentProcess($documentId, $userId);
            } else {
                $this->auditLogger->logDocumentUnprocess($documentId, $userId);
            }
        } catch (\Exception $e) {
            Log::error('Error logging document processing action', [
                'error' => $e->getMessage(),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId ?? null,
                'is_processed' => $event->isProcessed ?? null
            ]);
        }
    }

    /**
     * Handle the DocumentTrashed event by logging a document trash action
     *
     * @param \App\Events\DocumentTrashed $event
     * @return void
     */
    public function handleDocumentTrashed(DocumentTrashed $event)
    {
        try {
            $documentId = $event->document->id;
            $userId = $event->userId;
            
            $this->auditLogger->logDocumentTrash($documentId, $userId);
        } catch (\Exception $e) {
            Log::error('Error logging document trash action', [
                'error' => $e->getMessage(),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId ?? null
            ]);
        }
    }
}