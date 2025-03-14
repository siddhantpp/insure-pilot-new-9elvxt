<?php

use App\Jobs\ArchiveDocuments;
use App\Jobs\CleanupTrashedDocuments;
use App\Jobs\ProcessDocumentIndex;
use App\Models\Document;
use App\Services\DocumentManager;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Artisan; // laravel/framework ^10.0
use Illuminate\Support\Facades\Log; // laravel/framework ^10.0
use Carbon\Carbon; // nesbot/carbon ^2.0

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

/**
 * Command to manually trigger the document archival process
 * 
 * This command dispatches the ArchiveDocuments job which will move processed
 * documents that have exceeded their retention period to archive storage.
 */
Artisan::command('documents:archive', function () {
    $this->info('Starting document archival process...');
    
    try {
        // Get service instances
        $documentManager = app(DocumentManager::class);
        $auditLogger = app(AuditLogger::class);
        
        // Default chunk size from configuration or fallback to 1000
        $chunkSize = config('documents.archive.chunk_size', 1000);
        
        // Dispatch the archive job
        ArchiveDocuments::dispatch($documentManager, $auditLogger, $chunkSize);
        
        // Log the action
        Log::info('Document archival process initiated via console command');
        
        // Provide feedback to console
        $this->info('Document archival job has been dispatched successfully.');
        $this->info('Documents processed more than ' . config('documents.archive.period_days', 730) . ' days ago will be moved to archive storage.');
    } catch (\Exception $e) {
        $this->error('Failed to initiate document archival process: ' . $e->getMessage());
        Log::error('Document archival command failed: ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);
    }
})->describe('Archive processed documents that have exceeded their retention period');

/**
 * Command to manually trigger the cleanup of trashed documents
 * 
 * This command dispatches the CleanupTrashedDocuments job which will permanently
 * delete documents that have been in the trash longer than the configured retention period.
 */
Artisan::command('documents:cleanup-trashed', function () {
    $this->info('Starting cleanup of trashed documents...');
    
    try {
        // Get service instances
        $documentManager = app(DocumentManager::class);
        $auditLogger = app(AuditLogger::class);
        
        // Default batch size from configuration or fallback to 500
        $batchSize = config('documents.document_processing.cleanup_batch_size', 500);
        
        // System user ID for audit logging
        $systemUserId = config('documents.system_user_id', 1);
        
        // Dispatch the cleanup job
        CleanupTrashedDocuments::dispatch($documentManager, $auditLogger, $batchSize, $systemUserId);
        
        // Log the action
        Log::info('Trashed documents cleanup process initiated via console command');
        
        // Provide feedback to console
        $this->info('Trashed documents cleanup job has been dispatched successfully.');
        $this->info('Documents in trash for more than ' . config('documents.document_processing.trash_retention_days', 90) . ' days will be permanently deleted.');
    } catch (\Exception $e) {
        $this->error('Failed to initiate trashed documents cleanup: ' . $e->getMessage());
        Log::error('Trashed documents cleanup command failed: ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);
    }
})->describe('Permanently delete documents that have been in trash longer than the retention period');

/**
 * Command to rebuild the document search index
 * 
 * This command initiates a complete rebuild of the document search index
 * by dispatching a ProcessDocumentIndex job for each active document.
 */
Artisan::command('documents:rebuild-index', function () {
    $this->info('Starting document search index rebuild...');
    
    try {
        // Count documents to be indexed
        $documentCount = Document::whereIn('status_id', [
            Document::STATUS_UNPROCESSED,
            Document::STATUS_PROCESSED
        ])->count();
        
        $this->info("Found {$documentCount} documents to be indexed.");
        
        if ($documentCount === 0) {
            $this->info('No documents to index. Exiting.');
            return;
        }
        
        // Confirm with user if there are many documents
        if ($documentCount > 1000 && !$this->confirm("This will index {$documentCount} documents which may take some time. Proceed?")) {
            $this->info('Operation cancelled by user.');
            return;
        }
        
        $bar = $this->output->createProgressBar($documentCount);
        $bar->start();
        
        // Process documents in chunks to avoid memory issues
        Document::whereIn('status_id', [
            Document::STATUS_UNPROCESSED,
            Document::STATUS_PROCESSED
        ])->chunk(100, function ($documents) use ($bar) {
            foreach ($documents as $document) {
                // Dispatch job to process each document's index with 'create' operation
                ProcessDocumentIndex::dispatch($document->id, 'create');
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine(2);
        
        // Log the action
        Log::info("Document search index rebuild initiated for {$documentCount} documents");
        
        // Provide feedback to console
        $this->info('Document search index rebuild has been initiated successfully.');
        $this->info('The indexing will continue in the background.');
    } catch (\Exception $e) {
        $this->error('Failed to rebuild document search index: ' . $e->getMessage());
        Log::error('Document index rebuild command failed: ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);
    }
})->describe('Rebuild the document search index for all active documents');

/**
 * Command to display document statistics
 * 
 * This command displays statistics about documents in the system,
 * including counts by status and pending archival/cleanup information.
 */
Artisan::command('documents:stats', function () {
    $this->info('Retrieving document statistics...');
    
    try {
        // Get total document count
        $totalDocuments = Document::count();
        $totalWithTrashed = Document::withTrashed()->count();
        
        // Get counts by status
        $unprocessedCount = Document::unprocessed()->count();
        $processedCount = Document::processed()->count();
        $trashedCount = Document::trashed()->count();
        
        // Calculate documents pending archival
        $archivePeriod = config('documents.archive.period_days', 730);
        $archiveCutoff = Carbon::now()->subDays($archivePeriod);
        $pendingArchive = Document::processed()
            ->where('updated_at', '<', $archiveCutoff)
            ->count();
        
        // Calculate documents pending permanent deletion
        $trashRetention = config('documents.document_processing.trash_retention_days', 90);
        $trashCutoff = Carbon::now()->subDays($trashRetention);
        $pendingDeletion = Document::trashed()
            ->where('updated_at', '<', $trashCutoff)
            ->count();
        
        // Display statistics in a table format
        $this->info('Document Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Documents', $totalDocuments],
                ['Total (Including Soft-Deleted)', $totalWithTrashed],
                ['Unprocessed Documents', $unprocessedCount],
                ['Processed Documents', $processedCount],
                ['Trashed Documents', $trashedCount],
                ['Pending Archive (Older than ' . $archivePeriod . ' days)', $pendingArchive],
                ['Pending Deletion (In trash longer than ' . $trashRetention . ' days)', $pendingDeletion],
            ]
        );
        
        // Display retention policies
        $this->info('Current Retention Policies:');
        $this->table(
            ['Policy', 'Duration'],
            [
                ['Document Archive', $archivePeriod . ' days after processing'],
                ['Trash Retention', $trashRetention . ' days before permanent deletion'],
            ]
        );
    } catch (\Exception $e) {
        $this->error('Failed to retrieve document statistics: ' . $e->getMessage());
        Log::error('Document stats command failed: ' . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);
    }
})->describe('Display document statistics and retention metrics');