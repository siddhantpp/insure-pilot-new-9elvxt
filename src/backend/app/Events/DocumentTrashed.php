<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets; // ^10.0
use Illuminate\Foundation\Events\Dispatchable; // ^10.0
use Illuminate\Queue\SerializesModels; // ^10.0

/**
 * Event class that is dispatched when a document is moved to trash in the system.
 * This event contains the Document model instance and the ID of the user who trashed it.
 * 
 * This event is part of the event-driven architecture for document processing,
 * enabling listeners to perform actions such as audit logging, search indexing,
 * and sending notifications when documents are trashed.
 */
class DocumentTrashed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The document instance being trashed.
     *
     * @var \App\Models\Document
     */
    public $document;

    /**
     * The ID of the user who trashed the document.
     *
     * @var int
     */
    public $userId;

    /**
     * Create a new DocumentTrashed event instance.
     *
     * @param \App\Models\Document $document The document being trashed
     * @param int $userId The ID of the user who performed the action
     * @return void
     */
    public function __construct(Document $document, int $userId)
    {
        $this->document = $document;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [];
    }
}