<?php

namespace Tests\Integration\Database;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\Document;
use App\Models\MapDocumentAction;
use App\Services\AuditLogger;

/**
 * Integration test class for testing the audit repository functionality in the Documents View feature.
 * This class verifies that document actions are properly recorded, retrieved, and managed in the audit trail system.
 */
class AuditRepositoryTest extends TestCase
{
    use RefreshDatabase;

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
        
        // Create a new instance of the AuditLogger service
        $this->auditLogger = new AuditLogger();
        
        // Ensure action types exist in the database
        ActionType::firstOrCreate(
            ['name' => AuditLogger::ACTION_VIEW],
            [
                'description' => 'Document view action',
                'status_id' => 1,
                'created_by' => 1,
                'updated_by' => 1
            ]
        );
        
        ActionType::firstOrCreate(
            ['name' => AuditLogger::ACTION_EDIT],
            [
                'description' => 'Document edit action',
                'status_id' => 1,
                'created_by' => 1,
                'updated_by' => 1
            ]
        );
        
        ActionType::firstOrCreate(
            ['name' => AuditLogger::ACTION_PROCESS],
            [
                'description' => 'Document process action',
                'status_id' => 1,
                'created_by' => 1,
                'updated_by' => 1
            ]
        );
        
        ActionType::firstOrCreate(
            ['name' => AuditLogger::ACTION_UNPROCESS],
            [
                'description' => 'Document unprocess action',
                'status_id' => 1,
                'created_by' => 1,
                'updated_by' => 1
            ]
        );
        
        ActionType::firstOrCreate(
            ['name' => AuditLogger::ACTION_TRASH],
            [
                'description' => 'Document trash action',
                'status_id' => 1,
                'created_by' => 1,
                'updated_by' => 1
            ]
        );
        
        ActionType::firstOrCreate(
            ['name' => AuditLogger::ACTION_RESTORE],
            [
                'description' => 'Document restore action',
                'status_id' => 1,
                'created_by' => 1,
                'updated_by' => 1
            ]
        );
    }
    
    /**
     * Test that an action record can be created
     *
     * @return void
     */
    public function testCanCreateActionRecord()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Get the 'view' action type
        $actionType = ActionType::byName(AuditLogger::ACTION_VIEW)->first();
        $this->assertNotNull($actionType, 'Action type not found');
        
        // Create an action record
        $action = Action::create([
            'action_type_id' => $actionType->id,
            'description' => 'Document viewed',
            'status_id' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id
        ]);
        
        // Associate the action with the document
        MapDocumentAction::create([
            'document_id' => $document->id,
            'action_id' => $action->id,
            'description' => 'Document viewed',
            'status_id' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id
        ]);
        
        // Assert that the action was created and associated with the document
        $this->assertDatabaseHas('action', [
            'id' => $action->id,
            'action_type_id' => $actionType->id,
            'description' => 'Document viewed',
            'created_by' => $user->id
        ]);
        
        $this->assertDatabaseHas('map_document_action', [
            'document_id' => $document->id,
            'action_id' => $action->id
        ]);
        
        // Assert that the action can be retrieved through the document relationship
        $documentActions = $document->actions()->get();
        $this->assertCount(1, $documentActions);
        $this->assertEquals($action->id, $documentActions->first()->id);
    }
    
    /**
     * Test that actions for a document can be retrieved
     *
     * @return void
     */
    public function testCanRetrieveDocumentActions()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Get the 'view' action type
        $viewActionType = ActionType::byName(AuditLogger::ACTION_VIEW)->first();
        
        // Get the 'edit' action type
        $editActionType = ActionType::byName(AuditLogger::ACTION_EDIT)->first();
        
        // Create multiple actions for the document
        DB::transaction(function () use ($document, $user, $viewActionType, $editActionType) {
            // Create view action
            $viewAction = Action::create([
                'action_type_id' => $viewActionType->id,
                'description' => 'Document viewed',
                'status_id' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);
            
            MapDocumentAction::create([
                'document_id' => $document->id,
                'action_id' => $viewAction->id,
                'description' => 'Document viewed',
                'status_id' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);
            
            // Create edit action
            $editAction = Action::create([
                'action_type_id' => $editActionType->id,
                'description' => 'Document edited',
                'status_id' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);
            
            MapDocumentAction::create([
                'document_id' => $document->id,
                'action_id' => $editAction->id,
                'description' => 'Document edited',
                'status_id' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);
        });
        
        // Retrieve actions for the document
        $documentActions = $document->actions()->get();
        
        // Assert that correct number of actions were retrieved
        $this->assertCount(2, $documentActions);
        
        // Assert that actions have the correct attributes
        $this->assertEquals(
            ['Document viewed', 'Document edited'],
            $documentActions->pluck('description')->toArray()
        );
        
        // Assert we can retrieve the action type through the relationship
        $this->assertEquals(
            [$viewActionType->id, $editActionType->id],
            $documentActions->pluck('action_type_id')->toArray()
        );
    }
    
    /**
     * Test that a document view action can be logged
     *
     * @return void
     */
    public function testCanLogDocumentView()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Use the AuditLogger service to log a document view action
        $result = $this->auditLogger->logDocumentView($document->id, $user->id);
        
        // Assert that the action was logged successfully
        $this->assertTrue($result);
        
        // Retrieve the actions for the document
        $documentActions = $document->actions()->get();
        
        // Assert that a view action was created
        $this->assertCount(1, $documentActions);
        
        // Get the action
        $action = $documentActions->first();
        
        // Assert the action type is 'view'
        $this->assertEquals(
            ActionType::byName(AuditLogger::ACTION_VIEW)->first()->id,
            $action->action_type_id
        );
        
        // Assert the action has the correct description
        $this->assertEquals('Document viewed', $action->description);
        
        // Assert the action is associated with the correct user
        $this->assertEquals($user->id, $action->created_by);
    }
    
    /**
     * Test that a document edit action can be logged
     *
     * @return void
     */
    public function testCanLogDocumentEdit()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Define changes to document metadata
        $changes = [
            'policy_id' => [null, 123],
            'description' => ['Old description', 'New description']
        ];
        
        // Use the AuditLogger service to log a document edit action
        $result = $this->auditLogger->logDocumentEdit($document->id, $user->id, $changes);
        
        // Assert that the action was logged successfully
        $this->assertTrue($result);
        
        // Retrieve the actions for the document
        $documentActions = $document->actions()->get();
        
        // Assert that an edit action was created
        $this->assertCount(1, $documentActions);
        
        // Get the action
        $action = $documentActions->first();
        
        // Assert the action type is 'edit'
        $this->assertEquals(
            ActionType::byName(AuditLogger::ACTION_EDIT)->first()->id,
            $action->action_type_id
        );
        
        // Assert the action description contains the changes
        $this->assertStringContainsString('policy_id changed from', $action->description);
        $this->assertStringContainsString('description changed from', $action->description);
        
        // Assert the action is associated with the correct user
        $this->assertEquals($user->id, $action->created_by);
    }
    
    /**
     * Test that a document process action can be logged
     *
     * @return void
     */
    public function testCanLogDocumentProcess()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Use the AuditLogger service to log a document process action
        $result = $this->auditLogger->logDocumentProcess($document->id, $user->id);
        
        // Assert that the action was logged successfully
        $this->assertTrue($result);
        
        // Retrieve the actions for the document
        $documentActions = $document->actions()->get();
        
        // Assert that a process action was created
        $this->assertCount(1, $documentActions);
        
        // Get the action
        $action = $documentActions->first();
        
        // Assert the action type is 'process'
        $this->assertEquals(
            ActionType::byName(AuditLogger::ACTION_PROCESS)->first()->id,
            $action->action_type_id
        );
        
        // Assert the action has the correct description
        $this->assertEquals('Marked as processed', $action->description);
        
        // Assert the action is associated with the correct user
        $this->assertEquals($user->id, $action->created_by);
    }
    
    /**
     * Test that document history can be retrieved with pagination
     *
     * @return void
     */
    public function testCanRetrieveDocumentHistory()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Create multiple action records for the document
        $this->auditLogger->logDocumentView($document->id, $user->id);
        $this->auditLogger->logDocumentEdit($document->id, $user->id, ['description' => ['Old', 'New']]);
        $this->auditLogger->logDocumentProcess($document->id, $user->id);
        
        // Use the AuditLogger service to retrieve the document history with pagination
        $history = $this->auditLogger->getDocumentHistory($document->id, 2, 'desc');
        
        // Assert that the correct number of history records were retrieved
        $this->assertNotNull($history);
        $this->assertEquals(2, $history->count()); // 2 per page
        $this->assertEquals(3, $history->total()); // 3 total records
        
        // Assert that history records are in the correct order (newest first)
        $firstAction = $history->first();
        $this->assertEquals('Marked as processed', $firstAction->action_description);
        
        // Assert that the pagination metadata is correct
        $this->assertEquals(2, $history->lastPage());
        $this->assertEquals(2, $history->perPage());
    }
    
    /**
     * Test that actions can be filtered by action type
     *
     * @return void
     */
    public function testCanFilterActionsByType()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Create multiple action records of different types
        $this->auditLogger->logDocumentView($document->id, $user->id);
        $this->auditLogger->logDocumentEdit($document->id, $user->id, ['description' => ['Old', 'New']]);
        $this->auditLogger->logDocumentProcess($document->id, $user->id);
        
        // Get the 'view' action type ID
        $viewTypeId = ActionType::byName(AuditLogger::ACTION_VIEW)->first()->id;
        
        // Filter actions by type using the Action model
        $viewActions = Action::ofType($viewTypeId)
            ->join('map_document_action', 'action.id', '=', 'map_document_action.action_id')
            ->where('map_document_action.document_id', $document->id)
            ->get();
        
        // Assert that only actions of the specified type are returned
        $this->assertCount(1, $viewActions);
        $this->assertEquals($viewTypeId, $viewActions->first()->action_type_id);
        $this->assertEquals('Document viewed', $viewActions->first()->description);
    }
    
    /**
     * Test that actions can be filtered by user
     *
     * @return void
     */
    public function testCanFilterActionsByUser()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create multiple users
        $user1 = $this->createUser(['username' => 'user1']);
        $user2 = $this->createUser(['username' => 'user2']);
        
        // Create action records associated with different users
        $this->auditLogger->logDocumentView($document->id, $user1->id);
        $this->auditLogger->logDocumentEdit($document->id, $user2->id, ['description' => ['Old', 'New']]);
        
        // Filter actions by user using the Action model
        $user1Actions = Action::byUser($user1->id)
            ->join('map_document_action', 'action.id', '=', 'map_document_action.action_id')
            ->where('map_document_action.document_id', $document->id)
            ->get();
        
        // Assert that only actions by the specified user are returned
        $this->assertCount(1, $user1Actions);
        $this->assertEquals($user1->id, $user1Actions->first()->created_by);
        
        // Check for user2 as well
        $user2Actions = Action::byUser($user2->id)
            ->join('map_document_action', 'action.id', '=', 'map_document_action.action_id')
            ->where('map_document_action.document_id', $document->id)
            ->get();
        
        $this->assertCount(1, $user2Actions);
        $this->assertEquals($user2->id, $user2Actions->first()->created_by);
    }
    
    /**
     * Test that actions can be retrieved in chronological order
     *
     * @return void
     */
    public function testCanRetrieveChronologicalActions()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Create multiple action records with different timestamps
        DB::transaction(function () use ($document, $user) {
            // Log a view action
            $this->auditLogger->logDocumentView($document->id, $user->id);
            
            // Sleep to ensure different timestamps
            sleep(1);
            
            // Log an edit action
            $this->auditLogger->logDocumentEdit($document->id, $user->id, ['description' => ['Old', 'New']]);
            
            // Sleep to ensure different timestamps
            sleep(1);
            
            // Log a process action
            $this->auditLogger->logDocumentProcess($document->id, $user->id);
        });
        
        // Retrieve actions in chronological order (oldest first)
        $ascActions = MapDocumentAction::forDocument($document->id)
            ->join('action', 'map_document_action.action_id', '=', 'action.id')
            ->join('action_type', 'action.action_type_id', '=', 'action_type.id')
            ->select('action.*', 'action_type.name as action_type_name')
            ->orderBy('action.created_at', 'asc')
            ->get();
        
        // Assert that actions are returned in the correct order
        $this->assertCount(3, $ascActions);
        $this->assertEquals(AuditLogger::ACTION_VIEW, $ascActions->first()->action_type_name);
        $this->assertEquals(AuditLogger::ACTION_PROCESS, $ascActions->last()->action_type_name);
        
        // Retrieve actions in reverse chronological order (newest first)
        $descActions = MapDocumentAction::forDocument($document->id)
            ->join('action', 'map_document_action.action_id', '=', 'action.id')
            ->join('action_type', 'action.action_type_id', '=', 'action_type.id')
            ->select('action.*', 'action_type.name as action_type_name')
            ->orderBy('action.created_at', 'desc')
            ->get();
        
        // Assert that actions are returned in the correct reverse order
        $this->assertEquals(AuditLogger::ACTION_PROCESS, $descActions->first()->action_type_name);
        $this->assertEquals(AuditLogger::ACTION_VIEW, $descActions->last()->action_type_name);
    }
    
    /**
     * Test that action records have the correct relationships
     *
     * @return void
     */
    public function testActionRecordHasCorrectRelationships()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser(['username' => 'testuser']);
        
        // Get the 'view' action type
        $actionType = ActionType::byName(AuditLogger::ACTION_VIEW)->first();
        
        // Create an action record and associate it with the document
        $action = Action::create([
            'action_type_id' => $actionType->id,
            'description' => 'Document viewed',
            'status_id' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id
        ]);
        
        MapDocumentAction::create([
            'document_id' => $document->id,
            'action_id' => $action->id,
            'description' => 'Document viewed',
            'status_id' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id
        ]);
        
        // Retrieve the action with its relationships
        $action = Action::with(['documents', 'actionType', 'createdBy'])->find($action->id);
        
        // Assert that the action is associated with the correct document
        $this->assertCount(1, $action->documents);
        $this->assertEquals($document->id, $action->documents->first()->id);
        
        // Assert that the action has the correct action type
        $this->assertEquals($actionType->id, $action->action_type_id);
        $this->assertEquals($actionType->id, $action->actionType->id);
        $this->assertEquals(AuditLogger::ACTION_VIEW, $action->actionType->name);
        
        // Assert that the action is associated with the correct user
        $this->assertEquals($user->id, $action->created_by);
        $this->assertEquals($user->id, $action->createdBy->id);
        $this->assertEquals($user->username, $action->createdBy->username);
    }
}