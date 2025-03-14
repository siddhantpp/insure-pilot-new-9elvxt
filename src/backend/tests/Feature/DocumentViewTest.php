<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event; // ^10.0
use Mockery; // ^1.5
use Tests\TestCase;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentManager;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage; // ^10.0

class DocumentViewTest extends TestCase
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
     * Set up the test environment before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = app(DocumentManager::class);
        $this->auditLogger = app(AuditLogger::class);

        Storage::fake('documents');
    }

    /**
     * Test that a user with proper permissions can view the document list
     *
     * @return void
     */
    public function test_user_can_view_document_list(): void
    {
        // Create multiple test documents
        $document1 = $this->createDocument(['name' => 'Document 1']);
        $document2 = $this->createDocument(['name' => 'Document 2']);

        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents
        $response = $this->get('/api/documents');

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response JSON contains the expected document data
        $response->assertJsonFragment(['name' => 'Document 1']);
        $response->assertJsonFragment(['name' => 'Document 2']);

        // Assert that the response includes pagination information
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
    }

    /**
     * Test that a user with proper permissions can view document details
     *
     * @return void
     */
    public function test_user_can_view_document_details(): void
    {
        // Create a test document with metadata
        $document = $this->createDocumentWithMetadata(['name' => 'Test Document']);

        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}
        $response = $this->get("/api/documents/{$document->id}");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response JSON contains the expected document data
        $response->assertJsonFragment(['name' => 'Test Document']);

        // Assert that the response includes metadata information
        $response->assertJsonStructure([
            'id',
            'name',
            'description',
            'policy_id',
            'loss_id',
            'claimant_id',
            'producer_id',
        ]);

        // Assert that a document view action was logged
        $this->assertDatabaseHas('map_document_action', [
            'document_id' => $document->id,
            'description' => 'Document viewed',
        ]);
    }

    /**
     * Test that a user with proper permissions can view the document file
     *
     * @return void
     */
    public function test_user_can_view_document_file(): void
    {
        // Create a test document with an associated file
        $document = $this->createDocumentWithFile(['name' => 'Test Document']);

        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}/file
        $response = $this->get("/api/documents/{$document->id}/file");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response has the correct content type
        $response->assertHeader('Content-Type', 'application/pdf');

        // Assert that the response contains the expected file content
        $response->assertSee('This is a test PDF file');
    }

    /**
     * Test that a user without proper permissions cannot view document details
     *
     * @return void
     */
    public function test_unauthorized_user_cannot_view_document(): void
    {
        // Create a test document
        $document = $this->createDocument(['name' => 'Test Document']);

        // Create a user without document viewing permissions
        $user = $this->createUserWithoutDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}
        $response = $this->get("/api/documents/{$document->id}");

        // Assert that the response has a 403 status code (Forbidden)
        $response->assertStatus(403);
    }

    /**
     * Test that an unauthenticated user cannot view document details
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_view_document(): void
    {
        // Create a test document
        $document = $this->createDocument(['name' => 'Test Document']);

        // Make a GET request to /api/documents/{id} without authentication
        $response = $this->get("/api/documents/{$document->id}");

        // Assert that the response has a 401 status code (Unauthorized)
        $response->assertStatus(401);
    }

    /**
     * Test that attempting to view a nonexistent document returns a not found error
     *
     * @return void
     */
    public function test_nonexistent_document_returns_not_found(): void
    {
        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/999999
        $response = $this->get('/api/documents/999999');

        // Assert that the response has a 404 status code (Not Found)
        $response->assertStatus(404);
    }

    /**
     * Test that viewing a document creates an audit log entry
     *
     * @return void
     */
    public function test_document_view_is_logged(): void
    {
        // Create a test document
        $document = $this->createDocument(['name' => 'Test Document']);

        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}
        $this->get("/api/documents/{$document->id}");

        // Get document history using auditLogger->getDocumentHistory
        $history = $this->auditLogger->getDocumentHistory($document->id);

        // Assert that the history contains at least one entry
        $this->assertNotEmpty($history);

        // Assert that the most recent entry is for the view action
        $this->assertEquals('Document viewed', $history->first()->action_description);

        // Assert that the entry has the correct user ID
        $this->assertEquals($user->id, $history->first()->created_by);
    }

    /**
     * Test direct integration with the DocumentManager service for retrieving documents
     *
     * @return void
     */
    public function test_document_manager_service_integration(): void
    {
        // Create a test document with metadata
        $document = $this->createDocumentWithMetadata(['name' => 'Test Document']);

        // Call documentManager->getDocument with document ID
        $retrievedDocument = $this->documentManager->getDocument($document->id);

        // Assert that the returned document is not null
        $this->assertNotNull($retrievedDocument);

        // Assert that the document has the expected properties and relationships
        $this->assertEquals('Test Document', $retrievedDocument->name);
        $this->assertNotNull($retrievedDocument->policy);
        $this->assertNotNull($retrievedDocument->loss);
        $this->assertNotNull($retrievedDocument->claimant);
        $this->assertNotNull($retrievedDocument->producer);

        // Assert that the document metadata matches the expected values
        $this->assertEquals('PLCY-12345', $retrievedDocument->policy->number);
        $this->assertEquals('Vehicle Accident', $retrievedDocument->loss->name);
    }

    /**
     * Test that documents can be filtered by status
     *
     * @return void
     */
    public function test_document_filtering_by_status(): void
    {
        // Create processed and unprocessed test documents
        $processedDocument = $this->createDocument(['name' => 'Processed Document', 'status_id' => Document::STATUS_PROCESSED]);
        $unprocessedDocument = $this->createDocument(['name' => 'Unprocessed Document', 'status_id' => Document::STATUS_UNPROCESSED]);

        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents?status=processed
        $responseProcessed = $this->get('/api/documents?status=processed');

        // Assert that the response only includes processed documents
        $responseProcessed->assertJsonFragment(['name' => 'Processed Document']);
        $responseProcessed->assertJsonMissing(['name' => 'Unprocessed Document']);

        // Make a GET request to /api/documents?status=unprocessed
        $responseUnprocessed = $this->get('/api/documents?status=unprocessed');

        // Assert that the response only includes unprocessed documents
        $responseUnprocessed->assertJsonFragment(['name' => 'Unprocessed Document']);
        $responseUnprocessed->assertJsonMissing(['name' => 'Processed Document']);
    }

    /**
     * Test that documents can be filtered by metadata (policy, loss, claimant, producer)
     *
     * @return void
     */
    public function test_document_filtering_by_metadata(): void
    {
        // Create test documents with different metadata
        $document1 = $this->createDocumentWithMetadata(['name' => 'Document 1'], ['policy_number' => 'PLCY-111']);
        $document2 = $this->createDocumentWithMetadata(['name' => 'Document 2'], ['loss_name' => 'Accident 222']);
        $document3 = $this->createDocumentWithMetadata(['name' => 'Document 3'], ['claimant_name' => 'Claimant 333']);
        $document4 = $this->createDocumentWithMetadata(['name' => 'Document 4'], ['producer_number' => 'AG-444']);

        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents with policy_id filter
        $responsePolicy = $this->get('/api/documents?policy_id=' . $document1->policy_id);

        // Assert that the response only includes documents with the specified policy
        $responsePolicy->assertJsonFragment(['name' => 'Document 1']);
        $responsePolicy->assertJsonMissing(['name' => 'Document 2']);
        $responsePolicy->assertJsonMissing(['name' => 'Document 3']);
        $responsePolicy->assertJsonMissing(['name' => 'Document 4']);

        // Make a GET request to /api/documents with loss_id filter
        $responseLoss = $this->get('/api/documents?loss_id=' . $document2->loss_id);

        // Assert that the response only includes documents with the specified loss
        $responseLoss->assertJsonFragment(['name' => 'Document 2']);
        $responseLoss->assertJsonMissing(['name' => 'Document 1']);
        $responseLoss->assertJsonMissing(['name' => 'Document 3']);
        $responseLoss->assertJsonMissing(['name' => 'Document 4']);

        // Make a GET request to /api/documents with claimant_id filter
        $responseClaimant = $this->get('/api/documents?claimant_id=' . $document3->claimant_id);

        // Assert that the response only includes documents with the specified claimant
        $responseClaimant->assertJsonFragment(['name' => 'Document 3']);
        $responseClaimant->assertJsonMissing(['name' => 'Document 1']);
        $responseClaimant->assertJsonMissing(['name' => 'Document 2']);
        $responseClaimant->assertJsonMissing(['name' => 'Document 4']);

        // Make a GET request to /api/documents with producer_id filter
        $responseProducer = $this->get('/api/documents?producer_id=' . $document4->producer_id);

        // Assert that the response only includes documents with the specified producer
        $responseProducer->assertJsonFragment(['name' => 'Document 4']);
        $responseProducer->assertJsonMissing(['name' => 'Document 1']);
        $responseProducer->assertJsonMissing(['name' => 'Document 2']);
        $responseProducer->assertJsonMissing(['name' => 'Document 3']);
    }

    /**
     * Test that documents can be searched by name, description, or related entities
     *
     * @return void
     */
    public function test_document_search_functionality(): void
    {
        // Create test documents with different names and descriptions
        $document1 = $this->createDocument(['name' => 'Policy Document', 'description' => 'This is a policy document']);
        $document2 = $this->createDocument(['name' => 'Claim Document', 'description' => 'This is a claim related document']);
        $document3 = $this->createDocument(['name' => 'Other Document', 'description' => 'This is another document']);

        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents?search=keyword
        $response = $this->get('/api/documents?search=policy');

        // Assert that the response includes documents matching the search term
        $response->assertJsonFragment(['name' => 'Policy Document']);

        // Assert that the response excludes documents not matching the search term
        $response->assertJsonMissing(['name' => 'Claim Document']);
        $response->assertJsonMissing(['name' => 'Other Document']);
    }

    /**
     * Test that documents can be sorted by different fields
     *
     * @return void
     */
    public function test_document_sorting(): void
    {
        // Create test documents with different creation dates and names
        $document1 = $this->createDocument(['name' => 'Document B', 'created_at' => now()->subDays(2)]);
        $document2 = $this->createDocument(['name' => 'Document A', 'created_at' => now()->subDays(1)]);
        $document3 = $this->createDocument(['name' => 'Document C', 'created_at' => now()]);

        // Create a user with document viewing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Make a GET request to /api/documents?sort_by=created_at&sort_direction=desc
        $responseCreatedAtDesc = $this->get('/api/documents?sort_by=created_at&sort_direction=desc');

        // Assert that the response has documents sorted by creation date in descending order
        $responseCreatedAtDesc->assertJsonPath('data.0.name', 'Document C');
        $responseCreatedAtDesc->assertJsonPath('data.1.name', 'Document A');
        $responseCreatedAtDesc->assertJsonPath('data.2.name', 'Document B');

        // Make a GET request to /api/documents?sort_by=name&sort_direction=asc
        $responseNameAsc = $this->get('/api/documents?sort_by=name&sort_direction=asc');

        // Assert that the response has documents sorted by name in ascending order
        $responseNameAsc->assertJsonPath('data.0.name', 'Document A');
        $responseNameAsc->assertJsonPath('data.1.name', 'Document B');
        $responseNameAsc->assertJsonPath('data.2.name', 'Document C');
    }
}