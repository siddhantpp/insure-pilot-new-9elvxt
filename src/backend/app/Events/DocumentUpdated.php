<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable; // ^10.0
use Illuminate\Queue\SerializesModels; // ^10.0

/**
 * Event class that is dispatched when a document's metadata is updated in the system.
 * This event contains the Document model instance, the ID of the user who updated it,
 * and an array of changes made to the document metadata.
 */
class DocumentUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The document that was updated.
     *
     * @var Document
     */
    public $document;

    /**
     * The ID of the user who updated the document.
     *
     * @var int
     */
    public $userId;

    /**
     * Array of changes made to the document metadata.
     * Format: ['field_name' => ['old' => 'old_value', 'new' => 'new_value']]
     *
     * @var array
     */
    public $changes;

    /**
     * Create a new DocumentUpdated event instance.
     *
     * @param Document $document The document model that was updated
     * @param int $userId The ID of the user who performed the update
     * @param array $changes Array of changes made to the document metadata
     * @return void
     */
    public function __construct(Document $document, int $userId, array $changes)
    {
        $this->document = $document;
        $this->userId = $userId;
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // This event does not need to be broadcast
        return [];
    }
}