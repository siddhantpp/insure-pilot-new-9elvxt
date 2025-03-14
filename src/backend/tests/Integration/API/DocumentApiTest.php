<?php

namespace Tests\Integration\API;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\File;
use App\Models\MapDocumentAction;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Storage facade for file operations
        Storage::fake('documents');
    }

    /**
     * Test that the GET /api/documents endpoint returns a paginated list of documents.
     *
     * @return void
     */
    public function testGetDocumentsReturnsDocumentList()
    {
        // Create multiple test documents
        $documents = [];
        for ($i = 0; $i < 5; $i++) {
            $documents[] = $this->createDocument([
                'name' => "Test Document $i",
            ]);
        }

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a GET request to /api/documents
        $response = $this->getJson('/api/documents');

        // Assert response has 200 status code
        $response->assertStatus(200);
        
        // Assert response contains the expected document data
        $response->assertJsonCount(5, 'data');
        
        // Assert response has the correct pagination structure
        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);
    }

    /**
     * Test that the GET /api/documents endpoint correctly applies filters.
     *
     * @return void
     */
    public function testGetDocumentsWithFilters()
    {
        // Create test documents with different attributes
        $processedDocument = $this->createDocument([
            'name' => 'Processed Document',
            'status_id' => Document::STATUS_PROCESSED,
        ]);
        
        $unprocessedDocument = $this->createDocument([
            'name' => 'Unprocessed Document',
            'status_id' => Document::STATUS_UNPROCESSED,
        ]);

        // Create a test document with policy, loss, etc.
        $documentWithPolicy = $this->createDocumentWithMetadata([
            'name' => 'Document with Policy',
        ]);
        $policyId = $documentWithPolicy->policy_id;

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Test filtering by status
        $response = $this->getJson('/api/documents?status_id=' . Document::STATUS_PROCESSED);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Processed Document');

        // Test filtering by policy_id
        $response = $this->getJson('/api/documents?policy_id=' . $policyId);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Document with Policy');
    }

    /**
     * Test that the GET /api/documents endpoint requires authentication.
     *
     * @return void
     */
    public function testGetDocumentsRequiresAuthentication()
    {
        // Make a GET request to /api/documents without authentication
        $response = $this->getJson('/api/documents');

        // Assert response has 401 status code
        $response->assertStatus(401);
    }

    /**
     * Test that the GET /api/documents/{id} endpoint returns the correct document.
     *
     * @return void
     */
    public function testGetDocumentReturnsCorrectDocument()
    {
        // Create a test document with metadata
        $document = $this->createDocumentWithMetadata([
            'name' => 'Test Document',
            'description' => 'Test Description',
        ]);

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}
        $response = $this->getJson('/api/documents/' . $document->id);

        // Assert response has 200 status code
        $response->assertStatus(200);
        
        // Assert response contains the expected document data
        $response->assertJsonPath('id', $document->id);
        $response->assertJsonPath('name', 'Test Document');
        $response->assertJsonPath('description', 'Test Description');
        
        // Assert response includes the correct metadata
        $response->assertJsonPath('policy_id', $document->policy_id);
        $response->assertJsonPath('loss_id', $document->loss_id);
        $response->assertJsonPath('claimant_id', $document->claimant_id);
        $response->assertJsonPath('producer_id', $document->producer_id);
    }

    /**
     * Test that the GET /api/documents/{id} endpoint returns 404 for nonexistent documents.
     *
     * @return void
     */
    public function testGetDocumentReturns404ForNonexistentDocument()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/999 (nonexistent ID)
        $response = $this->getJson('/api/documents/999');

        // Assert response has 404 status code
        $response->assertStatus(404);
    }

    /**
     * Test that the GET /api/documents/{id} endpoint requires authentication.
     *
     * @return void
     */
    public function testGetDocumentRequiresAuthentication()
    {
        // Create a test document
        $document = $this->createDocument();

        // Make a GET request to /api/documents/{id} without authentication
        $response = $this->getJson('/api/documents/' . $document->id);

        // Assert response has 401 status code
        $response->assertStatus(401);
    }

    /**
     * Test that the GET /api/documents/{id} endpoint requires proper authorization.
     *
     * @return void
     */
    public function testGetDocumentRequiresAuthorization()
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a user without document permissions
        $user = $this->createUserWithoutDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}
        $response = $this->getJson('/api/documents/' . $document->id);

        // Assert response has 403 status code
        $response->assertStatus(403);
    }

    /**
     * Test that the PUT /api/documents/{id} endpoint updates document metadata.
     *
     * @return void
     */
    public function testUpdateDocumentUpdatesMetadata()
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata([
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]);

        // Capture original data for comparison
        $originalPolicyId = $document->policy_id;
        
        // Create a different policy to update to
        $newMetadata = $this->createDocumentWithMetadata();
        $newPolicyId = $newMetadata->policy_id;
        $newLossId = $newMetadata->loss_id;
        $newClaimantId = $newMetadata->claimant_id;
        $newProducerId = $newMetadata->producer_id;

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Prepare updated metadata
        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'policy_id' => $newPolicyId,
            'loss_id' => $newLossId,
            'claimant_id' => $newClaimantId,
            'producer_id' => $newProducerId,
        ];

        // Make a PUT request to /api/documents/{id} with updated metadata
        $response = $this->putJson('/api/documents/' . $document->id, $updatedData);

        // Assert response has 200 status code
        $response->assertStatus(200);
        
        // Assert response contains the updated metadata
        $response->assertJsonPath('name', 'Updated Name');
        $response->assertJsonPath('description', 'Updated Description');
        $response->assertJsonPath('policy_id', $newPolicyId);
        $response->assertJsonPath('loss_id', $newLossId);
        $response->assertJsonPath('claimant_id', $newClaimantId);
        $response->assertJsonPath('producer_id', $newProducerId);
        
        // Verify the database record was updated correctly
        $this->assertDatabaseHas('document', [
            'id' => $document->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'policy_id' => $newPolicyId,
            'loss_id' => $newLossId,
            'claimant_id' => $newClaimantId,
            'producer_id' => $newProducerId,
        ]);
    }

    /**
     * Test that the PUT /api/documents/{id} endpoint validates input data.
     *
     * @return void
     */
    public function testUpdateDocumentValidatesInput()
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Prepare invalid metadata (e.g., invalid policy_id)
        $invalidData = [
            'policy_id' => 9999, // Non-existent policy ID
        ];

        // Make a PUT request to /api/documents/{id} with invalid metadata
        $response = $this->putJson('/api/documents/' . $document->id, $invalidData);

        // Assert response has 422 status code
        $response->assertStatus(422);
        
        // Assert response contains validation error messages
        $response->assertJsonValidationErrors(['policy_id']);
    }

    /**
     * Test that the PUT /api/documents/{id} endpoint requires authentication.
     *
     * @return void
     */
    public function testUpdateDocumentRequiresAuthentication()
    {
        // Create a test document
        $document = $this->createDocument();

        // Prepare valid metadata
        $validData = [
            'name' => 'Updated Name',
        ];

        // Make a PUT request to /api/documents/{id} without authentication
        $response = $this->putJson('/api/documents/' . $document->id, $validData);

        // Assert response has 401 status code
        $response->assertStatus(401);
    }

    /**
     * Test that the PUT /api/documents/{id} endpoint requires proper authorization.
     *
     * @return void
     */
    public function testUpdateDocumentRequiresAuthorization()
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a user without document permissions
        $user = $this->createUserWithoutDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Prepare valid metadata
        $validData = [
            'name' => 'Updated Name',
        ];

        // Make a PUT request to /api/documents/{id}
        $response = $this->putJson('/api/documents/' . $document->id, $validData);

        // Assert response has 403 status code
        $response->assertStatus(403);
    }

    /**
     * Test that processed documents cannot be updated.
     *
     * @return void
     */
    public function testUpdateDocumentCannotUpdateProcessedDocument()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Mark the document as processed
        $document->markAsProcessed();

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Prepare valid metadata
        $validData = [
            'name' => 'Updated Name',
        ];

        // Make a PUT request to /api/documents/{id}
        $response = $this->putJson('/api/documents/' . $document->id, $validData);

        // Assert response has 422 status code
        $response->assertStatus(422);
        
        // Assert response contains error about processed documents
        $response->assertJsonValidationErrors(['document']);
    }

    /**
     * Test that the POST /api/documents/{id}/process endpoint marks a document as processed.
     *
     * @return void
     */
    public function testProcessDocumentMarksDocumentAsProcessed()
    {
        // Create a test document
        $document = $this->createDocument([
            'status_id' => Document::STATUS_UNPROCESSED,
        ]);

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a POST request to /api/documents/{id}/process with process_state=true
        $response = $this->postJson('/api/documents/' . $document->id . '/process', [
            'process_state' => true,
        ]);

        // Assert response has 200 status code
        $response->assertStatus(200);
        
        // Assert response indicates document is processed
        $response->assertJsonPath('status_id', Document::STATUS_PROCESSED);
        $response->assertJsonPath('is_processed', true);
        
        // Verify the document status in the database is updated
        $this->assertDatabaseHas('document', [
            'id' => $document->id,
            'status_id' => Document::STATUS_PROCESSED,
        ]);
        
        // Verify an action record was created for the process action
        $this->assertDatabaseHas('map_document_action', [
            'document_id' => $document->id,
        ]);
    }

    /**
     * Test that the POST /api/documents/{id}/process endpoint can unprocess a document.
     *
     * @return void
     */
    public function testProcessDocumentCanUnprocessDocument()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Mark the document as processed
        $document->markAsProcessed();

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a POST request to /api/documents/{id}/process with process_state=false
        $response = $this->postJson('/api/documents/' . $document->id . '/process', [
            'process_state' => false,
        ]);

        // Assert response has 200 status code
        $response->assertStatus(200);
        
        // Assert response indicates document is unprocessed
        $response->assertJsonPath('status_id', Document::STATUS_UNPROCESSED);
        $response->assertJsonPath('is_processed', false);
        
        // Verify the document status in the database is updated
        $this->assertDatabaseHas('document', [
            'id' => $document->id,
            'status_id' => Document::STATUS_UNPROCESSED,
        ]);
        
        // Verify an action record was created for the unprocess action
        $this->assertDatabaseHas('map_document_action', [
            'document_id' => $document->id,
        ]);
    }

    /**
     * Test that the POST /api/documents/{id}/process endpoint requires authentication.
     *
     * @return void
     */
    public function testProcessDocumentRequiresAuthentication()
    {
        // Create a test document
        $document = $this->createDocument();

        // Make a POST request to /api/documents/{id}/process without authentication
        $response = $this->postJson('/api/documents/' . $document->id . '/process', [
            'process_state' => true,
        ]);

        // Assert response has 401 status code
        $response->assertStatus(401);
    }

    /**
     * Test that the POST /api/documents/{id}/process endpoint requires proper authorization.
     *
     * @return void
     */
    public function testProcessDocumentRequiresAuthorization()
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a user without document permissions
        $user = $this->createUserWithoutDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a POST request to /api/documents/{id}/process
        $response = $this->postJson('/api/documents/' . $document->id . '/process', [
            'process_state' => true,
        ]);

        // Assert response has 403 status code
        $response->assertStatus(403);
    }

    /**
     * Test that the POST /api/documents/{id}/trash endpoint moves a document to trash.
     *
     * @return void
     */
    public function testTrashDocumentMovesDocumentToTrash()
    {
        // Create a test document
        $document = $this->createDocument([
            'status_id' => Document::STATUS_UNPROCESSED,
        ]);

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a POST request to /api/documents/{id}/trash
        $response = $this->postJson('/api/documents/' . $document->id . '/trash');

        // Assert response has 200 status code
        $response->assertStatus(200);
        
        // Assert response indicates success
        $response->assertJsonPath('success', true);
        
        // Verify the document status in the database is updated to trashed
        $this->assertSoftDeleted('document', [
            'id' => $document->id,
            'status_id' => Document::STATUS_TRASHED,
        ]);
        
        // Verify an action record was created for the trash action
        $this->assertDatabaseHas('map_document_action', [
            'document_id' => $document->id,
        ]);
    }

    /**
     * Test that the POST /api/documents/{id}/trash endpoint requires authentication.
     *
     * @return void
     */
    public function testTrashDocumentRequiresAuthentication()
    {
        // Create a test document
        $document = $this->createDocument();

        // Make a POST request to /api/documents/{id}/trash without authentication
        $response = $this->postJson('/api/documents/' . $document->id . '/trash');

        // Assert response has 401 status code
        $response->assertStatus(401);
    }

    /**
     * Test that the POST /api/documents/{id}/trash endpoint requires proper authorization.
     *
     * @return void
     */
    public function testTrashDocumentRequiresAuthorization()
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a user without document permissions
        $user = $this->createUserWithoutDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a POST request to /api/documents/{id}/trash
        $response = $this->postJson('/api/documents/' . $document->id . '/trash');

        // Assert response has 403 status code
        $response->assertStatus(403);
    }

    /**
     * Test that the GET /api/documents/{id}/history endpoint returns document action history.
     *
     * @return void
     */
    public function testGetDocumentHistoryReturnsActionHistory()
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Perform various actions on the document (view, update, process)
        // These actions would normally be recorded through the controllers
        // For testing, we'll create action records directly
        
        // Create action records
        $viewAction = MapDocumentAction::create([
            'document_id' => $document->id,
            'action_id' => 1, // Assume 1 is 'view' action_id
            'description' => 'Document viewed',
            'status_id' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        $updateAction = MapDocumentAction::create([
            'document_id' => $document->id,
            'action_id' => 2, // Assume 2 is 'update' action_id
            'description' => 'Document metadata updated',
            'status_id' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        $processAction = MapDocumentAction::create([
            'document_id' => $document->id,
            'action_id' => 3, // Assume 3 is 'process' action_id
            'description' => 'Document marked as processed',
            'status_id' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Make a GET request to /api/documents/{id}/history
        $response = $this->getJson('/api/documents/' . $document->id . '/history');

        // Assert response has 200 status code
        $response->assertStatus(200);
        
        // Assert response contains the expected action records
        $response->assertJsonCount(3, 'data');
        
        // Assert actions are in the correct chronological order (newest first)
        $response->assertJsonPath('data.0.id', $processAction->id);
        $response->assertJsonPath('data.1.id', $updateAction->id);
        $response->assertJsonPath('data.2.id', $viewAction->id);
        
        // Assert response has the correct pagination structure
        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);
    }

    /**
     * Test that the GET /api/documents/{id}/history endpoint requires authentication.
     *
     * @return void
     */
    public function testGetDocumentHistoryRequiresAuthentication()
    {
        // Create a test document
        $document = $this->createDocument();

        // Make a GET request to /api/documents/{id}/history without authentication
        $response = $this->getJson('/api/documents/' . $document->id . '/history');

        // Assert response has 401 status code
        $response->assertStatus(401);
    }

    /**
     * Test that the GET /api/documents/{id}/history endpoint requires proper authorization.
     *
     * @return void
     */
    public function testGetDocumentHistoryRequiresAuthorization()
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a user without document permissions
        $user = $this->createUserWithoutDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}/history
        $response = $this->getJson('/api/documents/' . $document->id . '/history');

        // Assert response has 403 status code
        $response->assertStatus(403);
    }

    /**
     * Test that the GET /api/documents/{id}/file endpoint returns the document file.
     *
     * @return void
     */
    public function testGetDocumentFileReturnsFile()
    {
        // Create a test document with a file
        $document = $this->createDocumentWithFile();
        
        // Get the file record
        $file = $document->files->first();
        
        // Put test content in the fake storage
        $fileContent = 'Test file content';
        Storage::put($file->path, $fileContent);

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}/file
        $response = $this->get('/api/documents/' . $document->id . '/file');

        // Assert response has 200 status code
        $response->assertStatus(200);
        
        // Assert response has the correct content type
        $response->assertHeader('Content-Type', $file->mime_type);
        
        // Assert response contains the file content
        $this->assertEquals($fileContent, $response->getContent());
    }

    /**
     * Test that the GET /api/documents/{id}/file endpoint returns 404 for documents without files.
     *
     * @return void
     */
    public function testGetDocumentFileReturns404ForDocumentWithoutFile()
    {
        // Create a test document without a file
        $document = $this->createDocument();

        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}/file
        $response = $this->get('/api/documents/' . $document->id . '/file');

        // Assert response has 404 status code
        $response->assertStatus(404);
    }

    /**
     * Test that the GET /api/documents/{id}/file endpoint requires authentication.
     *
     * @return void
     */
    public function testGetDocumentFileRequiresAuthentication()
    {
        // Create a test document with a file
        $document = $this->createDocumentWithFile();

        // Make a GET request to /api/documents/{id}/file without authentication
        $response = $this->get('/api/documents/' . $document->id . '/file');

        // Assert response has 401 status code
        $response->assertStatus(401);
    }

    /**
     * Test that the GET /api/documents/{id}/file endpoint requires proper authorization.
     *
     * @return void
     */
    public function testGetDocumentFileRequiresAuthorization()
    {
        // Create a test document with a file
        $document = $this->createDocumentWithFile();

        // Create a user without document permissions
        $user = $this->createUserWithoutDocumentPermissions();

        // Act as the authenticated user
        $this->actingAsUser($user);

        // Make a GET request to /api/documents/{id}/file
        $response = $this->get('/api/documents/' . $document->id . '/file');

        // Assert response has 403 status code
        $response->assertStatus(403);
    }
}