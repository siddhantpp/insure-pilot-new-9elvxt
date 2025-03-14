<?php

namespace App\Services;

use App\Models\Action;
use App\Models\ActionType;
use App\Models\Document;
use App\Models\MapDocumentAction;
use App\Models\User;
use Illuminate\Support\Facades\DB; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Exception; // 8.2

/**
 * Service responsible for logging document actions to the audit trail in the Insure Pilot system.
 * This service is a critical component of the Document History & Audit Trail feature,
 * enabling comprehensive tracking of all document-related activities with user attribution
 * for compliance and accountability purposes.
 */
class AuditLogger
{
    /**
     * Action type constant for document view actions
     */
    public const ACTION_VIEW = 'view';
    
    /**
     * Action type constant for document edit actions
     */
    public const ACTION_EDIT = 'edit';
    
    /**
     * Action type constant for document process actions
     */
    public const ACTION_PROCESS = 'process';
    
    /**
     * Action type constant for document unprocess actions
     */
    public const ACTION_UNPROCESS = 'unprocess';
    
    /**
     * Action type constant for document trash actions
     */
    public const ACTION_TRASH = 'trash';
    
    /**
     * Action type constant for document restore actions
     */
    public const ACTION_RESTORE = 'restore';
    
    /**
     * Creates a new AuditLogger service instance
     */
    public function __construct()
    {
        // No initialization needed
    }
    
    /**
     * Logs a document view action when a user views a document
     *
     * @param int $documentId ID of the document being viewed
     * @param int $userId ID of the user viewing the document
     * @return bool True if the action was logged successfully, false otherwise
     */
    public function logDocumentView(int $documentId, int $userId): bool
    {
        try {
            // Validate that the document exists
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("AuditLogger: Failed to log view action - Document ID {$documentId} not found");
                return false;
            }
            
            // Get the 'view' action type from the database
            $actionTypeId = $this->getActionTypeId(self::ACTION_VIEW);
            if (!$actionTypeId) {
                Log::error("AuditLogger: Failed to log view action - Action type '".self::ACTION_VIEW."' not found");
                return false;
            }
            
            // Create a new action record with the action type and description
            $description = "Document viewed";
            return $this->createActionRecord($documentId, $userId, $actionTypeId, $description);
        } catch (Exception $e) {
            Log::error("AuditLogger: Error logging document view action", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Logs a document edit action when a user updates document metadata
     *
     * @param int $documentId ID of the document being edited
     * @param int $userId ID of the user editing the document
     * @param array $changes Array of field changes with old and new values
     * @return bool True if the action was logged successfully, false otherwise
     */
    public function logDocumentEdit(int $documentId, int $userId, array $changes): bool
    {
        try {
            // Validate that the document exists
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("AuditLogger: Failed to log edit action - Document ID {$documentId} not found");
                return false;
            }
            
            // Get the 'edit' action type from the database
            $actionTypeId = $this->getActionTypeId(self::ACTION_EDIT);
            if (!$actionTypeId) {
                Log::error("AuditLogger: Failed to log edit action - Action type '".self::ACTION_EDIT."' not found");
                return false;
            }
            
            // Format the changes array into a readable description
            $description = $this->formatChangesDescription($changes);
            
            // Create a new action record with the action type and description
            return $this->createActionRecord($documentId, $userId, $actionTypeId, $description);
        } catch (Exception $e) {
            Log::error("AuditLogger: Error logging document edit action", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'user_id' => $userId,
                'changes' => $changes
            ]);
            return false;
        }
    }
    
    /**
     * Logs a document process action when a user marks a document as processed
     *
     * @param int $documentId ID of the document being processed
     * @param int $userId ID of the user marking the document as processed
     * @return bool True if the action was logged successfully, false otherwise
     */
    public function logDocumentProcess(int $documentId, int $userId): bool
    {
        try {
            // Validate that the document exists
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("AuditLogger: Failed to log process action - Document ID {$documentId} not found");
                return false;
            }
            
            // Get the 'process' action type from the database
            $actionTypeId = $this->getActionTypeId(self::ACTION_PROCESS);
            if (!$actionTypeId) {
                Log::error("AuditLogger: Failed to log process action - Action type '".self::ACTION_PROCESS."' not found");
                return false;
            }
            
            // Create a new action record with the action type and description
            $description = "Marked as processed";
            return $this->createActionRecord($documentId, $userId, $actionTypeId, $description);
        } catch (Exception $e) {
            Log::error("AuditLogger: Error logging document process action", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Logs a document unprocess action when a user marks a document as unprocessed
     *
     * @param int $documentId ID of the document being unprocessed
     * @param int $userId ID of the user marking the document as unprocessed
     * @return bool True if the action was logged successfully, false otherwise
     */
    public function logDocumentUnprocess(int $documentId, int $userId): bool
    {
        try {
            // Validate that the document exists
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("AuditLogger: Failed to log unprocess action - Document ID {$documentId} not found");
                return false;
            }
            
            // Get the 'unprocess' action type from the database
            $actionTypeId = $this->getActionTypeId(self::ACTION_UNPROCESS);
            if (!$actionTypeId) {
                Log::error("AuditLogger: Failed to log unprocess action - Action type '".self::ACTION_UNPROCESS."' not found");
                return false;
            }
            
            // Create a new action record with the action type and description
            $description = "Marked as unprocessed";
            return $this->createActionRecord($documentId, $userId, $actionTypeId, $description);
        } catch (Exception $e) {
            Log::error("AuditLogger: Error logging document unprocess action", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Logs a document trash action when a user moves a document to trash
     *
     * @param int $documentId ID of the document being trashed
     * @param int $userId ID of the user trashing the document
     * @return bool True if the action was logged successfully, false otherwise
     */
    public function logDocumentTrash(int $documentId, int $userId): bool
    {
        try {
            // Validate that the document exists
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("AuditLogger: Failed to log trash action - Document ID {$documentId} not found");
                return false;
            }
            
            // Get the 'trash' action type from the database
            $actionTypeId = $this->getActionTypeId(self::ACTION_TRASH);
            if (!$actionTypeId) {
                Log::error("AuditLogger: Failed to log trash action - Action type '".self::ACTION_TRASH."' not found");
                return false;
            }
            
            // Create a new action record with the action type and description
            $description = "Moved to trash";
            return $this->createActionRecord($documentId, $userId, $actionTypeId, $description);
        } catch (Exception $e) {
            Log::error("AuditLogger: Error logging document trash action", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Logs a document restore action when a user restores a document from trash
     *
     * @param int $documentId ID of the document being restored
     * @param int $userId ID of the user restoring the document
     * @return bool True if the action was logged successfully, false otherwise
     */
    public function logDocumentRestore(int $documentId, int $userId): bool
    {
        try {
            // Validate that the document exists
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("AuditLogger: Failed to log restore action - Document ID {$documentId} not found");
                return false;
            }
            
            // Get the 'restore' action type from the database
            $actionTypeId = $this->getActionTypeId(self::ACTION_RESTORE);
            if (!$actionTypeId) {
                Log::error("AuditLogger: Failed to log restore action - Action type '".self::ACTION_RESTORE."' not found");
                return false;
            }
            
            // Create a new action record with the action type and description
            $description = "Restored from trash";
            return $this->createActionRecord($documentId, $userId, $actionTypeId, $description);
        } catch (Exception $e) {
            Log::error("AuditLogger: Error logging document restore action", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Generic method to log any document action with a custom action type and description
     *
     * @param int $documentId ID of the document
     * @param int $userId ID of the user performing the action
     * @param string $actionType Type of action being performed
     * @param string $description Description of the action
     * @return bool True if the action was logged successfully, false otherwise
     */
    public function logDocumentAction(int $documentId, int $userId, string $actionType, string $description): bool
    {
        try {
            // Validate that the document exists
            $document = Document::find($documentId);
            if (!$document) {
                Log::error("AuditLogger: Failed to log custom action - Document ID {$documentId} not found");
                return false;
            }
            
            // Get the specified action type from the database
            $actionTypeId = $this->getActionTypeId($actionType);
            if (!$actionTypeId) {
                Log::error("AuditLogger: Failed to log custom action - Action type '{$actionType}' not found");
                return false;
            }
            
            // Create a new action record with the action type and description
            return $this->createActionRecord($documentId, $userId, $actionTypeId, $description);
        } catch (Exception $e) {
            Log::error("AuditLogger: Error logging custom document action", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'user_id' => $userId,
                'action_type' => $actionType,
                'description' => $description
            ]);
            return false;
        }
    }
    
    /**
     * Retrieves the history of actions performed on a document
     *
     * @param int $documentId ID of the document to retrieve history for
     * @param int $perPage Number of results per page for pagination
     * @param string $direction Sort direction for results ('asc' or 'desc')
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of document actions
     */
    public function getDocumentHistory(int $documentId, int $perPage = 10, string $direction = 'desc')
    {
        try {
            // Validate that the document exists
            if (!Document::find($documentId)) {
                Log::error("AuditLogger: Failed to get history - Document ID {$documentId} not found");
                return null;
            }
            
            // Query the map_document_action table for records related to the document
            return MapDocumentAction::forDocument($documentId)
                ->join('action', 'map_document_action.action_id', '=', 'action.id')
                ->join('action_type', 'action.action_type_id', '=', 'action_type.id')
                ->join('user', 'action.created_by', '=', 'user.id')
                ->select(
                    'map_document_action.*',
                    'action.description as action_description',
                    'action_type.name as action_type_name',
                    'user.username',
                    'user.first_name',
                    'user.last_name',
                    'action.created_at as action_timestamp'
                )
                ->orderBy('action.created_at', $direction)
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error("AuditLogger: Error retrieving document history", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'per_page' => $perPage,
                'direction' => $direction
            ]);
            return null;
        }
    }
    
    /**
     * Retrieves the most recent action performed on a document
     *
     * @param int $documentId ID of the document to retrieve last action for
     * @return ?array The last action data or null if no actions exist
     */
    public function getLastDocumentAction(int $documentId): ?array
    {
        try {
            // Validate that the document exists
            if (!Document::find($documentId)) {
                Log::error("AuditLogger: Failed to get last action - Document ID {$documentId} not found");
                return null;
            }
            
            // Query the map_document_action table for the most recent record related to the document
            $lastAction = MapDocumentAction::forDocument($documentId)
                ->join('action', 'map_document_action.action_id', '=', 'action.id')
                ->join('action_type', 'action.action_type_id', '=', 'action_type.id')
                ->join('user', 'action.created_by', '=', 'user.id')
                ->select(
                    'map_document_action.*',
                    'action.description as action_description',
                    'action_type.name as action_type_name',
                    'user.username',
                    'user.first_name',
                    'user.last_name',
                    'action.created_at as action_timestamp'
                )
                ->orderBy('action.created_at', 'desc')
                ->first();
            
            if (!$lastAction) {
                return null;
            }
            
            return [
                'id' => $lastAction->id,
                'action_type' => $lastAction->action_type_name,
                'description' => $lastAction->action_description,
                'timestamp' => $lastAction->action_timestamp,
                'user' => [
                    'id' => $lastAction->created_by,
                    'username' => $lastAction->username,
                    'name' => $lastAction->first_name . ' ' . $lastAction->last_name
                ]
            ];
        } catch (Exception $e) {
            Log::error("AuditLogger: Error retrieving last document action", [
                'error' => $e->getMessage(),
                'document_id' => $documentId
            ]);
            return null;
        }
    }
    
    /**
     * Retrieves the ID of an action type by name
     *
     * @param string $actionTypeName Name of the action type to look up
     * @return ?int The action type ID or null if not found
     */
    private function getActionTypeId(string $actionTypeName): ?int
    {
        try {
            // Query the action_type table for a record with the specified name
            $actionType = ActionType::byName($actionTypeName)->first();
            return $actionType ? $actionType->id : null;
        } catch (Exception $e) {
            Log::error("AuditLogger: Error getting action type ID", [
                'error' => $e->getMessage(),
                'action_type_name' => $actionTypeName
            ]);
            return null;
        }
    }
    
    /**
     * Formats an array of changes into a readable description string
     *
     * @param array $changes Array of field changes with old and new values
     * @return string Formatted description of the changes
     */
    private function formatChangesDescription(array $changes): string
    {
        // Initialize an empty array for change descriptions
        $descriptions = [];
        
        // For each change, format as 'Field changed from X to Y'
        foreach ($changes as $field => $values) {
            if (is_array($values) && count($values) === 2) {
                $oldValue = isset($values[0]) && $values[0] !== null ? $values[0] : '(empty)';
                $newValue = isset($values[1]) && $values[1] !== null ? $values[1] : '(empty)';
                $descriptions[] = "$field changed from '$oldValue' to '$newValue'";
            }
        }
        
        // Join the change descriptions with commas
        return empty($descriptions) ? "Document updated" : implode(', ', $descriptions);
    }
    
    /**
     * Creates a new action record and links it to a document
     *
     * @param int $documentId ID of the document
     * @param int $userId ID of the user performing the action
     * @param int $actionTypeId ID of the action type
     * @param string $description Description of the action
     * @return bool True if the action was created successfully, false otherwise
     */
    private function createActionRecord(int $documentId, int $userId, int $actionTypeId, string $description): bool
    {
        try {
            // Begin a database transaction
            DB::beginTransaction();
            
            // Create a new action record with the action type ID, description, and user ID
            $action = Action::create([
                'action_type_id' => $actionTypeId,
                'description' => $description,
                'status_id' => 1, // Assuming 1 is the active status ID
                'created_by' => $userId,
                'updated_by' => $userId
            ]);
            
            // Create a map_document_action record linking the action to the document
            MapDocumentAction::create([
                'document_id' => $documentId,
                'action_id' => $action->id,
                'description' => $description,
                'status_id' => 1, // Assuming 1 is the active status ID
                'created_by' => $userId,
                'updated_by' => $userId
            ]);
            
            // Commit the transaction
            DB::commit();
            return true;
        } catch (Exception $e) {
            // Roll back the transaction and return false if an error occurs
            DB::rollBack();
            Log::error("AuditLogger: Error creating action record", [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
                'user_id' => $userId,
                'action_type_id' => $actionTypeId,
                'description' => $description
            ]);
            return false;
        }
    }
}