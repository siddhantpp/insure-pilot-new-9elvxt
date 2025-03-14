<?php

namespace Tests\Integration\Database;

use Tests\TestCase;
use App\Models\Document;
use App\Models\File;
use App\Models\MapDocumentFile;
use App\Models\Action;
use App\Models\MapDocumentAction;
use Illuminate\Support\Facades\DB;

class DocumentRepositoryTest extends TestCase
{
    /**
     * Set up the test environment before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Set up any specific database test dependencies
    }

    /**
     * Test that a document can be created in the database
     *
     * @return void
     */
    public function test_can_create_document()
    {
        // Create a document using the createDocument helper method
        $document = $this->createDocument([
            'name' => 'Test Document',
            'description' => 'This is a test document'
        ]);

        // Retrieve the document from the database using Document::find()
        $retrieved = Document::find($document->id);
        
        // Assert that the document exists in the database
        $this->assertNotNull($retrieved);
        
        // Assert that the document attributes match the expected values
        $this->assertDocumentEquals($document, $retrieved);
        $this->assertEquals('Test Document', $retrieved->name);
        $this->assertEquals('This is a test document', $retrieved->description);
    }

    /**
     * Test that a document can be updated in the database
     *
     * @return void
     */
    public function test_can_update_document()
    {
        // Create a document using the createDocument helper method
        $document = $this->createDocument();
        
        // Update the document attributes
        $document->name = 'Updated Document Name';
        $document->description = 'Updated document description';
        $document->save();
        
        // Retrieve the fresh document from the database
        $updated = Document::find($document->id);
        
        // Assert that the document attributes have been updated correctly
        $this->assertEquals('Updated Document Name', $updated->name);
        $this->assertEquals('Updated document description', $updated->description);
    }

    /**
     * Test that a document can be soft deleted (trashed)
     *
     * @return void
     */
    public function test_can_delete_document()
    {
        // Create a document using the createDocument helper method
        $document = $this->createDocument();
        
        // Call moveToTrash() on the document
        $document->moveToTrash();
        
        // Assert that the document is not found with a regular query
        $this->assertNull(Document::find($document->id));
        
        // Assert that the document is found when including trashed documents
        $trashed = Document::withTrashed()->find($document->id);
        $this->assertNotNull($trashed);
        
        // Assert that the document status is set to STATUS_TRASHED
        $this->assertEquals(Document::STATUS_TRASHED, $trashed->status_id);
    }

    /**
     * Test that a trashed document can be restored
     *
     * @return void
     */
    public function test_can_restore_document()
    {
        // Create a document using the createDocument helper method
        $document = $this->createDocument();
        
        // Call moveToTrash() on the document
        $document->moveToTrash();
        
        // Call restore() on the document
        $document = Document::withTrashed()->find($document->id);
        $document->restore();
        
        // Assert that the document is found with a regular query
        $restored = Document::find($document->id);
        $this->assertNotNull($restored);
        
        // Assert that the document status is set to STATUS_UNPROCESSED
        $this->assertEquals(Document::STATUS_UNPROCESSED, $restored->status_id);
    }

    /**
     * Test that a document can be marked as processed
     *
     * @return void
     */
    public function test_can_mark_document_as_processed()
    {
        // Create a document using the createDocument helper method
        $document = $this->createDocument();
        
        // Call markAsProcessed() on the document
        $document->markAsProcessed();
        
        // Assert that the document status is set to STATUS_PROCESSED
        $this->assertEquals(Document::STATUS_PROCESSED, $document->status_id);
        
        // Assert that the document is returned by the processed() scope
        $processed = Document::processed()->find($document->id);
        $this->assertNotNull($processed);
    }

    /**
     * Test that a processed document can be marked as unprocessed
     *
     * @return void
     */
    public function test_can_mark_document_as_unprocessed()
    {
        // Create a document using the createDocument helper method
        $document = $this->createDocument();
        
        // Call markAsProcessed() on the document
        $document->markAsProcessed();
        
        // Call markAsUnprocessed() on the document
        $document->markAsUnprocessed();
        
        // Assert that the document status is set to STATUS_UNPROCESSED
        $this->assertEquals(Document::STATUS_UNPROCESSED, $document->status_id);
        
        // Assert that the document is returned by the unprocessed() scope
        $unprocessed = Document::unprocessed()->find($document->id);
        $this->assertNotNull($unprocessed);
    }

    /**
     * Test the relationship between documents and files
     *
     * @return void
     */
    public function test_document_file_relationship()
    {
        // Create a document with an associated file using createDocumentWithFile
        $document = $this->createDocumentWithFile();
        
        // Retrieve the document with the files relationship loaded
        $document = Document::with('files')->find($document->id);
        
        // Assert that the files relationship exists
        $this->assertNotEmpty($document->files);
        $this->assertEquals(1, $document->files->count());
        
        // Assert that the file attributes match the expected values
        $this->assertEquals('test_document.pdf', $document->files->first()->name);
        $this->assertEquals('application/pdf', $document->files->first()->mime_type);
        
        // Assert that the mainFile accessor returns the correct file
        $this->assertNotNull($document->main_file);
        $this->assertEquals('test_document.pdf', $document->main_file->name);
        $this->assertNotNull($document->file_url);
    }

    /**
     * Test the relationship between documents and actions (history)
     *
     * @return void
     */
    public function test_document_action_relationship()
    {
        // Create a document using the createDocument helper method
        $document = $this->createDocument();
        
        // Create an action using the Action factory
        $action = Action::factory()->create([
            'description' => 'Test action',
            'action_type_id' => 1, // Assuming 1 is a valid action type
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Associate the action with the document using MapDocumentAction
        MapDocumentAction::create([
            'document_id' => $document->id,
            'action_id' => $action->id,
            'description' => 'Test document action',
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Retrieve the document with the actions relationship loaded
        $document = Document::with('actions')->find($document->id);
        
        // Assert that the actions relationship exists
        $this->assertNotEmpty($document->actions);
        $this->assertEquals(1, $document->actions->count());
        
        // Assert that the action attributes match the expected values
        $this->assertEquals('Test action', $document->actions->first()->description);
    }

    /**
     * Test the relationships between documents and metadata entities (policy, loss, claimant, producer)
     *
     * @return void
     */
    public function test_document_metadata_relationships()
    {
        // Create a document with complete metadata using createDocumentWithMetadata
        $document = $this->createDocumentWithMetadata();
        
        // Retrieve the document with all relationships loaded
        $document = Document::with(['policy', 'loss', 'claimant', 'producer'])->find($document->id);
        
        // Assert that the policy relationship exists and has the correct attributes
        $this->assertNotNull($document->policy);
        $this->assertEquals('PLCY-12345', $document->policy->number);
        
        // Assert that the loss relationship exists and has the correct attributes
        $this->assertNotNull($document->loss);
        $this->assertEquals('Vehicle Accident', $document->loss->name);
        
        // Assert that the claimant relationship exists and has the correct attributes
        $this->assertNotNull($document->claimant);
        
        // Assert that the producer relationship exists and has the correct attributes
        $this->assertNotNull($document->producer);
        $this->assertEquals('AG-789456', $document->producer->number);
        $this->assertEquals('Test Producer', $document->producer->name);
    }

    /**
     * Test the query scopes defined on the Document model
     *
     * @return void
     */
    public function test_document_query_scopes()
    {
        // Create multiple documents with different statuses and relationships
        $processed = $this->createDocument(['status_id' => Document::STATUS_PROCESSED]);
        $unprocessed = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);
        
        // Create a trashed document
        $trashed = $this->createDocument();
        $trashed->moveToTrash();
        
        // Create a document with complete metadata
        $withMetadata = $this->createDocumentWithMetadata();
        
        // Test the processed() scope returns only processed documents
        $processedDocs = Document::processed()->get();
        $this->assertEquals(1, $processedDocs->count());
        $this->assertEquals($processed->id, $processedDocs->first()->id);
        
        // Test the unprocessed() scope returns only unprocessed documents
        $unprocessedDocs = Document::unprocessed()->get();
        $this->assertGreaterThanOrEqual(2, $unprocessedDocs->count()); // Includes the withMetadata document
        
        // Test the trashed() scope returns only trashed documents
        $trashedDocs = Document::trashed()->get();
        $this->assertEquals(1, $trashedDocs->count());
        $this->assertEquals($trashed->id, $trashedDocs->first()->id);
        
        // Test the forPolicy() scope returns only documents for a specific policy
        $policyDocs = Document::forPolicy($withMetadata->policy_id)->get();
        $this->assertGreaterThan(0, $policyDocs->count());
        $this->assertTrue($policyDocs->contains('id', $withMetadata->id));
        
        // Test the forLoss() scope returns only documents for a specific loss
        $lossDocs = Document::forLoss($withMetadata->loss_id)->get();
        $this->assertGreaterThan(0, $lossDocs->count());
        $this->assertTrue($lossDocs->contains('id', $withMetadata->id));
        
        // Test the forClaimant() scope returns only documents for a specific claimant
        $claimantDocs = Document::forClaimant($withMetadata->claimant_id)->get();
        $this->assertGreaterThan(0, $claimantDocs->count());
        $this->assertTrue($claimantDocs->contains('id', $withMetadata->id));
        
        // Test the forProducer() scope returns only documents for a specific producer
        $producerDocs = Document::forProducer($withMetadata->producer_id)->get();
        $this->assertGreaterThan(0, $producerDocs->count());
        $this->assertTrue($producerDocs->contains('id', $withMetadata->id));
        
        // Test the search() scope returns documents matching the search criteria
        $searchDocs = Document::search($withMetadata->name)->get();
        $this->assertNotEmpty($searchDocs);
        $this->assertTrue($searchDocs->contains('id', $withMetadata->id));
    }

    /**
     * Test the accessor methods defined on the Document model
     *
     * @return void
     */
    public function test_document_accessors()
    {
        // Create a document with complete metadata using createDocumentWithMetadata
        $document = $this->createDocumentWithMetadata();
        
        // Test the isProcessed accessor returns the correct value
        $this->assertFalse($document->is_processed);
        $document->markAsProcessed();
        $document->refresh();
        $this->assertTrue($document->is_processed);
        
        // Test the isTrashed accessor returns the correct value
        $this->assertFalse($document->is_trashed);
        $document->moveToTrash();
        $document = Document::withTrashed()->find($document->id);
        $this->assertTrue($document->is_trashed);
        
        // Restore the document for further testing
        $document->restore();
        $document = Document::find($document->id);
        
        // Test the policyNumber accessor returns the correct value
        $this->assertEquals('PLCY-12345', $document->policy_number);
        
        // Test the lossSequence accessor returns the correct value
        $this->assertNotNull($document->loss_sequence);
        $this->assertStringContainsString('Vehicle Accident', $document->loss_sequence);
        
        // Test the claimantName accessor returns the correct value
        $this->assertNotNull($document->claimant_name);
        
        // Test the producerNumber accessor returns the correct value
        $this->assertEquals('AG-789456', $document->producer_number);
        
        // Test the fileUrl accessor returns the correct value
        $documentWithFile = $this->createDocumentWithFile();
        $this->assertNotNull($documentWithFile->file_url);
    }

    /**
     * Test that database transactions properly rollback on failure
     *
     * @return void
     */
    public function test_document_transaction_rollback()
    {
        // Begin a database transaction
        DB::beginTransaction();
        
        // Create a document within the transaction
        $document = $this->createDocument();
        $documentId = $document->id;
        
        // Intentionally cause a failure (e.g., validation error)
        DB::rollBack();
        
        // Assert that the document was not persisted to the database
        $this->assertNull(Document::find($documentId));
    }
}