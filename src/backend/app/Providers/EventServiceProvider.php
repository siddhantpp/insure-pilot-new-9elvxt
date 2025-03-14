<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\DocumentCreated;
use App\Events\DocumentUpdated;
use App\Events\DocumentProcessed;
use App\Events\DocumentTrashed;
use App\Listeners\LogDocumentAction;
use App\Listeners\SendDocumentNotification;
use App\Listeners\UpdateSearchIndex;

/**
 * Service provider responsible for registering event listeners and subscribers for the Documents View feature.
 * This provider maps document-related events to their corresponding listeners, enabling event-driven
 * architecture for document processing, audit logging, notifications, and search indexing.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        DocumentCreated::class => [
            LogDocumentAction::class.'@handleDocumentCreated',
            SendDocumentNotification::class.'@handleDocumentCreated',
            UpdateSearchIndex::class.'@handleDocumentCreated',
        ],
        DocumentUpdated::class => [
            LogDocumentAction::class.'@handleDocumentUpdated',
            SendDocumentNotification::class.'@handleDocumentUpdated',
            UpdateSearchIndex::class.'@handleDocumentUpdated',
        ],
        DocumentProcessed::class => [
            LogDocumentAction::class.'@handleDocumentProcessed',
            SendDocumentNotification::class.'@handleDocumentProcessed',
            UpdateSearchIndex::class.'@handleDocumentProcessed',
        ],
        DocumentTrashed::class => [
            LogDocumentAction::class.'@handleDocumentTrashed',
            SendDocumentNotification::class.'@handleDocumentTrashed',
            UpdateSearchIndex::class.'@handleDocumentTrashed',
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [];

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        // In production, it's better to explicitly define event listeners
        // rather than relying on auto-discovery for better performance and control
        return app()->environment('local');
    }

    /**
     * Bootstrap any application events after registration.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        
        // Additional event registration logic can be added here if needed
    }
}