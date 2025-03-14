<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event class that is dispatched when a document is marked as processed or unprocessed in the system.
 * This event is part of the event-driven architecture for document processing, enabling listeners to
 * perform actions such as audit logging, search indexing, and sending notifications when document
 * processing status changes.
 */
class DocumentProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The document that was processed/unprocessed.
     *
     * @var Document
     */
    public $document;

    /**
     * The ID of the user who processed/unprocessed the document.
     *
     * @var int
     */
    public $userId;

    /**
     * Indicates whether the document is now processed (true) or unprocessed (false).
     *
     * @var bool
     */
    public $isProcessed;

    /**
     * Creates a new DocumentProcessed event instance
     *
     * @param Document $document The document that was processed/unprocessed
     * @param int $userId The ID of the user who performed the action
     * @param bool $isProcessed True if the document is now processed, false if unprocessed
     * @return void
     */
    public function __construct(Document $document, int $userId, bool $isProcessed)
    {
        $this->document = $document;
        $this->userId = $userId;
        $this->isProcessed = $isProcessed;
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