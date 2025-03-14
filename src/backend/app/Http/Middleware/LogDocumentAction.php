<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use App\Models\Document;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware that automatically logs document actions during HTTP requests.
 * This middleware intercepts requests related to document operations and creates
 * audit trail entries to track user interactions with documents, ensuring
 * comprehensive logging for compliance and accountability purposes.
 */
class LogDocumentAction
{
    /**
     * The audit logger service instance.
     *
     * @var \App\Services\AuditLogger
     */
    protected $auditLogger;

    /**
     * Creates a new LogDocumentAction middleware instance
     *
     * @param \App\Services\AuditLogger $auditLogger
     * @return void
     */
    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle an incoming request and log document actions
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Illuminate\Http\Response The response after processing the request
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check if this is a document-related request that should be logged
            if (!$this->shouldLogAction($request)) {
                return $next($request);
            }
            
            // Extract document ID from route parameters
            $documentId = $request->route('document');
            
            // If document ID is an object (model binding), get the ID
            if (is_object($documentId) && method_exists($documentId, 'getKey')) {
                $documentId = $documentId->getKey();
            }
            
            // Get the authenticated user ID
            $userId = $request->user() ? $request->user()->id : null;
            
            // If no document ID or no authenticated user, just continue
            if (!$documentId || !$userId) {
                return $next($request);
            }
            
            // Verify the document exists
            $document = Document::find($documentId);
            if (!$document) {
                return $next($request);
            }
            
            // For GET requests, log document view before processing
            if ($request->isMethod('GET')) {
                $this->auditLogger->logDocumentView($documentId, $userId);
            }
            
            // Process the request
            $response = $next($request);
            
            // For non-GET requests, log the appropriate action after processing
            if (!$request->isMethod('GET')) {
                $actionType = $this->determineActionType($request);
                
                switch ($actionType) {
                    case 'process':
                        $this->auditLogger->logDocumentProcess($documentId, $userId);
                        break;
                    case 'unprocess':
                        $this->auditLogger->logDocumentUnprocess($documentId, $userId);
                        break;
                    case 'trash':
                        $this->auditLogger->logDocumentTrash($documentId, $userId);
                        break;
                    case 'edit':
                        // Edit actions are typically logged in the controller with specific change details
                        // We don't log generic edits here to avoid duplicate logs
                        break;
                    default:
                        // For other actions, log a generic action
                        $description = 'Document action performed: ' . $actionType;
                        $this->auditLogger->logDocumentAction($documentId, $userId, $actionType, $description);
                        break;
                }
            }
            
            return $response;
        } catch (\Exception $e) {
            // Log the error but don't interrupt the request
            Log::error('Error in LogDocumentAction middleware: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->path()
            ]);
            
            // Continue with the request despite the logging error
            return $next($request);
        }
    }

    /**
     * Determine the type of action being performed based on the request method and path
     *
     * @param \Illuminate\Http\Request $request
     * @return string The action type identifier
     */
    private function determineActionType(Request $request)
    {
        $method = $request->method();
        $path = $request->path();
        
        if ($method === 'GET') {
            return 'view';
        } elseif ($method === 'POST') {
            if (strpos($path, 'process') !== false) {
                // Determine if processing or unprocessing based on request data
                return $request->input('processed', true) ? 'process' : 'unprocess';
            } elseif (strpos($path, 'trash') !== false) {
                return 'trash';
            }
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            return 'edit';
        } elseif ($method === 'DELETE') {
            return 'delete';
        }
        
        return 'other';
    }

    /**
     * Determine if the current request should trigger document action logging
     *
     * @param \Illuminate\Http\Request $request
     * @return bool True if the action should be logged, false otherwise
     */
    private function shouldLogAction(Request $request)
    {
        // Check if the request path includes 'documents' or 'api/documents'
        $path = $request->path();
        $isDocumentPath = strpos($path, 'documents') !== false || strpos($path, 'api/documents') !== false;
        
        // Check if the request has a document ID parameter
        $hasDocumentId = $request->route('document') !== null;
        
        return $isDocumentPath && $hasDocumentId;
    }
}