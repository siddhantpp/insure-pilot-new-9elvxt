<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; // ^10.0
use Illuminate\Queue\InteractsWithQueue; // ^10.0
use Illuminate\Queue\SerializesModels; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0

/**
 * Job class that processes document indexing operations asynchronously. This job is dispatched by the UpdateSearchIndex listener when document-related events occur.
 */
class ProcessDocumentIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The ID of the document to index.
     *
     * @var int
     */
    protected $documentId;

    /**
     * The operation type to perform (create, update, delete).
     *
     * @var string
     */
    protected $operation;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string
     */
    public $queue = 'document-background';

    /**
     * Creates a new ProcessDocumentIndex job instance
     *
     * @param int $documentId
     * @param string $operation
     * @return void
     */
    public function __construct(int $documentId, string $operation)
    {
        $this->documentId = $documentId;
        $this->operation = $operation;
        $this->tries = 3;
        $this->maxExceptions = 3;
        $this->timeout = 120;
        $this->queue = 'document-background';
    }

    /**
     * Execute the job to process the document index operation
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Starting document indexing job for document ID: {$this->documentId}, operation: {$this->operation}");
        
        try {
            // Retrieve the document by ID
            $document = Document::find($this->documentId);
            
            // If document doesn't exist and operation is 'delete', process deletion from index
            if (!$document && $this->operation === 'delete') {
                $this->deleteIndex($this->documentId);
                Log::info("Completed document deletion from index for ID: {$this->documentId}");
                return;
            }
            
            // If document doesn't exist and operation is not 'delete', log error and exit
            if (!$document) {
                Log::error("Document not found for indexing, ID: {$this->documentId}, operation: {$this->operation}");
                return;
            }
            
            // Process based on operation type
            $success = false;
            $actionText = '';
            
            switch ($this->operation) {
                case 'create':
                    $success = $this->createIndex($document);
                    $actionText = 'creation';
                    break;
                
                case 'update':
                    $success = $this->updateIndex($document);
                    $actionText = 'update';
                    break;
                
                case 'delete':
                    $success = $this->deleteIndex($this->documentId);
                    $actionText = 'deletion';
                    break;
                
                default:
                    Log::error("Invalid operation type for document indexing: {$this->operation}");
                    return;
            }
            
            // Log the result of the operation
            if ($success) {
                Log::info("Completed document index {$actionText} for ID: {$this->documentId}");
            } else {
                Log::warning("Document index {$actionText} may not have completed successfully for ID: {$this->documentId}");
            }
            
        } catch (\Exception $exception) {
            Log::error("Error processing document indexing job: " . $exception->getMessage(), [
                'document_id' => $this->documentId,
                'operation' => $this->operation,
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
            
            throw $exception; // Re-throw to trigger job retry
        }
    }

    /**
     * Add a document to the search index
     *
     * @param Document $document
     * @return bool True if indexing was successful, false otherwise
     */
    protected function createIndex(Document $document)
    {
        try {
            // Prepare document data for indexing (title, content, metadata)
            $indexData = [
                'id' => $document->id,
                'name' => $document->name,
                'description' => $document->description,
                'policy_number' => $document->policy_number,
                'loss_sequence' => $document->loss_sequence,
                'claimant_name' => $document->claimant_name,
                'producer_number' => $document->producer_number,
                'is_processed' => $document->is_processed,
                'created_at' => $document->created_at->toIso8601String(),
                'updated_at' => $document->updated_at->toIso8601String(),
            ];
            
            // Extract document content if available
            if ($document->main_file) {
                $indexData['content'] = "Content of {$document->name}"; // Placeholder for actual content extraction
            }
            
            // Here we would integrate with the actual search service to index the document
            // Example: SearchService::index($indexData);
            
            Log::debug("Created search index for document ID: {$document->id}");
            return true;
            
        } catch (\Exception $exception) {
            Log::error("Failed to create search index for document: " . $exception->getMessage(), [
                'document_id' => $document->id,
                'exception' => get_class($exception),
            ]);
            return false;
        }
    }

    /**
     * Update a document in the search index
     *
     * @param Document $document
     * @return bool True if update was successful, false otherwise
     */
    protected function updateIndex(Document $document)
    {
        try {
            // Check if document exists in the index
            // In a real implementation, we would check if the document exists in the search index
            // If not, we would create it instead of updating
            
            // For this example, we'll reuse the createIndex method for the update
            // as the logic is similar - gather data and send to the search service
            return $this->createIndex($document);
            
        } catch (\Exception $exception) {
            Log::error("Failed to update search index for document: " . $exception->getMessage(), [
                'document_id' => $document->id,
                'exception' => get_class($exception),
            ]);
            return false;
        }
    }

    /**
     * Remove a document from the search index
     *
     * @param int $documentId
     * @return bool True if deletion was successful, false otherwise
     */
    protected function deleteIndex(int $documentId)
    {
        try {
            // Here we would integrate with the actual search service to delete the document
            // Example: SearchService::delete($documentId);
            
            Log::debug("Deleted document from search index, ID: {$documentId}");
            return true;
            
        } catch (\Exception $exception) {
            Log::error("Failed to delete document from search index: " . $exception->getMessage(), [
                'document_id' => $documentId,
                'exception' => get_class($exception),
            ]);
            return false;
        }
    }

    /**
     * Handle a job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Document indexing job failed after multiple attempts", [
            'document_id' => $this->documentId,
            'operation' => $this->operation,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
        
        // In a production environment, we might want to:
        // 1. Send notification to administrators
        // 2. Add the document to a "failed index" list for manual review
        // 3. Trigger a fallback mechanism
    }
}