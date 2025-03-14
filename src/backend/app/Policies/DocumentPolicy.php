<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Document;
use Illuminate\Support\Facades\Gate; // ^10.0

/**
 * Policy class that defines authorization rules for document-related operations
 * based on user roles and document state.
 */
class DocumentPolicy
{
    /**
     * Performs preliminary authorization checks before specific policy methods
     *
     * @param  \App\Models\User|null  $user
     * @param  string  $ability
     * @return bool|null True to grant access, false to deny access, or null to fall through to the specific policy method
     */
    public function before(?User $user, $ability)
    {
        // Unauthenticated users have no access
        if ($user === null) {
            return false;
        }
        
        // Administrators can do everything
        if ($user->isAdmin()) {
            return true;
        }
        
        // Fall through to specific policy methods for non-admin users
        return null;
    }

    /**
     * Determines if the user can view any documents
     *
     * @param  \App\Models\User  $user
     * @return bool True if the user can view any documents, false otherwise
     */
    public function viewAny(User $user)
    {
        // All authenticated users can view documents
        return true;
    }

    /**
     * Determines if the user can view a specific document
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can view the document, false otherwise
     */
    public function view(User $user, Document $document)
    {
        // Check if document is trashed and user is not an admin or manager
        if ($document->getIsTrashedAttribute() && !$this->canAccessTrashed($user)) {
            return false;
        }
        
        // All authenticated users can view non-trashed documents
        return true;
    }

    /**
     * Determines if the user can create documents
     *
     * @param  \App\Models\User  $user
     * @return bool True if the user can create documents, false otherwise
     */
    public function create(User $user)
    {
        // Admins, managers, adjusters, underwriters, and support staff can create documents
        // Read-only users cannot create documents
        return $user->isAdmin() || $user->isManager() || $user->isAdjuster() || 
               $user->isUnderwriter() || $user->isSupport();
    }

    /**
     * Determines if the user can update a document's metadata
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can update the document, false otherwise
     */
    public function update(User $user, Document $document)
    {
        // Cannot update trashed documents
        if ($document->getIsTrashedAttribute()) {
            return false;
        }
        
        // Check if document is processed and user cannot override locks
        if ($document->getIsProcessedAttribute() && !$this->overrideLock($user, $document)) {
            return false;
        }
        
        // Admins, managers, adjusters, underwriters, and support staff can update
        // Read-only users cannot update documents
        return $user->isAdmin() || $user->isManager() || $user->isAdjuster() || 
               $user->isUnderwriter() || $user->isSupport();
    }

    /**
     * Determines if the user can delete a document
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can delete the document, false otherwise
     */
    public function delete(User $user, Document $document)
    {
        // Only administrators can permanently delete documents
        return $user->isAdmin();
    }

    /**
     * Determines if the user can restore a trashed document
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can restore the document, false otherwise
     */
    public function restore(User $user, Document $document)
    {
        // Only administrators and managers can restore trashed documents
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determines if the user can force delete a document
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can force delete the document, false otherwise
     */
    public function forceDelete(User $user, Document $document)
    {
        // Only administrators can force delete documents
        return $user->isAdmin();
    }

    /**
     * Determines if the user can mark a document as processed or unprocessed
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can process the document, false otherwise
     */
    public function process(User $user, Document $document)
    {
        // Cannot process trashed documents
        if ($document->getIsTrashedAttribute()) {
            return false;
        }
        
        // Check if document is processed and user cannot override locks
        if ($document->getIsProcessedAttribute() && !$this->overrideLock($user, $document)) {
            return false;
        }
        
        // Admins, managers, adjusters, and underwriters can process documents
        // Support staff and read-only users cannot process documents
        return $user->isAdmin() || $user->isManager() || $user->isAdjuster() || $user->isUnderwriter();
    }

    /**
     * Determines if the user can move a document to trash
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can trash the document, false otherwise
     */
    public function trash(User $user, Document $document)
    {
        // Cannot trash a document that's already trashed
        if ($document->getIsTrashedAttribute()) {
            return false;
        }
        
        // Only administrators and managers can trash documents
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determines if the user can view a document's history
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can view the document's history, false otherwise
     */
    public function viewHistory(User $user, Document $document)
    {
        // User can view history if they can view the document
        return $this->view($user, $document);
    }

    /**
     * Determines if the user can override a document's processed state lock
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return bool True if the user can override the lock, false otherwise
     */
    public function overrideLock(User $user, Document $document)
    {
        // Only administrators and managers can override document locks
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determines if the user can access trashed documents
     *
     * @param  \App\Models\User  $user
     * @return bool True if the user can access trashed documents, false otherwise
     */
    public function canAccessTrashed(User $user)
    {
        // Only administrators and managers can access trashed documents
        return $user->isAdmin() || $user->isManager();
    }
}