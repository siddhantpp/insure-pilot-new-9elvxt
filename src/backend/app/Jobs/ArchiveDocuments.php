<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Services\DocumentManager;
use App\Services\AuditLogger;

/**
 * Job class responsible for archiving processed documents that have been in that state for longer than the configured
 * retention period. This job is scheduled to run periodically to move older processed documents to an archive storage
 * location while maintaining their metadata and audit trail.
 */
class ArchiveDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The constant for archive action type ID.
     * 
     * @var int
     */
    const ARCHIVE_ACTION_TYPE_ID = 8; // Action type ID for archive actions

    /**
     * The system user ID used for system-generated actions.
     * 
     * @var int
     */
    const SYSTEM_USER_ID = 1; // System user ID for automated processes

    /**
     * The number of documents to process in each chunk.
     *
     * @var int
     */
    protected $chunkSize;

    /**
     * The DocumentManager instance.
     *
     * @var \App\Services\DocumentManager
     */
    protected $documentManager;

    /**
     * The AuditLogger instance.
     *
     * @var \App\Services\AuditLogger
     */
    protected $auditLogger;

    /**
     * Constructor for the ArchiveDocuments job
     *
     * @param DocumentManager $documentManager
     * @param AuditLogger $auditLogger
     * @param int $chunkSize
     */
    public function __construct(DocumentManager $documentManager, AuditLogger $auditLogger, int $chunkSize = 1000)
    {
        $this->documentManager = $documentManager;
        $this->auditLogger = $auditLogger;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Executes the job to archive eligible processed documents
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting document archival process');

        // Calculate the cutoff date for archive eligibility (documents processed more than 2 years ago)
        $cutoffDate = $this->getArchiveCutoffDate();

        // Query for processed documents with updated_at date before the cutoff date
        $totalArchived = 0;
        Document::processed()
            ->where('updated_at', '<', $cutoffDate)
            ->chunk($this->chunkSize, function ($documents) use (&$totalArchived) {
                $count = $this->processDocumentsForArchive($documents);
                $totalArchived += $count;
                Log::info("Archived chunk of {$count} documents. Total archived: {$totalArchived}");
            });

        Log::info("Document archival process complete. Total documents archived: {$totalArchived}");
    }

    /**
     * Processes a chunk of documents for archiving
     *
     * @param array $documents
     * @return int Count of successfully archived documents
     */
    protected function processDocumentsForArchive($documents): int
    {
        $archivedCount = 0;

        // Begin a database transaction
        DB::beginTransaction();

        try {
            foreach ($documents as $document) {
                // Copy document file to archive storage location
                $archivePath = $this->copyDocumentToArchive($document);

                // Update document record with archive information
                $this->updateDocumentArchiveStatus($document, $archivePath);

                // Log the archive action for audit trail
                $this->auditLogger->logDocumentAction(
                    $document->id,
                    self::SYSTEM_USER_ID,
                    'archive',
                    'Document archived due to retention policy'
                );

                $archivedCount++;
            }

            // Commit the transaction
            DB::commit();
            return $archivedCount;
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            
            Log::error("Error archiving documents: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return the current count of archived documents
            return $archivedCount;
        }
    }

    /**
     * Calculates the cutoff date for document archive eligibility
     *
     * @return \Carbon\Carbon Carbon instance representing the cutoff date
     */
    protected function getArchiveCutoffDate(): Carbon
    {
        // Get the archive period from configuration (default to 730 days/2 years if not configured)
        $archivePeriodDays = Config::get('documents.archive.period_days', 730);
        
        // Create a Carbon instance for the current date
        $cutoffDate = Carbon::now();
        
        // Subtract the archive period from the current date
        $cutoffDate->subDays($archivePeriodDays);
        
        return $cutoffDate;
    }

    /**
     * Copies a document file to the archive storage location
     *
     * @param \App\Models\Document $document
     * @return bool True if the copy was successful, false otherwise
     */
    protected function copyDocumentToArchive(Document $document): ?string
    {
        // Get the document file path using the DocumentManager
        $filePath = $this->documentManager->getDocumentFile($document->id);
        
        // If no file exists, return true (nothing to archive)
        if (!$filePath) {
            return null;
        }
        
        // Create the archive path based on document metadata
        $archivePath = 'archives/';
        
        // Add policy information to path if available
        if ($document->policy_id) {
            $document->load('policy');
            if ($document->policy) {
                $archivePath .= 'policy_' . $document->policy->id . '/';
            }
        }
        
        // Add date-based folder structure
        $archivePath .= date('Y/m/d') . '/';
        
        // Add document ID and original filename
        $archivePath .= $document->id . '_' . basename($filePath);
        
        // Copy the file to the archive location using Storage facade
        $archiveDisk = Config::get('documents.archive.disk', 'archives');
        if (Storage::disk($archiveDisk)->put($archivePath, file_get_contents($filePath))) {
            return $archivePath;
        }
        
        return null;
    }

    /**
     * Updates a document record with archive information
     *
     * @param \App\Models\Document $document
     * @param string $archivePath
     * @return bool True if the update was successful, false otherwise
     */
    protected function updateDocumentArchiveStatus(Document $document, ?string $archivePath): bool
    {
        // Update the document record with archive_path and archived_at fields
        $document->archive_path = $archivePath;
        $document->archived_at = Carbon::now();
        return $document->save();
    }

    /**
     * Handles job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Document archival job failed: ' . $exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString()
        ]);
    }
}