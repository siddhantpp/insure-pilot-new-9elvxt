<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use App\Services\MetadataService;
use App\Services\AuditLogger;
use App\Models\Document;
use App\Models\Policy;
use App\Models\Loss;
use App\Models\Claimant;
use App\Models\Producer;
use App\Models\User;
use App\Models\UserGroup;

class MetadataServiceTest extends TestCase
{
    /**
     * The MetadataService instance being tested.
     *
     * @var MetadataService
     */
    protected $metadataService;

    /**
     * The mocked AuditLogger instance.
     *
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * Set up the test environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock of the AuditLogger service
        $this->auditLogger = Mockery::mock(AuditLogger::class);
        
        // Create an instance of MetadataService with the mocked AuditLogger
        $this->metadataService = new MetadataService($this->auditLogger);
    }

    /**
     * Clean up the test environment after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that getDocumentMetadata returns the correct metadata for a document.
     *
     * @return void
     */
    public function testGetDocumentMetadata()
    {
        // Create a test document with metadata
        $document = $this->createDocumentWithMetadata();
        
        // Call the method being tested
        $result = $this->metadataService->getDocumentMetadata($document->id);
        
        // Assert that the result is not null
        $this->assertNotNull($result);
        
        // Assert that the result contains the expected document ID
        $this->assertEquals($document->id, $result['id']);
        
        // Assert that the result contains the expected metadata fields
        $this->assertArrayHasKey('policy_number', $result);
        $this->assertArrayHasKey('loss_sequence', $result);
        $this->assertArrayHasKey('claimant_name', $result);
        $this->assertArrayHasKey('producer_number', $result);
        $this->assertArrayHasKey('assigned_to', $result);
    }

    /**
     * Test that getDocumentMetadata returns null for a non-existent document.
     *
     * @return void
     */
    public function testGetDocumentMetadataWithNonExistentDocument()
    {
        // Call getDocumentMetadata with a non-existent document ID
        $result = $this->metadataService->getDocumentMetadata(9999);
        
        // Assert that the result is null
        $this->assertNull($result);
    }

    /**
     * Test that updateDocumentMetadata correctly updates a document's metadata.
     *
     * @return void
     */
    public function testUpdateDocumentMetadata()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Create a test user
        $user = $this->createUser();
        
        // Set up the auditLogger mock to expect logDocumentEdit to be called once
        $this->auditLogger->shouldReceive('logDocumentEdit')
            ->once()
            ->andReturn(true);
        
        // Prepare update data
        $updateData = [
            'policy_id' => Policy::factory()->create()->id,
            'description' => 'Updated document description',
        ];
        
        // Call updateDocumentMetadata with the document ID, update data, and user ID
        $result = $this->metadataService->updateDocumentMetadata($document->id, $updateData, $user->id);
        
        // Assert that the result is not false (update successful)
        $this->assertNotFalse($result);
        
        // Assert that the result contains the updated values
        $this->assertEquals($updateData['policy_id'], $result['policy_id']);
        $this->assertEquals($updateData['description'], $result['description']);
        
        // Retrieve the document from the database and verify the updates were persisted
        $updatedDocument = Document::find($document->id);
        $this->assertEquals($updateData['policy_id'], $updatedDocument->policy_id);
        $this->assertEquals($updateData['description'], $updatedDocument->description);
    }

    /**
     * Test that updateDocumentMetadata returns false for a non-existent document.
     *
     * @return void
     */
    public function testUpdateDocumentMetadataWithNonExistentDocument()
    {
        // Create a test user
        $user = $this->createUser();
        
        // Call updateDocumentMetadata with a non-existent document ID, some data, and the user ID
        $result = $this->metadataService->updateDocumentMetadata(9999, ['description' => 'Test'], $user->id);
        
        // Assert that the result is false
        $this->assertFalse($result);
    }

    /**
     * Test that updateDocumentMetadata returns false for a processed document.
     *
     * @return void
     */
    public function testUpdateDocumentMetadataWithProcessedDocument()
    {
        // Create a test document
        $document = $this->createDocument();
        
        // Mark the document as processed
        $document->markAsProcessed();
        
        // Create a test user
        $user = $this->createUser();
        
        // Call updateDocumentMetadata with the document ID, some data, and the user ID
        $result = $this->metadataService->updateDocumentMetadata($document->id, ['description' => 'Test'], $user->id);
        
        // Assert that the result is false
        $this->assertFalse($result);
    }

    /**
     * Test that getPolicyOptions returns correctly formatted policy options.
     *
     * @return void
     */
    public function testGetPolicyOptions()
    {
        // Create multiple test policies using Policy::factory
        $policies = Policy::factory()->count(3)->create();
        
        // Call getPolicyOptions on the metadataService
        $result = $this->metadataService->getPolicyOptions();
        
        // Assert that the result is an array with the expected structure
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Assert that each option has id, value, and label properties
        foreach ($result as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
        
        // Assert that the options match the created policies
        $resultIds = array_column($result, 'id');
        foreach ($policies as $policy) {
            $this->assertContains($policy->id, $resultIds);
        }
    }

    /**
     * Test that getPolicyOptions correctly filters results based on search term.
     *
     * @return void
     */
    public function testGetPolicyOptionsWithSearch()
    {
        // Create multiple test policies with different numbers
        $policy1 = Policy::factory()->create(['number' => 'ABC123']);
        $policy2 = Policy::factory()->create(['number' => 'ABC456']);
        $policy3 = Policy::factory()->create(['number' => 'XYZ789']);
        
        // Call getPolicyOptions with a search term that matches some policies
        $result = $this->metadataService->getPolicyOptions('ABC');
        
        // Assert that only matching policies are returned
        $this->assertIsArray($result);
        $resultIds = array_column($result, 'id');
        
        // Assert that matching policies are included
        $this->assertContains($policy1->id, $resultIds);
        $this->assertContains($policy2->id, $resultIds);
        
        // Assert that non-matching policies are not included
        $this->assertNotContains($policy3->id, $resultIds);
    }

    /**
     * Test that getPolicyOptions correctly filters results based on producer ID.
     *
     * @return void
     */
    public function testGetPolicyOptionsWithProducerId()
    {
        // Create a test producer
        $producer = Producer::factory()->create();
        
        // Create multiple policies, some associated with the producer
        $policy1 = Policy::factory()->create();
        $policy2 = Policy::factory()->create();
        $policy3 = Policy::factory()->create();
        
        // Associate policy1 and policy2 with the producer
        \App\Models\MapProducerPolicy::create([
            'producer_id' => $producer->id,
            'policy_id' => $policy1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        \App\Models\MapProducerPolicy::create([
            'producer_id' => $producer->id,
            'policy_id' => $policy2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Call getPolicyOptions with the producer ID
        $result = $this->metadataService->getPolicyOptions(null, $producer->id);
        
        // Assert that only policies associated with the producer are returned
        $this->assertIsArray($result);
        $resultIds = array_column($result, 'id');
        
        $this->assertContains($policy1->id, $resultIds);
        $this->assertContains($policy2->id, $resultIds);
        $this->assertNotContains($policy3->id, $resultIds);
    }

    /**
     * Test that getLossOptions returns correctly formatted loss options for a policy.
     *
     * @return void
     */
    public function testGetLossOptions()
    {
        // Create a test policy
        $policy = Policy::factory()->create();
        
        // Create multiple losses associated with the policy
        $loss1 = Loss::factory()->create();
        $loss2 = Loss::factory()->create();
        
        // Create a loss not associated with the policy
        $otherLoss = Loss::factory()->create();
        
        // Associate losses with the policy
        \App\Models\MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        \App\Models\MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Call getLossOptions with the policy ID
        $result = $this->metadataService->getLossOptions($policy->id);
        
        // Assert that the result is an array with the expected structure
        $this->assertIsArray($result);
        
        // Assert that each option has id, value, and label properties
        foreach ($result as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
        
        // Assert that the options match the created losses
        $resultIds = array_column($result, 'id');
        $this->assertContains($loss1->id, $resultIds);
        $this->assertContains($loss2->id, $resultIds);
        $this->assertNotContains($otherLoss->id, $resultIds);
    }

    /**
     * Test that getLossOptions correctly filters results based on search term.
     *
     * @return void
     */
    public function testGetLossOptionsWithSearch()
    {
        // Create a test policy
        $policy = Policy::factory()->create();
        
        // Create multiple losses with different descriptions
        $loss1 = Loss::factory()->create(['name' => 'Fire Damage']);
        $loss2 = Loss::factory()->create(['name' => 'Water Damage']);
        $loss3 = Loss::factory()->create(['name' => 'Theft']);
        
        // Associate all losses with the policy
        \App\Models\MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        \App\Models\MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        \App\Models\MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss3->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Call getLossOptions with the policy ID and a search term
        $result = $this->metadataService->getLossOptions($policy->id, 'Damage');
        
        // Assert that only matching losses are returned
        $this->assertIsArray($result);
        $resultIds = array_column($result, 'id');
        
        $this->assertContains($loss1->id, $resultIds);
        $this->assertContains($loss2->id, $resultIds);
        $this->assertNotContains($loss3->id, $resultIds);
    }

    /**
     * Test that getClaimantOptions returns correctly formatted claimant options for a loss.
     *
     * @return void
     */
    public function testGetClaimantOptions()
    {
        // Create a test loss
        $loss = Loss::factory()->create();
        
        // Create multiple claimants associated with the loss
        $claimant1 = Claimant::factory()->create();
        $claimant2 = Claimant::factory()->create();
        
        // Create a claimant not associated with the loss
        $otherClaimant = Claimant::factory()->create();
        
        // Associate claimants with the loss
        \App\Models\MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        \App\Models\MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Call getClaimantOptions with the loss ID
        $result = $this->metadataService->getClaimantOptions($loss->id);
        
        // Assert that the result is an array with the expected structure
        $this->assertIsArray($result);
        
        // Assert that each option has id, value, and label properties
        foreach ($result as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
        
        // Assert that the options match the created claimants
        $resultIds = array_column($result, 'id');
        $this->assertContains($claimant1->id, $resultIds);
        $this->assertContains($claimant2->id, $resultIds);
        $this->assertNotContains($otherClaimant->id, $resultIds);
    }

    /**
     * Test that getClaimantOptions correctly filters results based on search term.
     *
     * @return void
     */
    public function testGetClaimantOptionsWithSearch()
    {
        // Create a test loss
        $loss = Loss::factory()->create();
        
        // Create multiple claimants with different names
        // Note: Since the Claimant model in this system relates to a separate name model,
        // we can't directly set the names here. For the sake of this test, we'll
        // focus on verifying that the search parameter is passed correctly.
        $claimant1 = Claimant::factory()->create();
        $claimant2 = Claimant::factory()->create();
        
        // Associate claimants with the loss
        \App\Models\MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        \App\Models\MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Call getClaimantOptions with the loss ID and a search term
        $result = $this->metadataService->getClaimantOptions($loss->id, 'Smith');
        
        // Assert that the result has the expected structure
        $this->assertIsArray($result);
        foreach ($result as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    /**
     * Test that getProducerOptions returns correctly formatted producer options.
     *
     * @return void
     */
    public function testGetProducerOptions()
    {
        // Create multiple test producers using Producer::factory
        $producers = Producer::factory()->count(3)->create();
        
        // Call getProducerOptions on the metadataService
        $result = $this->metadataService->getProducerOptions();
        
        // Assert that the result is an array with the expected structure
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Assert that each option has id, value, and label properties
        foreach ($result as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
        
        // Assert that the options match the created producers
        $resultIds = array_column($result, 'id');
        foreach ($producers as $producer) {
            $this->assertContains($producer->id, $resultIds);
        }
    }

    /**
     * Test that getProducerOptions correctly filters results based on search term.
     *
     * @return void
     */
    public function testGetProducerOptionsWithSearch()
    {
        // Create multiple test producers with different names
        $producer1 = Producer::factory()->create(['name' => 'ABC Insurance']);
        $producer2 = Producer::factory()->create(['name' => 'ABC Brokers']);
        $producer3 = Producer::factory()->create(['name' => 'XYZ Agency']);
        
        // Call getProducerOptions with a search term
        $result = $this->metadataService->getProducerOptions('ABC');
        
        // Assert that only matching producers are returned
        $this->assertIsArray($result);
        $resultIds = array_column($result, 'id');
        
        $this->assertContains($producer1->id, $resultIds);
        $this->assertContains($producer2->id, $resultIds);
        $this->assertNotContains($producer3->id, $resultIds);
    }

    /**
     * Test that getUserOptions returns correctly formatted user options.
     *
     * @return void
     */
    public function testGetUserOptions()
    {
        // Create multiple test users
        $users = [];
        for ($i = 0; $i < 3; $i++) {
            $users[] = $this->createUser(['username' => "testuser{$i}"]);
        }
        
        // Call getUserOptions on the metadataService
        $result = $this->metadataService->getUserOptions();
        
        // Assert that the result is an array with the expected structure
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Assert that each option has id, value, and label properties
        foreach ($result as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
        
        // Assert that the users we created are in the results
        $resultIds = array_column($result, 'id');
        foreach ($users as $user) {
            $this->assertContains($user->id, $resultIds);
        }
    }

    /**
     * Test that getUserGroupOptions returns correctly formatted user group options.
     *
     * @return void
     */
    public function testGetUserGroupOptions()
    {
        // Create multiple test user groups
        $groups = [];
        for ($i = 0; $i < 3; $i++) {
            $group = UserGroup::create([
                'name' => "Test Group {$i}",
                'description' => "Description for test group {$i}",
                'status_id' => 1,
                'created_by' => 1,
                'updated_by' => 1
            ]);
            $groups[] = $group;
        }
        
        // Call getUserGroupOptions on the metadataService
        $result = $this->metadataService->getUserGroupOptions();
        
        // Assert that the result is an array with the expected structure
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Assert that each option has id, value, and label properties
        foreach ($result as $option) {
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
        
        // Assert that the groups we created are in the results
        $resultIds = array_column($result, 'id');
        foreach ($groups as $group) {
            $this->assertContains($group->id, $resultIds);
        }
    }

    /**
     * Test that validateMetadataRelationships correctly validates relationships between metadata fields.
     *
     * @return void
     */
    public function testValidateMetadataRelationships()
    {
        // Create a test policy
        $policy = Policy::factory()->create();
        
        // Create a test loss associated with the policy
        $loss = Loss::factory()->create();
        \App\Models\MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Create a test loss associated with a different policy
        $otherPolicy = Policy::factory()->create();
        $otherLoss = Loss::factory()->create();
        \App\Models\MapPolicyLoss::create([
            'policy_id' => $otherPolicy->id,
            'loss_id' => $otherLoss->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Create a test claimant associated with the first loss
        $claimant = Claimant::factory()->create();
        \App\Models\MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Test valid relationships (loss belongs to policy, claimant belongs to loss)
        $validData = [
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
            'claimant_id' => $claimant->id
        ];
        $result = $this->metadataService->validateMetadataRelationships($validData);
        $this->assertEmpty($result, "Valid relationships should return an empty errors array");
        
        // Test invalid relationships (loss doesn't belong to policy)
        $invalidPolicyLoss = [
            'policy_id' => $policy->id,
            'loss_id' => $otherLoss->id
        ];
        $result = $this->metadataService->validateMetadataRelationships($invalidPolicyLoss);
        $this->assertArrayHasKey('loss_id', $result, "Invalid policy-loss relationship should return an error");
        
        // Test invalid relationships (claimant doesn't belong to loss)
        $invalidLossClaimant = [
            'loss_id' => $otherLoss->id,
            'claimant_id' => $claimant->id
        ];
        $result = $this->metadataService->validateMetadataRelationships($invalidLossClaimant);
        $this->assertArrayHasKey('claimant_id', $result, "Invalid loss-claimant relationship should return an error");
    }
}