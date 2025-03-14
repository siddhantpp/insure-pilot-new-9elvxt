<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Policy;
use App\Models\Loss;
use App\Models\Claimant;
use App\Models\Producer;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Exception; // 8.2

/**
 * Service responsible for managing document metadata in the Insure Pilot system.
 * This service handles retrieval, updates, and validation of document metadata,
 * as well as providing dropdown options for the Documents View feature's metadata panel.
 */
class MetadataService
{
    /**
     * The AuditLogger instance for logging document changes.
     *
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * Creates a new MetadataService instance
     *
     * @param AuditLogger $auditLogger
     */
    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Retrieves metadata for a specific document
     *
     * @param int $documentId
     * @return ?array Document metadata or null if document not found
     */
    public function getDocumentMetadata(int $documentId): ?array
    {
        try {
            // Find the document by ID
            $document = Document::find($documentId);
            
            // Return null if document not found
            if (!$document) {
                Log::error("MetadataService: Document not found", ['document_id' => $documentId]);
                return null;
            }
            
            // Load relationships needed for metadata
            $document->load([
                'policy',
                'loss',
                'claimant',
                'producer',
                'users',
                'userGroups',
                'files'
            ]);
            
            // Format metadata into a structured array
            $metadata = [
                'id' => $document->id,
                'name' => $document->name,
                'description' => $document->description,
                'date_received' => $document->date_received ? $document->date_received->format('Y-m-d') : null,
                'policy_id' => $document->policy_id,
                'policy_number' => $document->policy_number,
                'loss_id' => $document->loss_id,
                'loss_sequence' => $document->loss_sequence,
                'claimant_id' => $document->claimant_id,
                'claimant_name' => $document->claimant_name,
                'producer_id' => $document->producer_id,
                'producer_number' => $document->producer_number,
                'assigned_to' => $document->assigned_to,
                'status_id' => $document->status_id,
                'is_processed' => $document->is_processed,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
                'created_by' => $document->created_by,
                'updated_by' => $document->updated_by,
                'assigned_users' => $document->users->pluck('id')->toArray(),
                'assigned_groups' => $document->userGroups->pluck('id')->toArray(),
                'file_url' => $document->file_url,
                'filename' => $document->main_file ? $document->main_file->name : null
            ];
            
            return $metadata;
        } catch (Exception $e) {
            Log::error("MetadataService: Error retrieving document metadata", [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Updates metadata for a specific document
     *
     * @param int $documentId
     * @param array $data
     * @param int $userId
     * @return array|bool Updated document metadata or false if update fails
     */
    public function updateDocumentMetadata(int $documentId, array $data, int $userId)
    {
        try {
            // Find the document by ID
            $document = Document::find($documentId);
            
            // Return false if document not found
            if (!$document) {
                Log::error("MetadataService: Document not found for update", ['document_id' => $documentId]);
                return false;
            }
            
            // Check if document is processed - if so, reject updates
            if ($document->is_processed) {
                Log::warning("MetadataService: Attempted to update processed document", [
                    'document_id' => $documentId,
                    'user_id' => $userId
                ]);
                return false;
            }
            
            // Start database transaction for atomic updates
            DB::beginTransaction();
            
            // Track changes for audit logging
            $changes = $this->trackChanges($document, $data);
            
            // Update document attributes from data array
            if (isset($data['policy_id'])) {
                $document->policy_id = $data['policy_id'] ?: null;
            }
            
            if (isset($data['loss_id'])) {
                $document->loss_id = $data['loss_id'] ?: null;
            }
            
            if (isset($data['claimant_id'])) {
                $document->claimant_id = $data['claimant_id'] ?: null;
            }
            
            if (isset($data['producer_id'])) {
                $document->producer_id = $data['producer_id'] ?: null;
            }
            
            if (isset($data['description'])) {
                $document->description = $data['description'];
            }
            
            // Update document relationships
            $this->updateDocumentRelationships($document, $data);
            
            // Set the user who updated the document
            $document->updated_by = $userId;
            
            // Save the document
            $document->save();
            
            // Log document edit action with changes
            if (!empty($changes)) {
                $this->auditLogger->logDocumentEdit($documentId, $userId, $changes);
            }
            
            // Commit transaction
            DB::commit();
            
            // Return updated document metadata
            return $this->getDocumentMetadata($documentId);
        } catch (Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Log::error("MetadataService: Error updating document metadata", [
                'document_id' => $documentId,
                'user_id' => $userId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieves policy options for dropdown selection
     *
     * @param ?string $search
     * @param ?int $producerId
     * @param int $limit
     * @return array Array of policy options formatted for dropdown
     */
    public function getPolicyOptions(?string $search = null, ?int $producerId = null, int $limit = 25): array
    {
        try {
            // Start with active policies query
            $query = Policy::active();
            
            // If producerId provided, filter policies by producer
            if ($producerId) {
                $query->forProducer($producerId);
            }
            
            // If search term provided, filter policies by search term
            if ($search) {
                $query->search($search);
            }
            
            // Get policies with limit
            $policies = $query->limit($limit)->get();
            
            // Format results for dropdown with id, value, and label
            return $this->formatDropdownOptions($policies, 'display_name');
        } catch (Exception $e) {
            Log::error("MetadataService: Error retrieving policy options", [
                'search' => $search,
                'producer_id' => $producerId,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * Retrieves loss options for a specific policy
     *
     * @param int $policyId
     * @param ?string $search
     * @param int $limit
     * @return array Array of loss options formatted for dropdown
     */
    public function getLossOptions(int $policyId, ?string $search = null, int $limit = 25): array
    {
        try {
            // Start with active losses query
            $query = Loss::active();
            
            // Filter losses by policy ID
            $query->forPolicy($policyId);
            
            // If search term provided, filter losses by search term
            if ($search) {
                $query->search($search);
            }
            
            // Order losses chronologically (newest first)
            $query->chronological('desc');
            
            // Get losses with limit
            $losses = $query->limit($limit)->get();
            
            // Format results for dropdown with id, value, and label
            return $this->formatDropdownOptions($losses, 'display_name');
        } catch (Exception $e) {
            Log::error("MetadataService: Error retrieving loss options", [
                'policy_id' => $policyId,
                'search' => $search,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * Retrieves claimant options for a specific loss
     *
     * @param int $lossId
     * @param ?string $search
     * @param int $limit
     * @return array Array of claimant options formatted for dropdown
     */
    public function getClaimantOptions(int $lossId, ?string $search = null, int $limit = 25): array
    {
        try {
            // Start with active claimants query
            $query = Claimant::active();
            
            // Filter claimants by loss ID
            $query->forLoss($lossId);
            
            // If search term provided, filter claimants by search term
            if ($search) {
                $query->search($search);
            }
            
            // Get claimants with limit
            $claimants = $query->limit($limit)->get();
            
            // Format results for dropdown with id, value, and label
            return $this->formatDropdownOptions($claimants, 'display_name');
        } catch (Exception $e) {
            Log::error("MetadataService: Error retrieving claimant options", [
                'loss_id' => $lossId,
                'search' => $search,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * Retrieves producer options for dropdown selection
     *
     * @param ?string $search
     * @param int $limit
     * @return array Array of producer options formatted for dropdown
     */
    public function getProducerOptions(?string $search = null, int $limit = 25): array
    {
        try {
            // Start with active producers query
            $query = Producer::active();
            
            // If search term provided, filter producers by search term
            if ($search) {
                $query->search($search);
            }
            
            // Get producers with limit
            $producers = $query->limit($limit)->get();
            
            // Format results for dropdown with id, value, and label
            return $this->formatDropdownOptions($producers, 'display_name');
        } catch (Exception $e) {
            Log::error("MetadataService: Error retrieving producer options", [
                'search' => $search,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * Retrieves user options for assignment dropdown
     *
     * @param ?string $search
     * @param int $limit
     * @return array Array of user options formatted for dropdown
     */
    public function getUserOptions(?string $search = null, int $limit = 25): array
    {
        try {
            // Start with active users query
            $query = User::active();
            
            // If search term provided, filter users by search term
            if ($search) {
                $query->search($search);
            }
            
            // Get users with limit
            $users = $query->limit($limit)->get();
            
            // Format results for dropdown with id, value, and label
            return $this->formatDropdownOptions($users, 'full_name');
        } catch (Exception $e) {
            Log::error("MetadataService: Error retrieving user options", [
                'search' => $search,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * Retrieves user group options for assignment dropdown
     *
     * @param ?string $search
     * @param int $limit
     * @return array Array of user group options formatted for dropdown
     */
    public function getUserGroupOptions(?string $search = null, int $limit = 25): array
    {
        try {
            // Start with active user groups query
            $query = UserGroup::active();
            
            // If search term provided, filter user groups by search term
            if ($search) {
                $query->search($search);
            }
            
            // Get user groups with limit
            $userGroups = $query->limit($limit)->get();
            
            // Format results for dropdown with id, value, and label
            return $this->formatDropdownOptions($userGroups, 'name');
        } catch (Exception $e) {
            Log::error("MetadataService: Error retrieving user group options", [
                'search' => $search,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * Validates relationships between metadata fields
     *
     * @param array $data
     * @return array Array of validation errors, empty if valid
     */
    public function validateMetadataRelationships(array $data): array
    {
        $errors = [];
        
        // Check if loss_id belongs to policy_id when both are present
        if (isset($data['policy_id'], $data['loss_id']) && $data['policy_id'] && $data['loss_id']) {
            // Check if this loss belongs to this policy
            $lossExists = DB::table('map_policy_loss')
                ->where('policy_id', $data['policy_id'])
                ->where('loss_id', $data['loss_id'])
                ->exists();
            
            if (!$lossExists) {
                $errors['loss_id'] = 'The selected loss does not belong to the selected policy.';
            }
        }
        
        // Check if claimant_id belongs to loss_id when both are present
        if (isset($data['loss_id'], $data['claimant_id']) && $data['loss_id'] && $data['claimant_id']) {
            // Check if this claimant belongs to this loss
            $claimantExists = DB::table('map_loss_claimant')
                ->where('loss_id', $data['loss_id'])
                ->where('claimant_id', $data['claimant_id'])
                ->exists();
            
            if (!$claimantExists) {
                $errors['claimant_id'] = 'The selected claimant does not belong to the selected loss.';
            }
        }
        
        return $errors;
    }

    /**
     * Formats query results into standardized dropdown options
     *
     * @param \Illuminate\Database\Eloquent\Collection $items
     * @param string $labelAttribute
     * @return array Formatted dropdown options
     */
    protected function formatDropdownOptions($items, string $labelAttribute): array
    {
        $options = [];
        
        foreach ($items as $item) {
            $options[] = [
                'id' => $item->id,
                'value' => $item->id,
                'label' => $item->{$labelAttribute}
            ];
        }
        
        return $options;
    }

    /**
     * Tracks changes to document metadata for audit logging
     *
     * @param \App\Models\Document $document
     * @param array $data
     * @return array Array of changes with old and new values
     */
    protected function trackChanges(Document $document, array $data): array
    {
        $changes = [];
        
        // Compare original document attributes with new data
        if (isset($data['policy_id']) && $document->policy_id != $data['policy_id']) {
            $oldPolicy = $document->policy_id ? Policy::find($document->policy_id) : null;
            $newPolicy = $data['policy_id'] ? Policy::find($data['policy_id']) : null;
            
            $changes['Policy Number'] = [
                $oldPolicy ? $oldPolicy->formatted_number : null,
                $newPolicy ? $newPolicy->formatted_number : null,
            ];
        }
        
        if (isset($data['loss_id']) && $document->loss_id != $data['loss_id']) {
            $oldLoss = $document->loss_id ? Loss::find($document->loss_id) : null;
            $newLoss = $data['loss_id'] ? Loss::find($data['loss_id']) : null;
            
            $changes['Loss Sequence'] = [
                $oldLoss ? $oldLoss->display_name : null,
                $newLoss ? $newLoss->display_name : null,
            ];
        }
        
        if (isset($data['claimant_id']) && $document->claimant_id != $data['claimant_id']) {
            $oldClaimant = $document->claimant_id ? Claimant::find($document->claimant_id) : null;
            $newClaimant = $data['claimant_id'] ? Claimant::find($data['claimant_id']) : null;
            
            $changes['Claimant'] = [
                $oldClaimant ? $oldClaimant->display_name : null,
                $newClaimant ? $newClaimant->display_name : null,
            ];
        }
        
        if (isset($data['producer_id']) && $document->producer_id != $data['producer_id']) {
            $oldProducer = $document->producer_id ? Producer::find($document->producer_id) : null;
            $newProducer = $data['producer_id'] ? Producer::find($data['producer_id']) : null;
            
            $changes['Producer Number'] = [
                $oldProducer ? $oldProducer->display_name : null,
                $newProducer ? $newProducer->display_name : null,
            ];
        }
        
        if (isset($data['description']) && $document->description != $data['description']) {
            $changes['Document Description'] = [
                $document->description,
                $data['description'],
            ];
        }
        
        // Handle assigned users changes
        if (isset($data['assigned_users'])) {
            $currentUsers = $document->users->pluck('id')->toArray();
            $newUsers = is_array($data['assigned_users']) ? $data['assigned_users'] : [];
            
            if (count(array_diff($currentUsers, $newUsers)) > 0 || count(array_diff($newUsers, $currentUsers)) > 0) {
                $changes['Assigned Users'] = [
                    implode(', ', $currentUsers),
                    implode(', ', $newUsers),
                ];
            }
        }
        
        // Handle assigned groups changes
        if (isset($data['assigned_groups'])) {
            $currentGroups = $document->userGroups->pluck('id')->toArray();
            $newGroups = is_array($data['assigned_groups']) ? $data['assigned_groups'] : [];
            
            if (count(array_diff($currentGroups, $newGroups)) > 0 || count(array_diff($newGroups, $currentGroups)) > 0) {
                $changes['Assigned Groups'] = [
                    implode(', ', $currentGroups),
                    implode(', ', $newGroups),
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Updates document relationships based on metadata changes
     *
     * @param \App\Models\Document $document
     * @param array $data
     * @return bool True if successful, false otherwise
     */
    protected function updateDocumentRelationships(Document $document, array $data): bool
    {
        try {
            // Update user assignments if assigned_users is present
            if (isset($data['assigned_users'])) {
                // Convert to array if it's not already
                $userIds = is_array($data['assigned_users']) ? $data['assigned_users'] : [];
                
                // Filter out any non-numeric values
                $userIds = array_filter($userIds, 'is_numeric');
                
                // Sync the users relationship with the provided user IDs
                $document->users()->sync($userIds);
            }
            
            // Update user group assignments if assigned_groups is present
            if (isset($data['assigned_groups'])) {
                // Convert to array if it's not already
                $groupIds = is_array($data['assigned_groups']) ? $data['assigned_groups'] : [];
                
                // Filter out any non-numeric values
                $groupIds = array_filter($groupIds, 'is_numeric');
                
                // Sync the userGroups relationship with the provided group IDs
                $document->userGroups()->sync($groupIds);
            }
            
            return true;
        } catch (Exception $e) {
            Log::error("MetadataService: Error updating document relationships", [
                'document_id' => $document->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
}