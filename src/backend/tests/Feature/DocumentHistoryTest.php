<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use App\Services\AuditLogger;
use App\Models\Document;
use App\Models\Action;
use App\Models\MapDocumentAction;

/**
 * Feature test class for testing the document history functionality in the Documents View feature.
 * This test verifies that document actions are properly logged, retrieved, and displayed in the document history panel.
 */
class DocumentHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The audit logger service instance.
     *
     * @var \App\Services\AuditLogger
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
        $this->auditLogger = App::make(AuditLogger::class);
    }

    /**
     * Test that the document history endpoint returns an empty history for a newly created document
     *
     * @return void
     */
    public function test_document_history_endpoint_returns_empty_history_for_new_document(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        $this->actingAsUser($user);

        // Create a new document
        $document = $this->createDocument();

        // Make a GET request to the document history endpoint
        $response = $this->getJson("/api/documents/{$document->id}/history");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response data contains an empty data array
        $response->assertJsonCount(0, 'data');

        // Assert that the pagination metadata is correct
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
                'total'
            ]
        ]);
        $response->assertJsonPath('meta.total', 0);
    }

    /**
     * Test that the document history endpoint returns actions in chronological order
     *
     * @return void
     */
    public function test_document_history_endpoint_returns_actions_in_chronological_order(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        $this->actingAsUser($user);

        // Create a new document
        $document = $this->createDocument();

        // Log multiple document actions (view, edit, process)
        $this->auditLogger->logDocumentView($document->id, $user->id);
        $this->auditLogger->logDocumentEdit($document->id, $user->id, [
            'description' => ['Old description', 'New description']
        ]);
        $this->auditLogger->logDocumentProcess($document->id, $user->id);

        // Make a GET request to the document history endpoint
        $response = $this->getJson("/api/documents/{$document->id}/history");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response data contains the correct number of actions
        $response->assertJsonCount(3, 'data');

        // Assert that the actions are in reverse chronological order (newest first)
        $responseData = $response->json('data');
        $this->assertGreaterThan(
            $responseData[1]['action_timestamp'],
            $responseData[0]['action_timestamp'],
            'Actions are not in reverse chronological order'
        );
        $this->assertGreaterThan(
            $responseData[2]['action_timestamp'],
            $responseData[1]['action_timestamp'],
            'Actions are not in reverse chronological order'
        );

        // Assert that each action has the correct structure and data
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'action_description',
                    'action_type_name',
                    'username',
                    'first_name',
                    'last_name',
                    'action_timestamp'
                ]
            ]
        ]);

        // Verify the action types are in the expected order (process, edit, view)
        $this->assertEquals('process', $responseData[0]['action_type_name']);
        $this->assertEquals('edit', $responseData[1]['action_type_name']);
        $this->assertEquals('view', $responseData[2]['action_type_name']);
    }

    /**
     * Test that the document history endpoint supports pagination
     *
     * @return void
     */
    public function test_document_history_endpoint_supports_pagination(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        $this->actingAsUser($user);

        // Create a new document
        $document = $this->createDocument();

        // Log multiple document actions (more than the default per page)
        for ($i = 0; $i < 5; $i++) {
            $this->auditLogger->logDocumentView($document->id, $user->id);
        }

        // Make a GET request to the document history endpoint with page=1 and perPage=2
        $response = $this->getJson("/api/documents/{$document->id}/history?page=1&perPage=2");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response data contains exactly 2 actions
        $response->assertJsonCount(2, 'data');

        // Assert that the pagination metadata is correct (current_page, per_page, total)
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 5);
        $response->assertJsonPath('meta.last_page', 3);

        // Make a GET request to the document history endpoint with page=2 and perPage=2
        $response = $this->getJson("/api/documents/{$document->id}/history?page=2&perPage=2");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response data contains the next set of actions
        $response->assertJsonCount(2, 'data');

        // Assert that the pagination metadata is correct
        $response->assertJsonPath('meta.current_page', 2);
    }

    /**
     * Test that the document history endpoint returns a 404 status for a nonexistent document
     *
     * @return void
     */
    public function test_document_history_endpoint_returns_404_for_nonexistent_document(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        $this->actingAsUser($user);

        // Make a GET request to the document history endpoint with a nonexistent document ID
        $nonexistentId = 99999;
        $response = $this->getJson("/api/documents/{$nonexistentId}/history");

        // Assert that the response has a 404 status code
        $response->assertStatus(404);
    }

    /**
     * Test that the document history endpoint requires authentication
     *
     * @return void
     */
    public function test_document_history_endpoint_requires_authentication(): void
    {
        // Create a new document
        $document = $this->createDocument();

        // Make a GET request to the document history endpoint without authentication
        $response = $this->getJson("/api/documents/{$document->id}/history");

        // Assert that the response has a 401 status code
        $response->assertStatus(401);
    }

    /**
     * Test that the last edited endpoint returns the correct information
     *
     * @return void
     */
    public function test_last_edited_endpoint_returns_correct_information(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        $this->actingAsUser($user);

        // Create a new document
        $document = $this->createDocument();

        // Log a document edit action
        $this->auditLogger->logDocumentEdit($document->id, $user->id, [
            'description' => ['Old description', 'New description']
        ]);

        // Make a GET request to the last edited endpoint
        $response = $this->getJson("/api/documents/{$document->id}/last-edited");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response data contains the correct last edited information
        $response->assertJsonStructure([
            'timestamp',
            'user' => [
                'id',
                'username',
                'name'
            ],
            'action_type'
        ]);

        $responseData = $response->json();
        $this->assertEquals($user->id, $responseData['user']['id']);
        $this->assertEquals('edit', $responseData['action_type']);
    }

    /**
     * Test that the last edited endpoint returns empty data for a new document
     *
     * @return void
     */
    public function test_last_edited_endpoint_returns_empty_for_new_document(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        $this->actingAsUser($user);

        // Create a new document
        $document = $this->createDocument();

        // Make a GET request to the last edited endpoint
        $response = $this->getJson("/api/documents/{$document->id}/last-edited");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response data is empty
        $response->assertJson([]);
    }

    /**
     * Test that the filter by action type endpoint returns actions filtered by type
     *
     * @return void
     */
    public function test_filter_by_action_type_endpoint_returns_filtered_actions(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        $this->actingAsUser($user);

        // Create a new document
        $document = $this->createDocument();

        // Log multiple document actions of different types
        $this->auditLogger->logDocumentView($document->id, $user->id);
        $this->auditLogger->logDocumentEdit($document->id, $user->id, [
            'description' => ['Old description', 'New description']
        ]);
        $this->auditLogger->logDocumentProcess($document->id, $user->id);

        // Make a GET request to the filter by action type endpoint with a specific action type ID
        // First, get the action type ID for "edit"
        $editActionType = \App\Models\ActionType::byName('edit')->first();
        $this->assertNotNull($editActionType, 'Edit action type not found');

        $response = $this->getJson("/api/documents/{$document->id}/history?actionTypeId={$editActionType->id}");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response data contains only actions of the specified type
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('edit', $responseData[0]['action_type_name']);
    }

    /**
     * Test that the get action types endpoint returns all available action types
     *
     * @return void
     */
    public function test_get_action_types_endpoint_returns_available_action_types(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        $this->actingAsUser($user);

        // Create a new document (not strictly necessary but included for consistency)
        $document = $this->createDocument();

        // Make a GET request to the get action types endpoint
        $response = $this->getJson("/api/action-types");

        // Assert that the response has a 200 status code
        $response->assertStatus(200);

        // Assert that the response data contains all available action types
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description'
                ]
            ]
        ]);

        // At minimum, we should have the types used in the AuditLogger service
        $actionTypeNames = collect($response->json('data'))->pluck('name')->toArray();
        $expectedTypes = ['view', 'edit', 'process', 'unprocess', 'trash', 'restore'];
        
        foreach ($expectedTypes as $type) {
            $this->assertContains($type, $actionTypeNames, "Expected action type '{$type}' not found");
        }
    }

    /**
     * Test that document actions are logged correctly in the database
     *
     * @return void
     */
    public function test_document_actions_are_logged_correctly(): void
    {
        // Create a test user with document permissions
        $user = $this->createUserWithDocumentPermissions();

        // Create a new document
        $document = $this->createDocument();

        // Log a document view action
        $this->auditLogger->logDocumentView($document->id, $user->id);

        // Query the database to verify the action was logged correctly
        $viewAction = Action::query()
            ->forDocument($document->id)
            ->join('action_type', 'action.action_type_id', '=', 'action_type.id')
            ->where('action_type.name', 'view')
            ->orderBy('action.created_at', 'desc')
            ->first();

        // Assert that the action record exists with the correct action type and user attribution
        $this->assertNotNull($viewAction, 'View action not logged');
        $this->assertEquals($user->id, $viewAction->created_by);
        $this->assertEquals('Document viewed', $viewAction->description);

        // Log a document edit action
        $changes = ['description' => ['Old description', 'New description']];
        $this->auditLogger->logDocumentEdit($document->id, $user->id, $changes);

        // Query the database to verify the edit action was logged correctly
        $editAction = Action::query()
            ->forDocument($document->id)
            ->join('action_type', 'action.action_type_id', '=', 'action_type.id')
            ->where('action_type.name', 'edit')
            ->orderBy('action.created_at', 'desc')
            ->first();

        // Assert that the edit action record exists with the correct changes description
        $this->assertNotNull($editAction, 'Edit action not logged');
        $this->assertEquals($user->id, $editAction->created_by);
        $this->assertStringContainsString('description changed from', $editAction->description);

        // Log a document process action
        $this->auditLogger->logDocumentProcess($document->id, $user->id);

        // Query the database to verify the process action was logged correctly
        $processAction = Action::query()
            ->forDocument($document->id)
            ->join('action_type', 'action.action_type_id', '=', 'action_type.id')
            ->where('action_type.name', 'process')
            ->orderBy('action.created_at', 'desc')
            ->first();

        // Assert that the process action record exists with the correct action type
        $this->assertNotNull($processAction, 'Process action not logged');
        $this->assertEquals($user->id, $processAction->created_by);
        $this->assertEquals('Marked as processed', $processAction->description);
    }
}