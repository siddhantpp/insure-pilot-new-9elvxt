<?php

namespace Tests\Feature;

use App\Events\DocumentProcessed; // ^10.0
use App\Models\Document; // ^10.0
use App\Models\User; // ^10.0
use App\Services\AuditLogger; //
use App\Services\DocumentManager; //
use Illuminate\Support\Facades\Event; // ^10.0
use Mockery; // ^1.5
use Tests\TestCase; //

/**
 * Feature test class for testing document processing functionality in the Documents View feature.
 */
class DocumentProcessingTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * Default constructor inherited from TestCase
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Set up the test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = app(DocumentManager::class);
        $this->auditLogger = app(AuditLogger::class);
    }

    /**
     * Test that a user with proper permissions can mark a document as processed
     */
    public function test_user_can_mark_document_as_processed(): void
    {
        $document = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);
        $user = $this->createUserWithDocumentPermissions();

        $this->actingAsUser($user);

        $response = $this->post("/api/documents/{$document->id}/process", ['process_state' => true]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Document processed successfully']);

        $document->refresh();
        $this->assertEquals(Document::STATUS_PROCESSED, $document->status_id);

        $history = $this->auditLogger->getDocumentHistory($document->id);
        $this->assertNotNull($history);
        $this->assertNotEmpty($history->items());
        $this->assertEquals('Marked as processed', $history->items()[0]->action_description);
    }

    /**
     * Test that a user with proper permissions can mark a document as unprocessed
     */
    public function test_user_can_mark_document_as_unprocessed(): void
    {
        $document = $this->createDocument(['status_id' => Document::STATUS_PROCESSED]);
        $user = $this->createUserWithDocumentPermissions();

        $this->actingAsUser($user);

        $response = $this->post("/api/documents/{$document->id}/process", ['process_state' => false]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Document processed successfully']);

        $document->refresh();
        $this->assertEquals(Document::STATUS_UNPROCESSED, $document->status_id);

        $history = $this->auditLogger->getDocumentHistory($document->id);
        $this->assertNotNull($history);
        $this->assertNotEmpty($history->items());
        $this->assertEquals('Marked as unprocessed', $history->items()[0]->action_description);
    }

    /**
     * Test that a user without proper permissions cannot process a document
     */
    public function test_unauthorized_user_cannot_process_document(): void
    {
        $document = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);
        $user = $this->createUserWithoutDocumentPermissions();

        $this->actingAsUser($user);

        $response = $this->post("/api/documents/{$document->id}/process", ['process_state' => true]);

        $response->assertStatus(403);

        $document->refresh();
        $this->assertEquals(Document::STATUS_UNPROCESSED, $document->status_id);
    }

    /**
     * Test that an unauthenticated user cannot process a document
     */
    public function test_unauthenticated_user_cannot_process_document(): void
    {
        $document = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);

        $response = $this->post("/api/documents/{$document->id}/process", ['process_state' => true]);

        $response->assertStatus(401);

        $document->refresh();
        $this->assertEquals(Document::STATUS_UNPROCESSED, $document->status_id);
    }

    /**
     * Test that the DocumentProcessed event is dispatched when a document is processed
     */
    public function test_document_processed_event_is_dispatched(): void
    {
        Event::fake();

        $document = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);
        $user = $this->createUserWithDocumentPermissions();

        $this->actingAsUser($user);

        $this->post("/api/documents/{$document->id}/process", ['process_state' => true]);

        Event::assertDispatched(DocumentProcessed::class, function ($event) use ($document, $user) {
            return $event->document->id === $document->id &&
                   $event->userId === $user->id &&
                   $event->isProcessed === true;
        });
    }

    /**
     * Test that the DocumentProcessed event is dispatched when a document is unprocessed
     */
    public function test_document_processed_event_is_dispatched_when_unprocessing(): void
    {
        Event::fake();

        $document = $this->createDocument(['status_id' => Document::STATUS_PROCESSED]);
        $user = $this->createUserWithDocumentPermissions();

        $this->actingAsUser($user);

        $this->post("/api/documents/{$document->id}/process", ['process_state' => false]);

        Event::assertDispatched(DocumentProcessed::class, function ($event) use ($document, $user) {
            return $event->document->id === $document->id &&
                   $event->userId === $user->id &&
                   $event->isProcessed === false;
        });
    }

    /**
     * Test that an invalid process_state value returns a validation error
     */
    public function test_invalid_process_state_returns_validation_error(): void
    {
        $document = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);
        $user = $this->createUserWithDocumentPermissions();

        $this->actingAsUser($user);

        $response = $this->post("/api/documents/{$document->id}/process", ['process_state' => 'invalid']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['process_state']);

        $document->refresh();
        $this->assertEquals(Document::STATUS_UNPROCESSED, $document->status_id);
    }

    /**
     * Test that attempting to process a nonexistent document returns a not found error
     */
    public function test_nonexistent_document_returns_not_found(): void
    {
        $user = $this->createUserWithDocumentPermissions();

        $this->actingAsUser($user);

        $response = $this->post("/api/documents/999999/process", ['process_state' => true]);

        $response->assertStatus(404);
    }

    /**
     * Test direct integration with the DocumentManager service for processing documents
     */
    public function test_document_manager_service_integration(): void
    {
        $document = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);
        $user = $this->createUserWithDocumentPermissions();

        $processedDocument = $this->documentManager->processDocument($document->id, true, $user->id);

        $this->assertNotNull($processedDocument);
        $this->assertEquals(Document::STATUS_PROCESSED, $processedDocument->status_id);

        $unprocessedDocument = $this->documentManager->processDocument($document->id, false, $user->id);

        $this->assertNotNull($unprocessedDocument);
        $this->assertEquals(Document::STATUS_UNPROCESSED, $unprocessedDocument->status_id);
    }

    /**
     * Test that audit logs are properly created for document processing actions
     */
    public function test_audit_logging_for_document_processing(): void
    {
        $document = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);
        $user = $this->createUserWithDocumentPermissions();

        $this->actingAsUser($user);

        $this->post("/api/documents/{$document->id}/process", ['process_state' => true]);

        $history = $this->auditLogger->getDocumentHistory($document->id);
        $this->assertNotNull($history);
        $this->assertNotEmpty($history->items());
        $this->assertEquals('Marked as processed', $history->items()[0]->action_description);
        $this->assertEquals($user->id, $history->items()[0]->created_by);

        $this->post("/api/documents/{$document->id}/process", ['process_state' => false]);

        $updatedHistory = $this->auditLogger->getDocumentHistory($document->id);
        $this->assertNotNull($updatedHistory);
        $this->assertCount(2, $updatedHistory->items());
        $this->assertEquals('Marked as unprocessed', $updatedHistory->items()[0]->action_description);
        $this->assertEquals($user->id, $updatedHistory->items()[0]->created_by);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}