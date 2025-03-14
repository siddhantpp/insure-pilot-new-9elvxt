<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable; // ^10.0
use Illuminate\Contracts\Queue\ShouldQueue; // ^10.0
use Illuminate\Foundation\Bus\Dispatchable; // ^10.0
use Illuminate\Queue\InteractsWithQueue; // ^10.0
use Illuminate\Queue\SerializesModels; // ^10.0
use App\Models\Document;
use App\Services\DocumentManager;
use App\Services\AuditLogger;
use Carbon\Carbon; // ^2.0
use Illuminate\Support\Facades\DB; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Illuminate\Support\Facades\Config; // ^10.0

/**
 * Job class responsible for permanently deleting documents that have been in the trash for longer than the configured retention period.
 * This job is typically scheduled to run daily during off-peak hours.
 */
class CleanupTrashedDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The document manager service.
     *
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * The audit logger service.
     *
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * The batch size for processing documents.
     *
     * @var int
     */
    protected $batchSize;

    /**
     * The system user ID to use for audit logging.
     *
     * @var int
     */
    protected $systemUserId;

    /**
     * Create a new job instance.
     *
     * @param DocumentManager $documentManager
     * @param AuditLogger $auditLogger
     * @param int $batchSize
     * @param int $systemUserId
     * @return void
     */
    public function __construct(
        DocumentManager $documentManager,
        AuditLogger $auditLogger,
        int $batchSize = 500,
        int $systemUserId = 1
    ) {
        $this->documentManager = $documentManager;
        $this->auditLogger = $auditLogger;
        $this->batchSize = $batchSize;
        $this->systemUserId = $systemUserId;
    }

    /**
     * Execute the job to clean up trashed documents.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Get the retention period from configuration
            $retentionDays = $this->getRetentionPeriod();
            
            // Calculate the cutoff date based on the retention period
            $cutoffDate = $this->getCutoffDate($retentionDays);
            
            // Begin a database transaction
            DB::beginTransaction();
            
            // Query for documents that have been in trash status longer than the retention period
            $query = $this->getTrashedDocumentsQuery($cutoffDate);
            
            // Count total documents to be deleted
            $totalDocuments = $query->count();
            
            if ($totalDocuments === 0) {
                Log::info('CleanupTrashedDocuments: No expired trashed documents found');
                DB::commit();
                return;
            }
            
            // Initialize counters
            $processedCount = 0;
            $successCount = 0;
            
            // Process documents in batches of the specified size
            $query->chunkById($this->batchSize, function ($documents) use (&$processedCount, &$successCount) {
                foreach ($documents as $document) {
                    $processedCount++;
                    
                    // Permanently delete the document and log the action
                    if ($this->permanentlyDeleteDocument($document)) {
                        $successCount++;
                    }
                }
            });
            
            // Commit the transaction
            DB::commit();
            
            // Log the number of documents that were permanently deleted
            Log::info("CleanupTrashedDocuments: Processed {$processedCount} documents, permanently deleted {$successCount}");
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            
            // Log the error
            Log::error("CleanupTrashedDocuments: Error processing trashed documents: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get the document trash retention period in days from configuration.
     *
     * @return int Number of days to retain trashed documents
     */
    protected function getRetentionPeriod(): int
    {
        return Config::get('documents.document_processing.trash_retention_days', 90);
    }

    /**
     * Calculate the cutoff date for document deletion based on the retention period.
     *
     * @param int $retentionDays
     * @return string Formatted date string for the cutoff date
     */
    protected function getCutoffDate(int $retentionDays): string
    {
        return Carbon::now()->subDays($retentionDays)->format('Y-m-d H:i:s');
    }

    /**
     * Build the query to find documents that should be permanently deleted.
     *
     * @param string $cutoffDate
     * @return \Illuminate\Database\Eloquent\Builder Query builder for retrieving expired trashed documents
     */
    protected function getTrashedDocumentsQuery(string $cutoffDate)
    {
        return Document::trashed()
            ->where('status_id', Document::STATUS_TRASHED)
            ->where('updated_at', '<', $cutoffDate);
    }

    /**
     * Permanently delete a document and log the action.
     *
     * @param Document $document
     * @return bool True if deletion was successful, false otherwise
     */
    protected function permanentlyDeleteDocument(Document $document): bool
    {
        try {
            // Verify the document is actually in the trash
            if (!$this->documentManager->isDocumentTrashed($document->id)) {
                Log::warning("CleanupTrashedDocuments: Document #{$document->id} is not in trash, skipping permanent deletion");
                return false;
            }
            
            // Permanently delete the document using forceDelete()
            $document->forceDelete();
            
            // Log the permanent deletion action using the AuditLogger
            $this->auditLogger->logDocumentAction(
                $document->id,
                $this->systemUserId,
                'permanent_delete',
                'Document permanently deleted after exceeding retention period'
            );
            
            return true;
        } catch (\Exception $e) {
            // Log the error and return false
            Log::error("CleanupTrashedDocuments: Error permanently deleting document #{$document->id}: " . $e->getMessage());
            return false;
        }
    }
}