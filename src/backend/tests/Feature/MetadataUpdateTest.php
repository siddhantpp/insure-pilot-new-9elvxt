<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use App\Models\Document;
use App\Models\Policy;
use App\Models\Loss;
use App\Models\Claimant;
use App\Models\Producer;
use App\Models\MapPolicyLoss;
use App\Models\MapLossClaimant;
use App\Models\MapProducerPolicy;
use App\Services\MetadataService;
use App\Services\AuditLogger;

class MetadataUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $metadataService;
    protected $auditLogger;

    /**
     * Set up the test environment before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->metadataService = App::make(MetadataService::class);
        $this->auditLogger = App::make(AuditLogger::class);
    }

    /**
     * Test that a user with proper permissions can update document metadata
     *
     * @return void
     */
    public function test_user_can_update_document_metadata(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Prepare updated metadata values
        $updatedData = [
            'policy_id' => Policy::factory()->create(['number' => 'PLCY-67890'])->id,
            'description' => 'Updated document description',
        ];

        // Make a PUT request to update the document metadata
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updatedData);

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response contains the updated values
        $response->assertJsonFragment([
            'description' => 'Updated document description',
            'policy_id' => $updatedData['policy_id'],
        ]);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes match the updated values
        $this->assertEquals($updatedData['policy_id'], $document->policy_id);
        $this->assertEquals($updatedData['description'], $document->description);

        // Assert that an audit log entry was created for the edit action
        $history = $this->auditLogger->getDocumentHistory($document->id);
        $this->assertNotNull($history);
        $this->assertGreaterThan(0, $history->count());
    }

    /**
     * Test that a user without proper permissions cannot update document metadata
     *
     * @return void
     */
    public function test_unauthorized_user_cannot_update_document_metadata(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Create a user without document editing permissions
        $user = $this->createUserWithoutDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Prepare updated metadata values
        $updatedData = [
            'policy_id' => Policy::factory()->create(['number' => 'PLCY-67890'])->id,
            'description' => 'Updated document description',
        ];

        // Make a PUT request to update the document metadata
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updatedData);

        // Assert that the response has a 403 status code (Forbidden)
        $response->assertStatus(403);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes still match the original values
        $this->assertNotEquals($updatedData['policy_id'], $document->policy_id);
        $this->assertNotEquals($updatedData['description'], $document->description);
    }

    /**
     * Test that an unauthenticated user cannot update document metadata
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_update_document_metadata(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Prepare updated metadata values
        $updatedData = [
            'policy_id' => Policy::factory()->create(['number' => 'PLCY-67890'])->id,
            'description' => 'Updated document description',
        ];

        // Make a PUT request without authentication
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updatedData);

        // Assert that the response has a 401 status code (Unauthorized)
        $response->assertStatus(401);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes still match the original values
        $this->assertNotEquals($updatedData['policy_id'], $document->policy_id);
        $this->assertNotEquals($updatedData['description'], $document->description);
    }

    /**
     * Test that metadata cannot be updated for a document that is marked as processed
     *
     * @return void
     */
    public function test_cannot_update_processed_document_metadata(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Mark the document as processed
        $document->markAsProcessed();

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Prepare updated metadata values
        $updatedData = [
            'policy_id' => Policy::factory()->create(['number' => 'PLCY-67890'])->id,
            'description' => 'Updated document description',
        ];

        // Make a PUT request to update the document metadata
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updatedData);

        // Assert that the response has a 422 status code (Unprocessable Entity)
        $response->assertStatus(422);

        // Assert that the response contains an error message about processed documents
        $response->assertJsonFragment([
            'message' => 'Cannot update metadata for a processed document',
        ]);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes still match the original values
        $this->assertNotEquals($updatedData['policy_id'], $document->policy_id);
        $this->assertNotEquals($updatedData['description'], $document->description);
    }

    /**
     * Test that validation enforces field dependencies (e.g., Loss Sequence depends on Policy Number)
     *
     * @return void
     */
    public function test_validation_enforces_field_dependencies(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Create a new policy and loss that are not related
        $policy = Policy::factory()->create(['number' => 'PLCY-67890']);
        $loss = Loss::factory()->create();

        // Prepare metadata with loss_id that doesn't belong to the policy_id
        $invalidData = [
            'policy_id' => $policy->id,
            'loss_id' => $loss->id, // Invalid: This loss is not associated with the policy
        ];

        // Make a PUT request with invalid dependencies
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $invalidData);

        // Assert that the response has a 422 status code (Unprocessable Entity)
        $response->assertStatus(422);

        // Assert that the response contains validation errors about field dependencies
        $response->assertJsonValidationErrors(['loss_id']);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes still match the original values
        $this->assertNotEquals($policy->id, $document->policy_id);
        $this->assertNotEquals($loss->id, $document->loss_id);
    }

    /**
     * Test that validation enforces that a claimant must belong to the selected loss
     *
     * @return void
     */
    public function test_validation_enforces_claimant_belongs_to_loss(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Create a new loss and claimant that are not related
        $loss = Loss::factory()->create();
        $claimant = Claimant::factory()->create();

        // Create a relationship between the loss and the original policy
        MapPolicyLoss::create([
            'policy_id' => $document->policy_id,
            'loss_id' => $loss->id,
            'status_id' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Prepare metadata with claimant_id that doesn't belong to the loss_id
        $invalidData = [
            'loss_id' => $loss->id,
            'claimant_id' => $claimant->id, // Invalid: This claimant is not associated with the loss
        ];

        // Make a PUT request with invalid dependencies
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $invalidData);

        // Assert that the response has a 422 status code (Unprocessable Entity)
        $response->assertStatus(422);

        // Assert that the response contains validation errors about claimant-loss relationship
        $response->assertJsonValidationErrors(['claimant_id']);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes still match the original values
        $this->assertNotEquals($loss->id, $document->loss_id);
        $this->assertNotEquals($claimant->id, $document->claimant_id);
    }

    /**
     * Test that validation enforces required fields
     *
     * @return void
     */
    public function test_validation_enforces_required_fields(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Prepare metadata with missing required fields
        $invalidData = [
            'policy_id' => null, // Required field
            'description' => '',  // Required field
        ];

        // Make a PUT request with missing fields
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $invalidData);

        // Assert that the response has a 422 status code (Unprocessable Entity)
        $response->assertStatus(422);

        // Assert that the response contains validation errors about required fields
        $response->assertJsonValidationErrors(['policy_id', 'description']);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes still match the original values
        $this->assertNotNull($document->policy_id);
        $this->assertNotEmpty($document->description);
    }

    /**
     * Test that optional fields can be cleared (set to null)
     *
     * @return void
     */
    public function test_can_clear_optional_fields(): void
    {
        // Create a test document with all metadata fields populated
        $document = $this->createDocumentWithMetadata();

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Prepare metadata with null values for optional fields
        $updateData = [
            'producer_id' => null,
            'claimant_id' => null,
            'loss_id' => null,
        ];

        // Make a PUT request with null values
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updateData);

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response JSON contains null values for optional fields
        $response->assertJsonFragment([
            'producer_id' => null,
            'claimant_id' => null,
            'loss_id' => null,
        ]);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes have null values for optional fields
        $this->assertNull($document->producer_id);
        $this->assertNull($document->claimant_id);
        $this->assertNull($document->loss_id);
    }

    /**
     * Test that updating document metadata creates an audit log entry
     *
     * @return void
     */
    public function test_metadata_update_creates_audit_log(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Prepare updated metadata values
        $updatedData = [
            'description' => 'Updated document description',
        ];

        // Make a PUT request to update the document metadata
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updatedData);

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Get document history using auditLogger->getDocumentHistory
        $history = $this->auditLogger->getDocumentHistory($document->id);
        
        // Assert that the history contains at least one entry
        $this->assertNotNull($history);
        $this->assertGreaterThan(0, $history->count());
        
        // Assert that the most recent entry is for the edit action
        $latestAction = $history->first();
        $this->assertEquals('edit', $latestAction->action_type_name);
        
        // Assert that the entry has the correct user ID
        $this->assertEquals($user->id, $latestAction->created_by);
        
        // Assert that the entry description contains the changed fields
        $this->assertStringContainsString('Document Description', $latestAction->action_description);
    }

    /**
     * Test direct integration with the MetadataService for updating document metadata
     *
     * @return void
     */
    public function test_metadata_service_direct_integration(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Prepare updated metadata values
        $updatedData = [
            'description' => 'Updated via MetadataService',
        ];

        // Call metadataService->updateDocumentMetadata with document ID, updated values, and user ID
        $result = $this->metadataService->updateDocumentMetadata($document->id, $updatedData, $user->id);

        // Assert that the returned data contains the updated metadata values
        $this->assertIsArray($result);
        $this->assertArrayHasKey('description', $result);
        $this->assertEquals('Updated via MetadataService', $result['description']);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes match the updated values
        $this->assertEquals('Updated via MetadataService', $document->description);
    }

    /**
     * Test that attempting to update a nonexistent document returns a not found error
     *
     * @return void
     */
    public function test_nonexistent_document_returns_not_found(): void
    {
        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Prepare metadata values
        $updateData = [
            'description' => 'This document does not exist',
        ];

        // Make a PUT request to a non-existent document ID
        $response = $this->putJson("/api/documents/999999/metadata", $updateData);

        // Assert that the response has a 404 status code (Not Found)
        $response->assertStatus(404);
    }

    /**
     * Test that a partial update of metadata fields works correctly
     *
     * @return void
     */
    public function test_partial_metadata_update(): void
    {
        // Create a test document with initial metadata
        $document = $this->createDocumentWithMetadata();
        $initialDescription = $document->description;
        $initialPolicyId = $document->policy_id;
        $initialLossId = $document->loss_id;

        // Create a user with document editing permissions
        $user = $this->createUserWithDocumentPermissions();

        // Set the authenticated user for the test
        $this->actingAsUser($user);

        // Prepare partial metadata update (only updating some fields)
        $partialUpdate = [
            'description' => 'Partially updated description',
        ];

        // Make a PUT request with partial update
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $partialUpdate);

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response JSON contains the updated fields
        $response->assertJsonFragment([
            'description' => 'Partially updated description',
        ]);

        // Assert that the response JSON contains the unchanged fields with original values
        $response->assertJsonFragment([
            'policy_id' => $initialPolicyId,
            'loss_id' => $initialLossId,
        ]);

        // Refresh the document from the database
        $document->refresh();

        // Assert that the document attributes match the expected combination of updated and original values
        $this->assertEquals('Partially updated description', $document->description);
        $this->assertEquals($initialPolicyId, $document->policy_id);
        $this->assertEquals($initialLossId, $document->loss_id);
    }
}