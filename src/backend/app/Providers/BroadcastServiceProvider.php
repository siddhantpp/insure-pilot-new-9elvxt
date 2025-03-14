<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider; // ^10.0

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application's broadcasting services.
     * This enables WebSocket-based broadcasting for document-related events
     * such as updates, processing status changes, and notifications.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register the Broadcast routes with appropriate middleware
        // These routes are used by client libraries to authenticate with the WebSocket server
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        // Set up core channel authorization rules for document events
        
        // Document updates channel - allows users to receive real-time updates when document metadata changes
        Broadcast::channel('document.{id}', function ($user, $id) {
            // Check if the user is authorized to view this document
            return $user->can('view', \App\Models\Document::find($id));
        });
        
        // Document processing channel - notifies when a document is marked as processed
        Broadcast::channel('document.processed.{id}', function ($user, $id) {
            // Check if the user should receive processed document notifications
            return $user->can('view', \App\Models\Document::find($id));
        });
        
        // User document notifications channel - for personalized notifications about documents
        Broadcast::channel('user.documents.{userId}', function ($user, $userId) {
            // Users can only listen to their own document notification channel
            return (int) $user->id === (int) $userId;
        });
        
        // Load additional channel authentication logic from routes/channels.php
        // More specific document-related channel authorization rules are defined there
        require base_path('routes/channels.php');
    }
}