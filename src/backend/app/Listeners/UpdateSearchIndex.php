<?php

namespace App\Listeners;

use App\Events\DocumentCreated;
use App\Events\DocumentUpdated;
use App\Events\DocumentProcessed;
use App\Events\DocumentTrashed;
use App\Jobs\ProcessDocumentIndex;
use Illuminate\Support\Facades\Log; // ^10.0

/**
 * Event listener that updates the search index when document-related events occur.
 * This listener dispatches background jobs to process search index updates asynchronously,
 * ensuring that document content and metadata are searchable throughout the application
 * without impacting user experience.
 */
class UpdateSearchIndex
{
    /**
     * Creates a new UpdateSearchIndex listener instance
     *
     * @return void
     */
    public function __construct()
    {
        // Initialize the listener
    }

    /**
     * Handle the DocumentCreated event by dispatching a job to add the document to the search index
     *
     * @param  \App\Events\DocumentCreated  $event
     * @return void
     */
    public function handleDocumentCreated(DocumentCreated $event)
    {
        $documentId = $event->document->id;
        Log::info("Dispatching document creation indexing job for document ID: {$documentId}");
        
        ProcessDocumentIndex::dispatch($documentId, 'create')
            ->onQueue('document-background');
    }

    /**
     * Handle the DocumentUpdated event by dispatching a job to update the document in the search index
     *
     * @param  \App\Events\DocumentUpdated  $event
     * @return void
     */
    public function handleDocumentUpdated(DocumentUpdated $event)
    {
        $documentId = $event->document->id;
        Log::info("Dispatching document update indexing job for document ID: {$documentId}");
        
        ProcessDocumentIndex::dispatch($documentId, 'update')
            ->onQueue('document-background');
    }

    /**
     * Handle the DocumentProcessed event by dispatching a job to update the document's processed status in the search index
     *
     * @param  \App\Events\DocumentProcessed  $event
     * @return void
     */
    public function handleDocumentProcessed(DocumentProcessed $event)
    {
        $documentId = $event->document->id;
        Log::info("Dispatching document processing indexing job for document ID: {$documentId}");
        
        ProcessDocumentIndex::dispatch($documentId, 'update')
            ->onQueue('document-background');
    }

    /**
     * Handle the DocumentTrashed event by dispatching a job to remove the document from the search index
     *
     * @param  \App\Events\DocumentTrashed  $event
     * @return void
     */
    public function handleDocumentTrashed(DocumentTrashed $event)
    {
        $documentId = $event->document->id;
        Log::info("Dispatching document deletion indexing job for document ID: {$documentId}");
        
        ProcessDocumentIndex::dispatch($documentId, 'delete')
            ->onQueue('document-background');
    }
}