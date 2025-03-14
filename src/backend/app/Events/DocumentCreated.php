<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets; // ^10.0
use Illuminate\Foundation\Events\Dispatchable; // ^10.0
use Illuminate\Queue\SerializesModels; // ^10.0

/**
 * Event class that is dispatched when a new document is created in the system.
 * This event contains the newly created Document model instance and the ID of the user who created it.
 * It enables listeners to perform actions such as audit logging, search indexing, and sending notifications.
 */
class DocumentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The document that was created.
     *
     * @var \App\Models\Document
     */
    public $document;

    /**
     * The ID of the user who created the document.
     *
     * @var int
     */
    public $userId;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Document  $document
     * @param  int  $userId
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