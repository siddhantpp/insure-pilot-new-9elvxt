<?php

namespace App\Http\Middleware;

use App\Models\Document;
use App\Models\User;
use App\Policies\DocumentPolicy;
use App\Services\DocumentManager;
use Closure; // php 8.2
use Illuminate\Http\Request; // laravel/framework ^10.0
use Illuminate\Http\Response; // laravel/framework ^10.0
use Illuminate\Support\Facades\Auth; // laravel/framework ^10.0
use Illuminate\Auth\Access\AuthorizationException; // laravel/framework ^10.0

/**
 * Middleware that validates user access to documents based on permissions, ownership, and relationships.
 * This middleware ensures that users can only access documents they are authorized to view according
 * to the role-based access control system and document-specific permissions.
 */
class ValidateDocumentAccess
{
    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * The DocumentPolicy instance.
     *
     * @var DocumentPolicy
     */
    protected $documentPolicy;

    /**
     * Constructor for the ValidateDocumentAccess middleware
     *
     * @param DocumentManager $documentManager
     * @param DocumentPolicy $documentPolicy
     */
    public function __construct(DocumentManager $documentManager, DocumentPolicy $documentPolicy)
    {
        $this->documentManager = $documentManager;
        $this->documentPolicy = $documentPolicy;
    }

    /**
     * Handle an incoming request and validate document access
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed The response from the next middleware or an authorization error
     * 
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if validation should be skipped for this route/request
        if ($this->shouldSkipValidation($request)) {
            return $next($request);
        }

        // Extract document ID from request
        $documentId = $this->getDocumentIdFromRequest($request);
        
        // If no document ID found, proceed to next middleware
        if (!$documentId) {
            return $next($request);
        }

        // Get currently authenticated user
        $user = Auth::user();
        
        // If no authenticated user, proceed to next middleware
        // (auth middleware will handle authentication requirements)
        if (!$user) {
            return $next($request);
        }

        // Get document from DocumentManager
        $document = $this->documentManager->getDocument($documentId);
        
        // If document not found, proceed to next middleware
        // (appropriate 404 responses will be handled elsewhere)
        if (!$document) {
            return $next($request);
        }

        // Check if user has permission to view this document
        if ($this->documentPolicy->view($user, $document)) {
            // Log the document view action
            $this->documentManager->logDocumentView($documentId, $user->id);
            
            // Proceed to next middleware
            return $next($request);
        }

        // User does not have permission to view this document
        throw new AuthorizationException('You do not have permission to access this document.');
    }

    /**
     * Extract the document ID from the request
     *
     * @param \Illuminate\Http\Request $request
     * @return ?int The document ID or null if not found
     */
    protected function getDocumentIdFromRequest(Request $request): ?int
    {
        // Check route parameters first
        if ($request->route('document')) {
            return (int) $request->route('document');
        }
        
        if ($request->route('document_id')) {
            return (int) $request->route('document_id');
        }
        
        // Check query parameters
        if ($request->query('document')) {
            return (int) $request->query('document');
        }
        
        if ($request->query('document_id')) {
            return (int) $request->query('document_id');
        }
        
        // If document ID is in the request body
        if ($request->has('document_id')) {
            return (int) $request->input('document_id');
        }
        
        // No document ID found
        return null;
    }

    /**
     * Determine if validation should be skipped for certain routes or request types
     *
     * @param \Illuminate\Http\Request $request
     * @return bool True if validation should be skipped, false otherwise
     */
    protected function shouldSkipValidation(Request $request): bool
    {
        // Skip validation for authentication routes
        if ($request->is('api/auth/*') || $request->is('login') || $request->is('register')) {
            return true;
        }
        
        // Skip validation for document listing routes (will be filtered at the controller level)
        if ($request->is('api/documents') && !$request->query('document') && !$request->query('document_id')) {
            return true;
        }
        
        // Skip validation for public routes
        if ($request->is('api/public/*')) {
            return true;
        }
        
        // Skip validation for document creation (will be checked at the controller level)
        if ($request->is('api/documents') && $request->isMethod('post')) {
            return true;
        }
        
        return false;
    }
}