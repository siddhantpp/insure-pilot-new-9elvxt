<?php

namespace App\Services;

use App\Models\Document;
use App\Models\File;
use App\Services\FileStorage;
use App\Services\MetadataService;
use App\Services\AuditLogger;
use App\Services\PdfViewerService;
use Illuminate\Support\Facades\DB; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Exception; // 8.2
use Illuminate\Support\Facades\Config; // ^10.0

/**
 * Service responsible for managing document operations in the Insure Pilot system.
 * This service acts as a central coordinator for document-related functionality,
 * including document retrieval, metadata management, processing actions, and history tracking.
 * It integrates with other services like FileStorage, MetadataService, and AuditLogger
 * to provide a comprehensive document management solution for the Documents View feature.
 */
class DocumentManager
{
    /**
     * The FileStorage service instance for file operations.
     *
     * @var FileStorage
     */
    protected $fileStorage;

    /**
     * The MetadataService instance for document metadata operations.
     *
     * @var MetadataService
     */
    protected $metadataService;

    /**
     * The AuditLogger service instance for logging document actions.
     *
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * The PdfViewerService instance for PDF viewer operations.
     *
     * @var PdfViewerService
     */
    protected $pdfViewerService;

    /**
     * Constructor for the DocumentManager service
     *
     * @param FileStorage $fileStorage
     * @param MetadataService $metadataService
     * @param AuditLogger $auditLogger
     * @param PdfViewerService $pdfViewerService
     */
    public function __construct(
        FileStorage $fileStorage,
        MetadataService $metadataService,
        AuditLogger $auditLogger,
        PdfViewerService $pdfViewerService
    ) {
        $this->fileStorage = $fileStorage;
        $this->metadataService = $metadataService;
        $this->auditLogger = $auditLogger;
        $this->pdfViewerService = $pdfViewerService;
    }

    /**
     * Retrieves a paginated list of documents with optional filtering
     *
     * @param array $filters
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDirection
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of documents
     */
    public function getDocuments(
        array $filters = [],
        int $perPage = 15,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ) {
        try {
            // Start with a basic query
            $query = Document::query();
            
            // Apply filters
            if (!empty($filters)) {
                $query = $this->applyDocumentFilters($query, $filters);
            }
            
            // Apply sorting
            $query->orderBy($sortBy, $sortDirection);
            
            // Eager load related entities for performance
            $query->with(['policy', 'loss', 'claimant', 'producer', 'files']);
            
            // Return paginated results
            return $query->paginate($perPage);
        } catch (Exception $e) {
            Log::error("Error retrieving documents: " . $e->getMessage(), [
                'filters' => $filters,
                'perPage' => $perPage,
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty paginator on error
            return Document::where('id', 0)->paginate($perPage);
        }
    }

    /**
     * Retrieves a specific document by ID with related entities
     *
     * @param int $documentId
     * @return ?\App\Models\Document Document with loaded relationships or null if not found
     */
    public function getDocument(int $documentId): ?Document
    {
        try {
            // Use the MetadataService to get document metadata
            $metadata = $this->metadataService->getDocumentMetadata($documentId);
            
            // If no metadata was found, return null
            if (!$metadata) {
                return null;
            }
            
            // Find the document with relationships loaded
            $document = Document::with([
                'policy',
                'loss',
                'claimant',
                'producer',
                'files',
                'users',
                'userGroups'
            ])->find($documentId);
            
            return $document;
        } catch (Exception $e) {
            Log::error("Error retrieving document: " . $e->getMessage(), [
                'documentId' => $documentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Updates a document with the provided metadata
     *
     * @param int $documentId
     * @param array $data
     * @param int $userId
     * @return ?\App\Models\Document Updated document or null if update failed
     */
    public function updateDocument(int $documentId, array $data, int $userId): ?Document
    {
        try {
            // Check if document exists and is not processed
            $document = Document::find($documentId);
            
            if (!$document) {
                Log::error("Document not found for update", ['documentId' => $documentId]);
                return null;
            }
            
            if ($document->is_processed) {
                Log::warning("Cannot update processed document", ['documentId' => $documentId]);
                return null;
            }
            
            // Validate metadata using MetadataService
            $validationErrors = $this->metadataService->validateMetadataRelationships($data);
            
            if (!empty($validationErrors)) {
                Log::warning("Metadata validation failed", [
                    'documentId' => $documentId,
                    'errors' => $validationErrors
                ]);
                return null;
            }
            
            // Begin transaction
            DB::beginTransaction();
            
            // Update metadata using MetadataService
            $result = $this->metadataService->updateDocumentMetadata($documentId, $data, $userId);
            
            if (!$result) {
                // If update failed, rollback and return null
                DB::rollBack();
                return null;
            }
            
            // Commit transaction
            DB::commit();
            
            // Return the updated document
            return $this->getDocument($documentId);
        } catch (Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Log::error("Error updating document: " . $e->getMessage(), [
                'documentId' => $documentId,
                'userId' => $userId,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Marks a document as processed or unprocessed
     *
     * @param int $documentId
     * @param bool $processState
     * @param int $userId
     * @return ?\App\Models\Document Updated document or null if processing failed
     */
    public function processDocument(int $documentId, bool $processState, int $userId): ?Document
    {
        try {
            // Find the document
            $document = Document::find($documentId);
            
            if (!$document) {
                Log::error("Document not found for processing", ['documentId' => $documentId]);
                return null;
            }
            
            // Begin transaction
            DB::beginTransaction();
            
            if ($processState) {
                // Mark as processed
                $success = $document->markAsProcessed();
                
                if ($success) {
                    // Log the process action
                    $this->auditLogger->logDocumentProcess($documentId, $userId);
                }
            } else {
                // Mark as unprocessed
                $success = $document->markAsUnprocessed();
                
                if ($success) {
                    // Log the unprocess action
                    $this->auditLogger->logDocumentUnprocess($documentId, $userId);
                }
            }
            
            if (!$success) {
                // If update failed, rollback and return null
                DB::rollBack();
                return null;
            }
            
            // Commit transaction
            DB::commit();
            
            // Reload the document to get the updated state
            $document->refresh();
            
            return $document;
        } catch (Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Log::error("Error processing document: " . $e->getMessage(), [
                'documentId' => $documentId,
                'processState' => $processState,
                'userId' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Moves a document to the trash
     *
     * @param int $documentId
     * @param int $userId
     * @return bool True if the document was trashed successfully, false otherwise
     */
    public function trashDocument(int $documentId, int $userId): bool
    {
        try {
            // Find the document
            $document = Document::find($documentId);
            
            if (!$document) {
                Log::error("Document not found for trashing", ['documentId' => $documentId]);
                return false;
            }
            
            // Begin transaction
            DB::beginTransaction();
            
            // Move to trash
            $success = $document->moveToTrash();
            
            if (!$success) {
                // If update failed, rollback and return false
                DB::rollBack();
                return false;
            }
            
            // Log the trash action
            $this->auditLogger->logDocumentTrash($documentId, $userId);
            
            // Commit transaction
            DB::commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Log::error("Error trashing document: " . $e->getMessage(), [
                'documentId' => $documentId,
                'userId' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Restores a document from the trash
     *
     * @param int $documentId
     * @param int $userId
     * @return ?\App\Models\Document Restored document or null if restoration failed
     */
    public function restoreDocument(int $documentId, int $userId): ?Document
    {
        try {
            // Find the document including trashed ones
            $document = Document::withTrashed()->find($documentId);
            
            if (!$document) {
                Log::error("Document not found for restoration", ['documentId' => $documentId]);
                return null;
            }
            
            // Begin transaction
            DB::beginTransaction();
            
            // Restore from trash
            $success = $document->restore();
            
            if (!$success) {
                // If restoration failed, rollback and return null
                DB::rollBack();
                return null;
            }
            
            // Log the restore action
            $this->auditLogger->logDocumentRestore($documentId, $userId);
            
            // Commit transaction
            DB::commit();
            
            // Reload the document to get the updated state
            $document->refresh();
            
            return $document;
        } catch (Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Log::error("Error restoring document: " . $e->getMessage(), [
                'documentId' => $documentId,
                'userId' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Retrieves the file associated with a document
     *
     * @param int $documentId
     * @return ?string File path or null if not found
     */
    public function getDocumentFile(int $documentId): ?string
    {
        try {
            // Find the document
            $document = Document::find($documentId);
            
            if (!$document) {
                return null;
            }
            
            // Get the main file
            $mainFile = $document->main_file;
            
            if (!$mainFile) {
                return null;
            }
            
            // Get the file from storage
            return $this->fileStorage->getFile($mainFile->id);
        } catch (Exception $e) {
            Log::error("Error retrieving document file: " . $e->getMessage(), [
                'documentId' => $documentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Generates a URL for accessing a document file
     *
     * @param int $documentId
     * @param int $expirationMinutes
     * @return ?string File URL or null if not found
     */
    public function getDocumentFileUrl(int $documentId, int $expirationMinutes = 60): ?string
    {
        try {
            // Find the document
            $document = Document::find($documentId);
            
            if (!$document) {
                return null;
            }
            
            // Get the main file
            $mainFile = $document->main_file;
            
            if (!$mainFile) {
                return null;
            }
            
            // Generate URL using FileStorage
            return $this->fileStorage->getFileUrl($mainFile->id, $expirationMinutes);
        } catch (Exception $e) {
            Log::error("Error generating document file URL: " . $e->getMessage(), [
                'documentId' => $documentId,
                'expirationMinutes' => $expirationMinutes,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Generates a URL for viewing a document in the PDF viewer
     *
     * @param int $documentId
     * @param int $expirationMinutes
     * @return ?string Viewer URL or null if not found
     */
    public function getDocumentViewerUrl(int $documentId, int $expirationMinutes = 60): ?string
    {
        try {
            // Find the document
            $document = Document::find($documentId);
            
            if (!$document) {
                return null;
            }
            
            // Get the main file
            $mainFile = $document->main_file;
            
            if (!$mainFile) {
                return null;
            }
            
            // Generate viewer URL using PdfViewerService
            return $this->pdfViewerService->getDocumentViewUrl($mainFile->id, $expirationMinutes);
        } catch (Exception $e) {
            Log::error("Error generating document viewer URL: " . $e->getMessage(), [
                'documentId' => $documentId,
                'expirationMinutes' => $expirationMinutes,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Retrieves the configuration for the PDF viewer
     *
     * @param int $documentId
     * @return ?array Viewer configuration or null if document not found
     */
    public function getDocumentViewerConfig(int $documentId): ?array
    {
        try {
            // Find the document
            $document = Document::find($documentId);
            
            if (!$document) {
                return null;
            }
            
            // Get the main file
            $mainFile = $document->main_file;
            
            if (!$mainFile) {
                return null;
            }
            
            // Get the viewer configuration from PdfViewerService
            $config = $this->pdfViewerService->getViewerConfig($mainFile->id);
            
            // Add document-specific configuration
            $config['documentId'] = $documentId;
            $config['documentName'] = $document->name;
            $config['isProcessed'] = $document->is_processed;
            
            return $config;
        } catch (Exception $e) {
            Log::error("Error retrieving document viewer configuration: " . $e->getMessage(), [
                'documentId' => $documentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Retrieves the history of actions performed on a document
     *
     * @param int $documentId
     * @param int $perPage
     * @param string $direction
     * @return ?\Illuminate\Pagination\LengthAwarePaginator Paginated list of document actions or null if document not found
     */
    public function getDocumentHistory(int $documentId, int $perPage = 10, string $direction = 'desc')
    {
        try {
            // Check if the document exists
            $document = Document::find($documentId);
            
            if (!$document) {
                return null;
            }
            
            // Get document history from AuditLogger
            return $this->auditLogger->getDocumentHistory($documentId, $perPage, $direction);
        } catch (Exception $e) {
            Log::error("Error retrieving document history: " . $e->getMessage(), [
                'documentId' => $documentId,
                'perPage' => $perPage,
                'direction' => $direction,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Logs a document view action
     *
     * @param int $documentId
     * @param int $userId
     * @return bool True if the action was logged successfully, false otherwise
     */
    public function logDocumentView(int $documentId, int $userId): bool
    {
        try {
            // Log the view action using AuditLogger
            return $this->auditLogger->logDocumentView($documentId, $userId);
        } catch (Exception $e) {
            Log::error("Error logging document view: " . $e->getMessage(), [
                'documentId' => $documentId,
                'userId' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Validates if a user has access to a document
     *
     * @param int $documentId
     * @param int $userId
     * @return bool True if the user has access, false otherwise
     */
    public function validateDocumentAccess(int $documentId, int $userId): bool
    {
        try {
            // Find the document
            $document = Document::find($documentId);
            
            if (!$document) {
                return false;
            }
            
            // Check if the user is assigned to the document
            $isAssignedToUser = $document->users()->where('user.id', $userId)->exists();
            
            // Check if the user belongs to any group assigned to the document
            $isInAssignedGroup = false;
            
            // Get the user's group ID
            $user = \App\Models\User::find($userId);
            if ($user && $user->user_group_id) {
                $isInAssignedGroup = $document->userGroups()->where('user_group.id', $user->user_group_id)->exists();
            }
            
            // Check if the user created or last updated the document
            $isCreatorOrUpdater = ($document->created_by == $userId || $document->updated_by == $userId);
            
            // Check if the user is an admin (has elevated privileges)
            $isAdmin = false;
            if ($user) {
                $isAdmin = $user->isAdmin() || $user->isManager();
            }
            
            return $isAssignedToUser || $isInAssignedGroup || $isCreatorOrUpdater || $isAdmin;
        } catch (Exception $e) {
            Log::error("Error validating document access: " . $e->getMessage(), [
                'documentId' => $documentId,
                'userId' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Checks if a document exists
     *
     * @param int $documentId
     * @return bool True if the document exists, false otherwise
     */
    public function checkDocumentExists(int $documentId): bool
    {
        try {
            return Document::where('id', $documentId)->exists();
        } catch (Exception $e) {
            Log::error("Error checking document existence: " . $e->getMessage(), [
                'documentId' => $documentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Checks if a document is marked as processed
     *
     * @param int $documentId
     * @return bool True if the document is processed, false otherwise
     */
    public function isDocumentProcessed(int $documentId): bool
    {
        try {
            $document = Document::find($documentId);
            
            if (!$document) {
                return false;
            }
            
            return $document->is_processed;
        } catch (Exception $e) {
            Log::error("Error checking document processed state: " . $e->getMessage(), [
                'documentId' => $documentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Checks if a document is in the trash
     *
     * @param int $documentId
     * @return bool True if the document is trashed, false otherwise
     */
    public function isDocumentTrashed(int $documentId): bool
    {
        try {
            $document = Document::withTrashed()->find($documentId);
            
            if (!$document) {
                return false;
            }
            
            return $document->is_trashed;
        } catch (Exception $e) {
            Log::error("Error checking document trashed state: " . $e->getMessage(), [
                'documentId' => $documentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Applies filters to a document query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder with filters applied
     */
    public function applyDocumentFilters($query, array $filters)
    {
        // Apply status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            if ($filters['status'] === 'processed') {
                $query->processed();
            } elseif ($filters['status'] === 'unprocessed') {
                $query->unprocessed();
            } elseif ($filters['status'] === 'trashed') {
                $query->trashed();
            }
        }
        
        // Apply policy filter
        if (isset($filters['policy_id']) && !empty($filters['policy_id'])) {
            $query->where('policy_id', $filters['policy_id']);
        }
        
        // Apply loss filter
        if (isset($filters['loss_id']) && !empty($filters['loss_id'])) {
            $query->where('loss_id', $filters['loss_id']);
        }
        
        // Apply claimant filter
        if (isset($filters['claimant_id']) && !empty($filters['claimant_id'])) {
            $query->where('claimant_id', $filters['claimant_id']);
        }
        
        // Apply producer filter
        if (isset($filters['producer_id']) && !empty($filters['producer_id'])) {
            $query->where('producer_id', $filters['producer_id']);
        }
        
        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->search($filters['search']);
        }
        
        // Apply date range filters
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->where('date_received', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->where('date_received', '<=', $filters['date_to']);
        }
        
        // Apply user assignment filter
        if (isset($filters['assigned_to_user']) && !empty($filters['assigned_to_user'])) {
            $query->assignedToUser($filters['assigned_to_user']);
        }
        
        // Apply group assignment filter
        if (isset($filters['assigned_to_group']) && !empty($filters['assigned_to_group'])) {
            $query->assignedToGroup($filters['assigned_to_group']);
        }
        
        // Apply created by filter
        if (isset($filters['created_by']) && !empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }
        
        // Apply updated by filter
        if (isset($filters['updated_by']) && !empty($filters['updated_by'])) {
            $query->where('updated_by', $filters['updated_by']);
        }
        
        return $query;
    }
}