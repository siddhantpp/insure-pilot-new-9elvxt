<?php

namespace Tests\Integration\API;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Document;
use App\Models\Policy;
use App\Models\Loss;
use App\Models\Claimant;
use App\Models\Producer;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\MapPolicyLoss;
use App\Models\MapLossClaimant;
use App\Models\MapProducerPolicy;

class MetadataApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Set up any common test dependencies or configurations
    }

    public function test_get_document_metadata()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a document with complete metadata
        $document = $this->createDocumentWithMetadata();
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/documents/{id}/metadata
        $response = $this->getJson("/api/documents/{$document->id}/metadata");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains the expected metadata fields
        $response->assertJsonStructure([
            'policy_id',
            'policy_number',
            'loss_id',
            'loss_sequence',
            'claimant_id',
            'claimant_name',
            'document_description',
            'assigned_to',
            'producer_id',
            'producer_number'
        ]);
        
        // Assert that the policy, loss, claimant, and producer information is correct
        $response->assertJson([
            'policy_id' => $document->policy_id,
            'policy_number' => $document->policy_number,
            'loss_id' => $document->loss_id,
            'loss_sequence' => $document->loss_sequence,
            'claimant_id' => $document->claimant_id,
            'claimant_name' => $document->claimant_name,
            'producer_id' => $document->producer_id,
            'producer_number' => $document->producer_number,
        ]);
    }

    public function test_get_document_metadata_unauthorized()
    {
        // Create a user without document permissions
        $user = $this->createUserWithoutDocumentPermissions();
        
        // Create a document with metadata
        $document = $this->createDocumentWithMetadata();
        
        // Act as the user without permissions
        $this->actingAsUser($user);
        
        // Send a GET request to /api/documents/{id}/metadata
        $response = $this->getJson("/api/documents/{$document->id}/metadata");
        
        // Assert that the response has a 403 status code (Forbidden)
        $response->assertStatus(403);
    }

    public function test_update_document_metadata()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a document with initial metadata
        $document = $this->createDocumentWithMetadata();
        
        // Create new policy, loss, claimant, and producer records for the update
        $newPolicy = Policy::factory()->create([
            'number' => 'PLCY-67890',
            'status_id' => 1,
        ]);
        
        $newProducer = Producer::factory()->create([
            'number' => 'AG-54321',
            'name' => 'New Test Producer',
            'status_id' => 1,
        ]);
        
        // Associate producer with policy
        MapProducerPolicy::create([
            'producer_id' => $newProducer->id,
            'policy_id' => $newPolicy->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        $newLoss = Loss::factory()->create([
            'name' => 'Property Damage',
            'date' => now()->subMonths(2),
            'status_id' => 1,
        ]);
        
        // Associate loss with policy
        MapPolicyLoss::create([
            'policy_id' => $newPolicy->id,
            'loss_id' => $newLoss->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        $newClaimant = Claimant::factory()->create([
            'status_id' => 1,
        ]);
        
        // Associate claimant with loss
        MapLossClaimant::create([
            'loss_id' => $newLoss->id,
            'claimant_id' => $newClaimant->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Create user group for assignment
        $userGroup = UserGroup::factory()->create([
            'name' => 'Test Group',
            'status_id' => 1,
        ]);
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Prepare updated metadata payload
        $updatedMetadata = [
            'policy_id' => $newPolicy->id,
            'loss_id' => $newLoss->id,
            'claimant_id' => $newClaimant->id,
            'document_description' => 'Updated Document Description',
            'assigned_to_id' => $userGroup->id,
            'assigned_to_type' => 'group',
            'producer_id' => $newProducer->id,
        ];
        
        // Send a PUT request to /api/documents/{id}/metadata with the payload
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updatedMetadata);
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains the updated metadata
        $response->assertJson([
            'policy_id' => $newPolicy->id,
            'loss_id' => $newLoss->id,
            'claimant_id' => $newClaimant->id,
            'document_description' => 'Updated Document Description',
            'producer_id' => $newProducer->id,
        ]);
        
        // Verify the database was updated correctly by retrieving the document
        $updatedDocument = Document::find($document->id);
        $this->assertEquals($newPolicy->id, $updatedDocument->policy_id);
        $this->assertEquals($newLoss->id, $updatedDocument->loss_id);
        $this->assertEquals($newClaimant->id, $updatedDocument->claimant_id);
        $this->assertEquals($newProducer->id, $updatedDocument->producer_id);
    }

    public function test_update_document_metadata_validation_failure()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a document with initial metadata
        $document = $this->createDocumentWithMetadata();
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Prepare invalid metadata payload (e.g., non-existent policy_id)
        $invalidMetadata = [
            'policy_id' => 9999999, // Non-existent policy ID
            'loss_id' => 9999999,   // Non-existent loss ID
            'claimant_id' => 9999999, // Non-existent claimant ID
        ];
        
        // Send a PUT request to /api/documents/{id}/metadata with the invalid payload
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $invalidMetadata);
        
        // Assert that the response has a 422 status code (Unprocessable Entity)
        $response->assertStatus(422);
        
        // Assert that the response contains validation error messages
        $response->assertJsonValidationErrors(['policy_id', 'loss_id', 'claimant_id']);
    }

    public function test_update_processed_document_metadata()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a document with metadata
        $document = $this->createDocumentWithMetadata();
        
        // Mark the document as processed
        $document->markAsProcessed();
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Prepare updated metadata payload
        $updatedMetadata = [
            'document_description' => 'Updated Document Description',
        ];
        
        // Send a PUT request to /api/documents/{id}/metadata with the payload
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updatedMetadata);
        
        // Assert that the response has a 422 status code (Unprocessable Entity)
        $response->assertStatus(422);
        
        // Assert that the response contains an error message about processed documents
        $response->assertJsonValidationErrors(['document']);
    }

    public function test_update_document_metadata_unauthorized()
    {
        // Create a user without document permissions
        $user = $this->createUserWithoutDocumentPermissions();
        
        // Create a document with metadata
        $document = $this->createDocumentWithMetadata();
        
        // Act as the user without permissions
        $this->actingAsUser($user);
        
        // Prepare updated metadata payload
        $updatedMetadata = [
            'document_description' => 'Updated Document Description',
        ];
        
        // Send a PUT request to /api/documents/{id}/metadata with the payload
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $updatedMetadata);
        
        // Assert that the response has a 403 status code (Forbidden)
        $response->assertStatus(403);
    }

    public function test_get_policy_options()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create multiple policy records
        $policies = Policy::factory()->count(3)->create();
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/policies
        $response = $this->getJson("/api/policies");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains the expected policy options
        $response->assertJsonCount(3); // 3 policies
        
        // Assert that each option has id, value, and label properties
        $response->assertJsonStructure([
            '*' => [
                'id',
                'value',
                'label'
            ]
        ]);
    }

    public function test_get_policy_options_with_search()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create multiple policy records with different numbers
        Policy::factory()->create(['number' => 'ABC-12345']);
        Policy::factory()->create(['number' => 'DEF-67890']);
        Policy::factory()->create(['number' => 'ABC-54321']);
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/policies?search=ABC
        $response = $this->getJson("/api/policies?search=ABC");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response only contains policies matching the search term
        $response->assertJsonCount(2); // Only the 2 policies with 'ABC'
        
        // Check that the returned policies contain 'ABC'
        $responseData = $response->json();
        $this->assertTrue(str_contains($responseData[0]['label'], 'ABC'));
        $this->assertTrue(str_contains($responseData[1]['label'], 'ABC'));
    }

    public function test_get_policy_options_with_producer_filter()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a producer record
        $producer = Producer::factory()->create();
        
        // Create multiple policy records
        $policy1 = Policy::factory()->create();
        $policy2 = Policy::factory()->create();
        $policy3 = Policy::factory()->create();
        
        // Associate some policies with the producer
        MapProducerPolicy::create([
            'producer_id' => $producer->id,
            'policy_id' => $policy1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        MapProducerPolicy::create([
            'producer_id' => $producer->id,
            'policy_id' => $policy2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/policies?producer_id={id}
        $response = $this->getJson("/api/policies?producer_id={$producer->id}");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response only contains policies associated with the producer
        $response->assertJsonCount(2); // Only the 2 policies associated with the producer
        
        // Verify the correct policy IDs are returned
        $responseData = $response->json();
        $returnedIds = array_column($responseData, 'id');
        sort($returnedIds);
        
        $expectedIds = [$policy1->id, $policy2->id];
        sort($expectedIds);
        
        $this->assertEquals($expectedIds, $returnedIds);
    }

    public function test_get_loss_options()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a policy record
        $policy = Policy::factory()->create();
        
        // Create multiple loss records
        $loss1 = Loss::factory()->create();
        $loss2 = Loss::factory()->create();
        $loss3 = Loss::factory()->create();
        
        // Associate the losses with the policy
        MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/policies/{id}/losses
        $response = $this->getJson("/api/policies/{$policy->id}/losses");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains the expected loss options
        $response->assertJsonCount(2); // Only the 2 losses associated with the policy
        
        // Assert that each option has id, value, and label properties
        $response->assertJsonStructure([
            '*' => [
                'id',
                'value',
                'label'
            ]
        ]);
        
        // Verify the correct loss IDs are returned
        $responseData = $response->json();
        $returnedIds = array_column($responseData, 'id');
        sort($returnedIds);
        
        $expectedIds = [$loss1->id, $loss2->id];
        sort($expectedIds);
        
        $this->assertEquals($expectedIds, $returnedIds);
    }

    public function test_get_loss_options_with_search()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a policy record
        $policy = Policy::factory()->create();
        
        // Create multiple loss records with different descriptions
        $loss1 = Loss::factory()->create(['name' => 'Vehicle Accident']);
        $loss2 = Loss::factory()->create(['name' => 'Property Damage']);
        $loss3 = Loss::factory()->create(['name' => 'Auto Collision']);
        
        // Associate the losses with the policy
        MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss3->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/policies/{id}/losses?search=Auto
        $response = $this->getJson("/api/policies/{$policy->id}/losses?search=Auto");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response only contains losses matching the search term
        $response->assertJsonCount(1); // Only the loss with 'Auto' in the name
        
        // Check that the returned loss contains 'Auto'
        $responseData = $response->json();
        $this->assertEquals($loss3->id, $responseData[0]['id']);
        $this->assertTrue(str_contains($responseData[0]['label'], 'Auto'));
    }

    public function test_get_claimant_options()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a loss record
        $loss = Loss::factory()->create();
        
        // Create multiple claimant records
        $claimant1 = Claimant::factory()->create();
        $claimant2 = Claimant::factory()->create();
        $claimant3 = Claimant::factory()->create();
        
        // Associate the claimants with the loss
        MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/losses/{id}/claimants
        $response = $this->getJson("/api/losses/{$loss->id}/claimants");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains the expected claimant options
        $response->assertJsonCount(2); // Only the 2 claimants associated with the loss
        
        // Assert that each option has id, value, and label properties
        $response->assertJsonStructure([
            '*' => [
                'id',
                'value',
                'label'
            ]
        ]);
        
        // Verify the correct claimant IDs are returned
        $responseData = $response->json();
        $returnedIds = array_column($responseData, 'id');
        sort($returnedIds);
        
        $expectedIds = [$claimant1->id, $claimant2->id];
        sort($expectedIds);
        
        $this->assertEquals($expectedIds, $returnedIds);
    }

    public function test_get_claimant_options_with_search()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a loss record
        $loss = Loss::factory()->create();
        
        // Create multiple claimant records with different names
        $claimant1 = Claimant::factory()->create([
            'description' => 'John Smith', // Using description for searchability
        ]);
        $claimant2 = Claimant::factory()->create([
            'description' => 'Jane Doe',
        ]);
        $claimant3 = Claimant::factory()->create([
            'description' => 'John Doe',
        ]);
        
        // Associate the claimants with the loss
        MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant1->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant2->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant3->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/losses/{id}/claimants?search=John
        $response = $this->getJson("/api/losses/{$loss->id}/claimants?search=John");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains claimants matching the search term
        $responseData = $response->json();
        $this->assertCount(2, $responseData); // The 2 claimants with 'John' in their description
    }

    public function test_get_producer_options()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create multiple producer records
        $producers = Producer::factory()->count(3)->create();
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/producers
        $response = $this->getJson("/api/producers");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains the expected producer options
        $response->assertJsonCount(3);
        
        // Assert that each option has id, value, and label properties
        $response->assertJsonStructure([
            '*' => [
                'id',
                'value',
                'label'
            ]
        ]);
    }

    public function test_get_producer_options_with_search()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create multiple producer records with different names
        Producer::factory()->create(['name' => 'ABC Insurance']);
        Producer::factory()->create(['name' => 'XYZ Insurance']);
        Producer::factory()->create(['name' => 'ABC Brokers']);
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/producers?search=ABC
        $response = $this->getJson("/api/producers?search=ABC");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response only contains producers matching the search term
        $response->assertJsonCount(2); // Only the 2 producers with 'ABC'
        
        // Check that the returned producers contain 'ABC'
        $responseData = $response->json();
        $this->assertTrue(str_contains($responseData[0]['label'], 'ABC'));
        $this->assertTrue(str_contains($responseData[1]['label'], 'ABC'));
    }

    public function test_get_user_options()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create multiple user records
        $users = User::factory()->count(3)->create();
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/users
        $response = $this->getJson("/api/users");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains user options with the expected structure
        $response->assertJsonStructure([
            '*' => [
                'id',
                'value',
                'label'
            ]
        ]);
    }

    public function test_get_user_group_options()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create multiple user group records
        $userGroups = UserGroup::factory()->count(3)->create();
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Send a GET request to /api/user-groups
        $response = $this->getJson("/api/user-groups");
        
        // Assert that the response has a 200 status code
        $response->assertStatus(200);
        
        // Assert that the response contains the expected user group options
        $response->assertJsonCount(3);
        
        // Assert that each option has id, value, and label properties
        $response->assertJsonStructure([
            '*' => [
                'id',
                'value',
                'label'
            ]
        ]);
    }

    public function test_field_dependency_validation()
    {
        // Create a user with document permissions
        $user = $this->createUserWithDocumentPermissions();
        
        // Create a document with initial metadata
        $document = $this->createDocumentWithMetadata();
        
        // Create policy and loss records that are not associated
        $policy = Policy::factory()->create();
        $loss = Loss::factory()->create();
        
        // Note: We're not creating a MapPolicyLoss record to associate them
        
        // Act as the created user
        $this->actingAsUser($user);
        
        // Prepare metadata payload with invalid dependency (loss not belonging to policy)
        $invalidMetadata = [
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
        ];
        
        // Send a PUT request to /api/documents/{id}/metadata with the payload
        $response = $this->putJson("/api/documents/{$document->id}/metadata", $invalidMetadata);
        
        // Assert that the response has a 422 status code (Unprocessable Entity)
        $response->assertStatus(422);
        
        // Assert that the response contains a validation error about the dependency
        $response->assertJsonValidationErrors(['loss_id']);
    }
}