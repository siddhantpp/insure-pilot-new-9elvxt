<?php

namespace Tests\Unit\DocumentViewer;

use Tests\TestCase;
use App\Services\DocumentManager;
use App\Services\FileStorage;
use App\Services\MetadataService;
use App\Services\AuditLogger;
use App\Services\PdfViewerService;
use App\Models\Document;
use Mockery; // mockery/mockery ^1.5
use Illuminate\Pagination\LengthAwarePaginator; // laravel/framework ^10.0
use Illuminate\Support\Collection; // laravel/framework ^10.0

class DocumentManagerTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var FileStorage
     */
    protected $fileStorage;

    /**
     * @var MetadataService
     */
    protected $metadataService;

    /**
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * @var PdfViewerService
     */
    protected $pdfViewerService;

    /**
     * Default constructor inherited from TestCase
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set up the test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock instances for FileStorage, MetadataService, AuditLogger, and PdfViewerService
        $this->fileStorage = Mockery::mock(FileStorage::class);
        $this->metadataService = Mockery::mock(MetadataService::class);
        $this->auditLogger = Mockery::mock(AuditLogger::class);
        $this->pdfViewerService = Mockery::mock(PdfViewerService::class);

        // Create a DocumentManager instance with the mock dependencies
        $this->documentManager = new DocumentManager(
            $this->fileStorage,
            $this->metadataService,
            $this->auditLogger,
            $this->pdfViewerService
        );

        // Configure common mock expectations
    }

    /**
     * Clean up the test environment after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that getDocument retrieves a document with its metadata
     */
    public function testGetDocument(): void
    {
        // Create a test document with metadata
        $document = $this->createDocumentWithMetadata();

        // Configure metadataService mock to return the document metadata
        $this->metadataService->shouldReceive('getDocumentMetadata')
            ->with($document->id)
            ->once()
            ->andReturn([
                'id' => $document->id,
                'name' => $document->name,
                'description' => $document->description,
            ]);

        // Call documentManager->getDocument with the document ID
        $retrievedDocument = $this->documentManager->getDocument($document->id);

        // Assert that the returned document matches the expected document
        $this->assertEquals($document->id, $retrievedDocument->id);
        $this->assertEquals($document->name, $retrievedDocument->name);
        $this->assertEquals($document->description, $retrievedDocument->description);

        // Verify that metadataService->getDocumentMetadata was called with the correct document ID
        $this->metadataService->shouldHaveReceived('getDocumentMetadata')->with($document->id);
    }

    /**
     * Test that getDocument returns null when the document is not found
     */
    public function testGetDocumentReturnsNullWhenDocumentNotFound(): void
    {
        // Configure metadataService mock to return null for a non-existent document ID
        $this->metadataService->shouldReceive('getDocumentMetadata')
            ->with(999)
            ->once()
            ->andReturn(null);

        // Call documentManager->getDocument with a non-existent document ID
        $retrievedDocument = $this->documentManager->getDocument(999);

        // Assert that the result is null
        $this->assertNull($retrievedDocument);

        // Verify that metadataService->getDocumentMetadata was called with the correct document ID
        $this->metadataService->shouldHaveReceived('getDocumentMetadata')->with(999);
    }

    /**
     * Test that getDocuments returns a paginated list of documents
     */
    public function testGetDocuments(): void
    {
        // Create multiple test documents
        $documents = Document::factory()->count(3)->create();

        // Call documentManager->getDocuments with optional filters
        $paginatedDocuments = $this->documentManager->getDocuments();

        // Assert that the returned result is a LengthAwarePaginator instance
        $this->assertInstanceOf(LengthAwarePaginator::class, $paginatedDocuments);

        // Assert that the paginator contains the expected number of documents
        $this->assertCount(3, $paginatedDocuments->items());

        // Assert that the documents in the paginator match the expected documents
        foreach ($documents as $document) {
            $this->assertTrue($paginatedDocuments->contains($document));
        }
    }

    /**
     * Test that updateDocument updates a document's metadata
     */
    public function testUpdateDocument(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a test user
        $user = $this->createUser();

        // Prepare update data (policy_id, loss_id, etc.)
        $updateData = [
            'policy_id' => 123,
            'loss_id' => 456,
            'description' => 'Updated description',
        ];

        // Configure metadataService mock to validate and update the document metadata
        $this->metadataService->shouldReceive('validateMetadataRelationships')
            ->with($updateData)
            ->once()
            ->andReturn([]);

        $this->metadataService->shouldReceive('updateDocumentMetadata')
            ->with($document->id, $updateData, $user->id)
            ->once()
            ->andReturn(true);

        // Call documentManager->updateDocument with document ID, update data, and user ID
        $updatedDocument = $this->documentManager->updateDocument($document->id, $updateData, $user->id);

        // Assert that the returned document has the updated metadata
        $this->assertEquals($updateData['policy_id'], $updatedDocument->policy_id);
        $this->assertEquals($updateData['loss_id'], $updatedDocument->loss_id);
        $this->assertEquals($updateData['description'], $updatedDocument->description);

        // Verify that metadataService->validateMetadataRelationships and updateDocumentMetadata were called with the correct parameters
        $this->metadataService->shouldHaveReceived('validateMetadataRelationships')->with($updateData);
        $this->metadataService->shouldHaveReceived('updateDocumentMetadata')->with($document->id, $updateData, $user->id);
    }

    /**
     * Test that updateDocument returns null when the document is not found
     */
    public function testUpdateDocumentReturnsNullWhenDocumentNotFound(): void
    {
        // Configure metadataService mock to return null for a non-existent document ID
        $this->metadataService->shouldReceive('validateMetadataRelationships')
            ->never();

        // Call documentManager->updateDocument with a non-existent document ID, update data, and user ID
        $updatedDocument = $this->documentManager->updateDocument(999, [], 1);

        // Assert that the result is null
        $this->assertNull($updatedDocument);

        // Verify that metadataService->validateMetadataRelationships was not called
        $this->metadataService->shouldNotHaveReceived('validateMetadataRelationships');
    }

    /**
     * Test that updateDocument returns null when metadata validation fails
     */
    public function testUpdateDocumentReturnsNullWhenValidationFails(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a test user
        $user = $this->createUser();

        // Prepare invalid update data
        $invalidUpdateData = [
            'policy_id' => 123,
            'loss_id' => 456,
        ];

        // Configure metadataService mock to return validation errors
        $this->metadataService->shouldReceive('validateMetadataRelationships')
            ->with($invalidUpdateData)
            ->once()
            ->andReturn(['policy_id' => 'Invalid policy ID']);

        $this->metadataService->shouldReceive('updateDocumentMetadata')
            ->never();

        // Call documentManager->updateDocument with document ID, invalid update data, and user ID
        $updatedDocument = $this->documentManager->updateDocument($document->id, $invalidUpdateData, $user->id);

        // Assert that the result is null
        $this->assertNull($updatedDocument);

        // Verify that metadataService->validateMetadataRelationships was called but updateDocumentMetadata was not called
        $this->metadataService->shouldHaveReceived('validateMetadataRelationships')->with($invalidUpdateData);
        $this->metadataService->shouldNotHaveReceived('updateDocumentMetadata');
    }

    /**
     * Test that processDocument marks a document as processed
     */
    public function testProcessDocument(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a test user
        $user = $this->createUser();

        // Configure auditLogger mock to log the process action
        $this->auditLogger->shouldReceive('logDocumentProcess')
            ->with($document->id, $user->id)
            ->once()
            ->andReturn(true);

        // Call documentManager->processDocument with document ID, true (process), and user ID
        $processedDocument = $this->documentManager->processDocument($document->id, true, $user->id);

        // Assert that the returned document is marked as processed
        $this->assertTrue($processedDocument->isProcessed());

        // Verify that auditLogger->logDocumentProcess was called with the correct parameters
        $this->auditLogger->shouldHaveReceived('logDocumentProcess')->with($document->id, $user->id);
    }

    /**
     * Test that processDocument marks a document as unprocessed
     */
    public function testUnprocessDocument(): void
    {
        // Create a test document that is already processed
        $document = $this->createDocument(['status_id' => Document::STATUS_PROCESSED]);

        // Create a test user
        $user = $this->createUser();

        // Configure auditLogger mock to log the unprocess action
        $this->auditLogger->shouldReceive('logDocumentUnprocess')
            ->with($document->id, $user->id)
            ->once()
            ->andReturn(true);

        // Call documentManager->processDocument with document ID, false (unprocess), and user ID
        $unprocessedDocument = $this->documentManager->processDocument($document->id, false, $user->id);

        // Assert that the returned document is marked as unprocessed
        $this->assertFalse($unprocessedDocument->isProcessed());

        // Verify that auditLogger->logDocumentUnprocess was called with the correct parameters
        $this->auditLogger->shouldHaveReceived('logDocumentUnprocess')->with($document->id, $user->id);
    }

    /**
     * Test that processDocument returns null when the document is not found
     */
    public function testProcessDocumentReturnsNullWhenDocumentNotFound(): void
    {
        // Call documentManager->processDocument with a non-existent document ID, true, and user ID
        $processedDocument = $this->documentManager->processDocument(999, true, 1);

        // Assert that the result is null
        $this->assertNull($processedDocument);

        // Verify that auditLogger->logDocumentProcess was not called
        $this->auditLogger->shouldNotHaveReceived('logDocumentProcess');
    }

    /**
     * Test that trashDocument moves a document to trash
     */
    public function testTrashDocument(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a test user
        $user = $this->createUser();

        // Configure auditLogger mock to log the trash action
        $this->auditLogger->shouldReceive('logDocumentTrash')
            ->with($document->id, $user->id)
            ->once()
            ->andReturn(true);

        // Call documentManager->trashDocument with document ID and user ID
        $trashed = $this->documentManager->trashDocument($document->id, $user->id);

        // Assert that the result is true
        $this->assertTrue($trashed);

        // Verify that auditLogger->logDocumentTrash was called with the correct parameters
        $this->auditLogger->shouldHaveReceived('logDocumentTrash')->with($document->id, $user->id);
    }

    /**
     * Test that trashDocument returns false when the document is not found
     */
    public function testTrashDocumentReturnsFalseWhenDocumentNotFound(): void
    {
        // Call documentManager->trashDocument with a non-existent document ID and user ID
        $trashed = $this->documentManager->trashDocument(999, 1);

        // Assert that the result is false
        $this->assertFalse($trashed);

        // Verify that auditLogger->logDocumentTrash was not called
        $this->auditLogger->shouldNotHaveReceived('logDocumentTrash');
    }

    /**
     * Test that getDocumentFile retrieves a document file
     */
    public function testGetDocumentFile(): void
    {
        // Create a test document with a file
        $document = $this->createDocumentWithFile();

        // Configure fileStorage mock to return the file content
        $this->fileStorage->shouldReceive('getFile')
            ->with($document->files->first()->id)
            ->once()
            ->andReturn('File content');

        // Call documentManager->getDocumentFile with the document ID
        $fileContent = $this->documentManager->getDocumentFile($document->id);

        // Assert that the returned file content matches the expected content
        $this->assertEquals('File content', $fileContent);

        // Verify that fileStorage->getFile was called with the correct file ID
        $this->fileStorage->shouldHaveReceived('getFile')->with($document->files->first()->id);
    }

    /**
     * Test that getDocumentFile returns null when the document is not found
     */
    public function testGetDocumentFileReturnsNullWhenDocumentNotFound(): void
    {
        // Call documentManager->getDocumentFile with a non-existent document ID
        $fileContent = $this->documentManager->getDocumentFile(999);

        // Assert that the result is null
        $this->assertNull($fileContent);

        // Verify that fileStorage->getFile was not called
        $this->fileStorage->shouldNotHaveReceived('getFile');
    }

    /**
     * Test that getDocumentFile returns null when the document has no file
     */
    public function testGetDocumentFileReturnsNullWhenFileNotFound(): void
    {
        // Create a test document without a file
        $document = $this->createDocument();

        // Call documentManager->getDocumentFile with the document ID
        $fileContent = $this->documentManager->getDocumentFile($document->id);

        // Assert that the result is null
        $this->assertNull($fileContent);

        // Verify that fileStorage->getFile was not called
        $this->fileStorage->shouldNotHaveReceived('getFile');
    }

    /**
     * Test that getDocumentFileUrl generates a URL for a document file
     */
    public function testGetDocumentFileUrl(): void
    {
        // Create a test document with a file
        $document = $this->createDocumentWithFile();

        // Configure fileStorage mock to return a file URL
        $this->fileStorage->shouldReceive('getFileUrl')
            ->with($document->files->first()->id, 60)
            ->once()
            ->andReturn('http://example.com/file.pdf');

        // Call documentManager->getDocumentFileUrl with the document ID and expiration minutes
        $fileUrl = $this->documentManager->getDocumentFileUrl($document->id, 60);

        // Assert that the returned URL matches the expected URL
        $this->assertEquals('http://example.com/file.pdf', $fileUrl);

        // Verify that fileStorage->getFileUrl was called with the correct file ID and expiration minutes
        $this->fileStorage->shouldHaveReceived('getFileUrl')->with($document->files->first()->id, 60);
    }

    /**
     * Test that getDocumentViewerUrl generates a URL for viewing a document
     */
    public function testGetDocumentViewerUrl(): void
    {
        // Create a test document with a file
        $document = $this->createDocumentWithFile();

        // Configure pdfViewerService mock to return a viewer URL
        $this->pdfViewerService->shouldReceive('getDocumentViewUrl')
            ->with($document->files->first()->id, 60)
            ->once()
            ->andReturn('http://example.com/viewer?file=http://example.com/file.pdf');

        // Call documentManager->getDocumentViewerUrl with the document ID and expiration minutes
        $viewerUrl = $this->documentManager->getDocumentViewerUrl($document->id, 60);

        // Assert that the returned URL matches the expected URL
        $this->assertEquals('http://example.com/viewer?file=http://example.com/file.pdf', $viewerUrl);

        // Verify that pdfViewerService->getDocumentViewUrl was called with the correct file ID and expiration minutes
        $this->pdfViewerService->shouldHaveReceived('getDocumentViewUrl')->with($document->files->first()->id, 60);
    }

    /**
     * Test that getDocumentViewerConfig retrieves the PDF viewer configuration
     */
    public function testGetDocumentViewerConfig(): void
    {
        // Create a test document with a file
        $document = $this->createDocumentWithFile();

        // Configure pdfViewerService mock to return a viewer configuration
        $this->pdfViewerService->shouldReceive('getViewerConfig')
            ->with($document->files->first()->id)
            ->once()
            ->andReturn(['config' => 'value']);

        // Call documentManager->getDocumentViewerConfig with the document ID
        $viewerConfig = $this->documentManager->getDocumentViewerConfig($document->id);

        // Assert that the returned configuration matches the expected configuration
        $this->assertEquals(['config' => 'value', 'documentId' => $document->id, 'documentName' => $document->name, 'isProcessed' => false], $viewerConfig);

        // Verify that pdfViewerService->getViewerConfig was called with the correct file ID
        $this->pdfViewerService->shouldHaveReceived('getViewerConfig')->with($document->files->first()->id);
    }

    /**
     * Test that getDocumentHistory retrieves the history of a document
     */
    public function testGetDocumentHistory(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Configure auditLogger mock to return a paginated history
        $historyItems = new Collection([
            ['id' => 1, 'action' => 'viewed'],
            ['id' => 2, 'action' => 'edited'],
        ]);
        $paginator = new LengthAwarePaginator($historyItems, 2, 10);

        $this->auditLogger->shouldReceive('getDocumentHistory')
            ->with($document->id, 10, 'desc')
            ->once()
            ->andReturn($paginator);

        // Call documentManager->getDocumentHistory with document ID, per page, and direction
        $documentHistory = $this->documentManager->getDocumentHistory($document->id, 10, 'desc');

        // Assert that the returned result is a LengthAwarePaginator instance
        $this->assertInstanceOf(LengthAwarePaginator::class, $documentHistory);

        // Assert that the paginator contains the expected history items
        $this->assertCount(2, $documentHistory->items());

        // Verify that auditLogger->getDocumentHistory was called with the correct parameters
        $this->auditLogger->shouldHaveReceived('getDocumentHistory')->with($document->id, 10, 'desc');
    }

    /**
     * Test that getDocumentHistory returns null when the document is not found
     */
    public function testGetDocumentHistoryReturnsNullWhenDocumentNotFound(): void
    {
        // Call documentManager->getDocumentHistory with a non-existent document ID
        $documentHistory = $this->documentManager->getDocumentHistory(999);

        // Assert that the result is null
        $this->assertNull($documentHistory);

        // Verify that auditLogger->getDocumentHistory was not called
        $this->auditLogger->shouldNotHaveReceived('getDocumentHistory');
    }

    /**
     * Test that logDocumentView logs a document view action
     */
    public function testLogDocumentView(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a test user
        $user = $this->createUser();

        // Configure auditLogger mock to log the view action
        $this->auditLogger->shouldReceive('logDocumentView')
            ->with($document->id, $user->id)
            ->once()
            ->andReturn(true);

        // Call documentManager->logDocumentView with document ID and user ID
        $result = $this->documentManager->logDocumentView($document->id, $user->id);

        // Assert that the result is true
        $this->assertTrue($result);

        // Verify that auditLogger->logDocumentView was called with the correct parameters
        $this->auditLogger->shouldHaveReceived('logDocumentView')->with($document->id, $user->id);
    }

    /**
     * Test that validateDocumentAccess checks if a user has access to a document
     */
    public function testValidateDocumentAccess(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a test user with document permissions
        $userWithPermissions = $this->createUserWithDocumentPermissions();

        // Call documentManager->validateDocumentAccess with document ID and user ID
        $hasAccess = $this->documentManager->validateDocumentAccess($document->id, $userWithPermissions->id);

        // Assert that the result is true
        $this->assertTrue($hasAccess);

        // Create a test user without document permissions
        $userWithoutPermissions = $this->createUserWithoutDocumentPermissions();

        // Call documentManager->validateDocumentAccess with document ID and user ID
        $hasAccess = $this->documentManager->validateDocumentAccess($document->id, $userWithoutPermissions->id);

        // Assert that the result is false
        $this->assertFalse($hasAccess);
    }

    /**
     * Test that checkDocumentExists verifies if a document exists
     */
    public function testCheckDocumentExists(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Call documentManager->checkDocumentExists with the document ID
        $exists = $this->documentManager->checkDocumentExists($document->id);

        // Assert that the result is true
        $this->assertTrue($exists);

        // Call documentManager->checkDocumentExists with a non-existent document ID
        $notExists = $this->documentManager->checkDocumentExists(999);

        // Assert that the result is false
        $this->assertFalse($notExists);
    }

    /**
     * Test that isDocumentProcessed checks if a document is processed
     */
    public function testIsDocumentProcessed(): void
    {
        // Create a test document that is processed
        $processedDocument = $this->createDocument(['status_id' => Document::STATUS_PROCESSED]);

        // Call documentManager->isDocumentProcessed with the document ID
        $isProcessed = $this->documentManager->isDocumentProcessed($processedDocument->id);

        // Assert that the result is true
        $this->assertTrue($isProcessed);

        // Create a test document that is not processed
        $unprocessedDocument = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);

        // Call documentManager->isDocumentProcessed with the document ID
        $isProcessed = $this->documentManager->isDocumentProcessed($unprocessedDocument->id);

        // Assert that the result is false
        $this->assertFalse($isProcessed);
    }

    /**
     * Test that isDocumentTrashed checks if a document is trashed
     */
    public function testIsDocumentTrashed(): void
    {
        // Create a test document that is trashed
        $trashedDocument = $this->createDocument(['status_id' => Document::STATUS_TRASHED]);

        // Call documentManager->isDocumentProcessed with the document ID
        $isTrashed = $this->documentManager->isDocumentTrashed($trashedDocument->id);

        // Assert that the result is true
        $this->assertTrue($isTrashed);

        // Create a test document that is not trashed
        $untrashedDocument = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);

        // Call documentManager->isDocumentProcessed with the document ID
        $isTrashed = $this->documentManager->isDocumentTrashed($untrashedDocument->id);

        // Assert that the result is false
        $this->assertFalse($isTrashed);
    }

    /**
     * Test that validateDocumentAccess checks if a user has access to a document
     */
    public function testValidateDocumentAccess(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Create a test user with document permissions
        $userWithPermissions = $this->createUserWithDocumentPermissions();

        // Call documentManager->validateDocumentAccess with document ID and user ID
        $hasAccess = $this->documentManager->validateDocumentAccess($document->id, $userWithPermissions->id);

        // Assert that the result is true
        $this->assertTrue($hasAccess);

        // Create a test user without document permissions
        $userWithoutPermissions = $this->createUserWithoutDocumentPermissions();

        // Call documentManager->validateDocumentAccess with document ID and user ID
        $hasAccess = $this->documentManager->validateDocumentAccess($document->id, $userWithoutPermissions->id);

        // Assert that the result is false
        $this->assertFalse($hasAccess);
    }

    /**
     * Test that checkDocumentExists verifies if a document exists
     */
    public function testCheckDocumentExists(): void
    {
        // Create a test document
        $document = $this->createDocument();

        // Call documentManager->checkDocumentExists with the document ID
        $exists = $this->documentManager->checkDocumentExists($document->id);

        // Assert that the result is true
        $this->assertTrue($exists);

        // Call documentManager->checkDocumentExists with a non-existent document ID
        $notExists = $this->documentManager->checkDocumentExists(999);

        // Assert that the result is false
        $this->assertFalse($notExists);
    }

    /**
     * Test that isDocumentProcessed checks if a document is processed
     */
    public function testIsDocumentProcessed(): void
    {
        // Create a test document that is processed
        $processedDocument = $this->createDocument(['status_id' => Document::STATUS_PROCESSED]);

        // Call documentManager->isDocumentProcessed with the document ID
        $isProcessed = $this->documentManager->isDocumentProcessed($processedDocument->id);

        // Assert that the result is true
        $this->assertTrue($isProcessed);

        // Create a test document that is not processed
        $unprocessedDocument = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);

        // Call documentManager->isDocumentProcessed with the document ID
        $isProcessed = $this->documentManager->isDocumentProcessed($unprocessedDocument->id);

        // Assert that the result is false
        $this->assertFalse($isProcessed);
    }

    /**
     * Test that isDocumentTrashed checks if a document is trashed
     */
    public function testIsDocumentTrashed(): void
    {
        // Create a test document that is trashed
        $trashedDocument = $this->createDocument(['status_id' => Document::STATUS_TRASHED]);

        // Call documentManager->isDocumentProcessed with the document ID
        $isTrashed = $this->documentManager->isDocumentTrashed($trashedDocument->id);

        // Assert that the result is true
        $this->assertTrue($isTrashed);

        // Create a test document that is not trashed
        $untrashedDocument = $this->createDocument(['status_id' => Document::STATUS_UNPROCESSED]);

        // Call documentManager->isDocumentProcessed with the document ID
        $isTrashed = $this->documentManager->isDocumentTrashed($untrashedDocument->id);

        // Assert that the result is false
        $this->assertFalse($isTrashed);
    }
}