<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuditLogger;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\MapDocumentAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogger $auditLogger;

    /**
     * Set up the test environment before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogger = new AuditLogger();
        
        // Seed the database with required action types
        ActionType::create(['name' => AuditLogger::ACTION_VIEW, 'description' => 'View document', 'status_id' => 1]);
        ActionType::create(['name' => AuditLogger::ACTION_EDIT, 'description' => 'Edit document', 'status_id' => 1]);
        ActionType::create(['name' => AuditLogger::ACTION_PROCESS, 'description' => 'Process document', 'status_id' => 1]);
        ActionType::create(['name' => AuditLogger::ACTION_UNPROCESS, 'description' => 'Unprocess document', 'status_id' => 1]);
        ActionType::create(['name' => AuditLogger::ACTION_TRASH, 'description' => 'Trash document', 'status_id' => 1]);
        ActionType::create(['name' => AuditLogger::ACTION_RESTORE, 'description' => 'Restore document', 'status_id' => 1]);
    }

    /**
     * Test that the logDocumentView method correctly logs a document view action
     *
     * @return void
     */
    public function testLogDocumentView(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Call the method under test
        $result = $this->auditLogger->logDocumentView($document->id, $user->id);
        
        // Assert that the method returns true
        $this->assertTrue($result);
        
        // Assert that a new action record was created
        $this->assertEquals(1, Action::count());
        $this->assertEquals(1, MapDocumentAction::count());
        
        // Assert that the action has the correct description
        $action = Action::first();
        $this->assertEquals('Document viewed', $action->description);
    }

    /**
     * Test that the logDocumentEdit method correctly logs a document edit action
     *
     * @return void
     */
    public function testLogDocumentEdit(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Create a changes array with field changes
        $changes = [
            'policy_number' => ['PLCY-12345', 'PLCY-12346'],
            'loss_sequence' => ['1 - Vehicle Accident', '2 - Property Damage']
        ];
        
        // Call the method under test
        $result = $this->auditLogger->logDocumentEdit($document->id, $user->id, $changes);
        
        // Assert that the method returns true
        $this->assertTrue($result);
        
        // Assert that a new action record was created
        $this->assertEquals(1, Action::count());
        $this->assertEquals(1, MapDocumentAction::count());
        
        // Assert that the action has the correct description containing the field changes
        $action = Action::first();
        $this->assertStringContainsString("policy_number changed from 'PLCY-12345' to 'PLCY-12346'", $action->description);
        $this->assertStringContainsString("loss_sequence changed from '1 - Vehicle Accident' to '2 - Property Damage'", $action->description);
    }

    /**
     * Test that the logDocumentProcess method correctly logs a document process action
     *
     * @return void
     */
    public function testLogDocumentProcess(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Call the method under test
        $result = $this->auditLogger->logDocumentProcess($document->id, $user->id);
        
        // Assert that the method returns true
        $this->assertTrue($result);
        
        // Assert that a new action record was created
        $this->assertEquals(1, Action::count());
        $this->assertEquals(1, MapDocumentAction::count());
        
        // Assert that the action has the correct description
        $action = Action::first();
        $this->assertEquals('Marked as processed', $action->description);
    }

    /**
     * Test that the logDocumentUnprocess method correctly logs a document unprocess action
     *
     * @return void
     */
    public function testLogDocumentUnprocess(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Call the method under test
        $result = $this->auditLogger->logDocumentUnprocess($document->id, $user->id);
        
        // Assert that the method returns true
        $this->assertTrue($result);
        
        // Assert that a new action record was created
        $this->assertEquals(1, Action::count());
        $this->assertEquals(1, MapDocumentAction::count());
        
        // Assert that the action has the correct description
        $action = Action::first();
        $this->assertEquals('Marked as unprocessed', $action->description);
    }

    /**
     * Test that the logDocumentTrash method correctly logs a document trash action
     *
     * @return void
     */
    public function testLogDocumentTrash(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Call the method under test
        $result = $this->auditLogger->logDocumentTrash($document->id, $user->id);
        
        // Assert that the method returns true
        $this->assertTrue($result);
        
        // Assert that a new action record was created
        $this->assertEquals(1, Action::count());
        $this->assertEquals(1, MapDocumentAction::count());
        
        // Assert that the action has the correct description
        $action = Action::first();
        $this->assertEquals('Moved to trash', $action->description);
    }

    /**
     * Test that the logDocumentRestore method correctly logs a document restore action
     *
     * @return void
     */
    public function testLogDocumentRestore(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Call the method under test
        $result = $this->auditLogger->logDocumentRestore($document->id, $user->id);
        
        // Assert that the method returns true
        $this->assertTrue($result);
        
        // Assert that a new action record was created
        $this->assertEquals(1, Action::count());
        $this->assertEquals(1, MapDocumentAction::count());
        
        // Assert that the action has the correct description
        $action = Action::first();
        $this->assertEquals('Restored from trash', $action->description);
    }

    /**
     * Test that the logDocumentAction method correctly logs a custom document action
     *
     * @return void
     */
    public function testLogDocumentAction(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Create a custom action type for testing
        ActionType::create(['name' => 'custom', 'description' => 'Custom action', 'status_id' => 1]);
        
        // Call the method under test
        $result = $this->auditLogger->logDocumentAction($document->id, $user->id, 'custom', 'Custom action description');
        
        // Assert that the method returns true
        $this->assertTrue($result);
        
        // Assert that a new action record was created
        $this->assertEquals(1, Action::count());
        $this->assertEquals(1, MapDocumentAction::count());
        
        // Assert that the action has the correct description
        $action = Action::first();
        $this->assertEquals('Custom action description', $action->description);
    }

    /**
     * Test that the getDocumentHistory method correctly retrieves document action history
     *
     * @return void
     */
    public function testGetDocumentHistory(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Log multiple actions for the document
        $this->auditLogger->logDocumentView($document->id, $user->id);
        $this->auditLogger->logDocumentEdit($document->id, $user->id, ['policy_number' => ['PLCY-12345', 'PLCY-12346']]);
        $this->auditLogger->logDocumentProcess($document->id, $user->id);
        
        // Get history (default order: desc)
        $history = $this->auditLogger->getDocumentHistory($document->id);
        
        // Assert that the returned collection has the correct number of items
        $this->assertNotNull($history);
        $this->assertEquals(3, $history->count());
        
        // Assert that the items are in the correct order (most recent first by default)
        $this->assertEquals('process', $history->first()->action_type_name);
        
        // Test pagination by specifying perPage parameter
        $historyPaginated = $this->auditLogger->getDocumentHistory($document->id, 2);
        $this->assertEquals(2, $historyPaginated->count());
        
        // Test ordering by specifying direction parameter
        $historyAsc = $this->auditLogger->getDocumentHistory($document->id, 10, 'asc');
        $this->assertEquals('view', $historyAsc->first()->action_type_name);
    }

    /**
     * Test that the getLastDocumentAction method correctly retrieves the most recent document action
     *
     * @return void
     */
    public function testGetLastDocumentAction(): void
    {
        // Create a test document and user
        $document = $this->createDocument();
        $user = $this->createUser();
        
        // Log multiple actions for the document
        $this->auditLogger->logDocumentView($document->id, $user->id);
        $this->auditLogger->logDocumentEdit($document->id, $user->id, ['policy_number' => ['PLCY-12345', 'PLCY-12346']]);
        $this->auditLogger->logDocumentProcess($document->id, $user->id);
        
        // Get the last action
        $lastAction = $this->auditLogger->getLastDocumentAction($document->id);
        
        // Assert that the returned action is the most recent one
        $this->assertNotNull($lastAction);
        $this->assertEquals('process', $lastAction['action_type']);
        $this->assertEquals('Marked as processed', $lastAction['description']);
        $this->assertEquals($user->id, $lastAction['user']['id']);
        $this->assertEquals($user->username, $lastAction['user']['username']);
    }

    /**
     * Test that the logDocumentView method returns false when given an invalid document ID
     *
     * @return void
     */
    public function testLogDocumentViewWithInvalidDocument(): void
    {
        // Create a test user
        $user = $this->createUser();
        
        // Call the method with a non-existent document ID
        $result = $this->auditLogger->logDocumentView(9999, $user->id);
        
        // Assert that the method returns false
        $this->assertFalse($result);
        
        // Assert that no new action records were created
        $this->assertEquals(0, Action::count());
    }

    /**
     * Test that the logDocumentView method returns false when given an invalid user ID
     *
     * @return void
     */
    public function testLogDocumentViewWithInvalidUser(): void
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Call the method with a non-existent user ID
        $result = $this->auditLogger->logDocumentView($document->id, 9999);
        
        // Assert that the method returns false
        $this->assertFalse($result);
        
        // Assert that no new action records were created
        $this->assertEquals(0, Action::count());
    }

    /**
     * Test that the getDocumentHistory method returns an empty collection when given an invalid document ID
     *
     * @return void
     */
    public function testGetDocumentHistoryWithInvalidDocument(): void
    {
        // Call the method with a non-existent document ID
        $history = $this->auditLogger->getDocumentHistory(9999);
        
        // Assert that the returned collection is empty
        $this->assertNull($history);
    }

    /**
     * Test that the formatChangesDescription method correctly formats an array of changes
     *
     * @return void
     */
    public function testFormatChangesDescription(): void
    {
        // Create a changes array with multiple field changes
        $changes = [
            'policy_number' => ['PLCY-12345', 'PLCY-12346'],
            'loss_sequence' => ['1 - Vehicle Accident', '2 - Property Damage'],
            'empty_field' => [null, 'New Value'],
            'cleared_field' => ['Old Value', null]
        ];
        
        // Use reflection to access the private formatChangesDescription method
        $reflection = new ReflectionClass(AuditLogger::class);
        $method = $reflection->getMethod('formatChangesDescription');
        $method->setAccessible(true);
        
        // Call the method with the changes array
        $result = $method->invoke($this->auditLogger, $changes);
        
        // Assert that the returned string contains all the expected changes in the correct format
        $this->assertStringContainsString("policy_number changed from 'PLCY-12345' to 'PLCY-12346'", $result);
        $this->assertStringContainsString("loss_sequence changed from '1 - Vehicle Accident' to '2 - Property Damage'", $result);
        $this->assertStringContainsString("empty_field changed from '(empty)' to 'New Value'", $result);
        $this->assertStringContainsString("cleared_field changed from 'Old Value' to '(empty)'", $result);
    }
}