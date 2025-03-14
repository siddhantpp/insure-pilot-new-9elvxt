<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\User;
use App\Models\Policy;
use App\Models\Loss;
use App\Models\Claimant;
use App\Models\Producer;
use App\Models\File;
use App\Models\MapDocumentFile;
use App\Models\MapPolicyLoss;
use App\Models\MapLossClaimant;
use App\Models\MapProducerPolicy;

class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker;

    /**
     * Set up the test environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Initialize any common test dependencies here
    }

    /**
     * Clean up the test environment after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Perform any necessary cleanup operations here
        parent::tearDown();
    }

    /**
     * Creates a test document with optional attributes.
     *
     * @param array $attributes
     * @return \App\Models\Document
     */
    protected function createDocument(array $attributes = []): Document
    {
        $defaultAttributes = [
            'name' => $this->faker->sentence,
            'date_received' => $this->faker->date(),
            'description' => $this->faker->paragraph,
            'status_id' => Document::STATUS_UNPROCESSED,
            'created_by' => 1,
            'updated_by' => 1,
        ];

        return Document::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    /**
     * Creates a test document with an associated file.
     *
     * @param array $documentAttributes
     * @param array $fileAttributes
     * @return \App\Models\Document
     */
    protected function createDocumentWithFile(array $documentAttributes = [], array $fileAttributes = []): Document
    {
        $document = $this->createDocument($documentAttributes);
        
        $defaultFileAttributes = [
            'name' => 'test_document.pdf',
            'path' => 'documents/test_document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
        
        $file = File::factory()->create(array_merge($defaultFileAttributes, $fileAttributes));
        
        MapDocumentFile::create([
            'document_id' => $document->id,
            'file_id' => $file->id,
            'description' => 'Test document file mapping',
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        return $document->fresh(['files']);
    }

    /**
     * Creates a test document with complete metadata (policy, loss, claimant, producer).
     *
     * @param array $documentAttributes
     * @param array $metadataAttributes
     * @return \App\Models\Document
     */
    protected function createDocumentWithMetadata(array $documentAttributes = [], array $metadataAttributes = []): Document
    {
        // Create producer
        $producer = Producer::factory()->create([
            'number' => $metadataAttributes['producer_number'] ?? 'AG-789456',
            'name' => $metadataAttributes['producer_name'] ?? 'Test Producer',
            'status_id' => 1,
        ]);
        
        // Create policy
        $policy = Policy::factory()->create([
            'number' => $metadataAttributes['policy_number'] ?? 'PLCY-12345',
            'status_id' => 1,
        ]);
        
        // Associate producer with policy
        MapProducerPolicy::create([
            'producer_id' => $producer->id,
            'policy_id' => $policy->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Create loss
        $loss = Loss::factory()->create([
            'name' => $metadataAttributes['loss_name'] ?? 'Vehicle Accident',
            'date' => $metadataAttributes['loss_date'] ?? now()->subMonths(3),
            'status_id' => 1,
        ]);
        
        // Associate loss with policy
        MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Create claimant
        $claimant = Claimant::factory()->create([
            'status_id' => 1,
        ]);
        
        // Associate claimant with loss
        MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ]);
        
        // Create document with references to policy, loss, claimant, and producer
        $document = $this->createDocument(array_merge([
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
            'claimant_id' => $claimant->id,
            'producer_id' => $producer->id,
        ], $documentAttributes));
        
        return $document->fresh(['policy', 'loss', 'claimant', 'producer']);
    }

    /**
     * Creates a test user with optional role and permissions.
     *
     * @param array $attributes
     * @param string|null $role
     * @return \App\Models\User
     */
    protected function createUser(array $attributes = [], string $role = null): User
    {
        $defaultAttributes = [
            'username' => $this->faker->userName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'status_id' => 1,
        ];
        
        if ($role) {
            $roleMap = [
                'admin' => User::ROLE_ADMIN,
                'manager' => User::ROLE_MANAGER,
                'adjuster' => User::ROLE_ADJUSTER,
                'underwriter' => User::ROLE_UNDERWRITER,
                'support' => User::ROLE_SUPPORT,
                'readonly' => User::ROLE_READONLY,
            ];
            
            $defaultAttributes['user_type_id'] = $roleMap[$role] ?? User::ROLE_READONLY;
        }
        
        return User::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    /**
     * Sets the authenticated user for the test.
     *
     * @param \App\Models\User $user
     * @return $this
     */
    protected function actingAsUser(User $user)
    {
        return $this->actingAs($user);
    }

    /**
     * Creates a user with permissions to view, edit, and process documents.
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    protected function createUserWithDocumentPermissions(array $attributes = []): User
    {
        // The 'adjuster' role has permissions to view, edit, and process documents
        return $this->createUser($attributes, 'adjuster');
    }

    /**
     * Creates a user without permissions to view, edit, or process documents.
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    protected function createUserWithoutDocumentPermissions(array $attributes = []): User
    {
        // Create a user with a role that doesn't have document permissions
        return $this->createUser($attributes, 'readonly');
    }

    /**
     * Asserts that a document has the expected attributes and relationships.
     *
     * @param \App\Models\Document $expected
     * @param \App\Models\Document $actual
     * @param array $attributes
     * @return void
     */
    protected function assertDocumentEquals(Document $expected, Document $actual, array $attributes = []): void
    {
        $this->assertEquals($expected->id, $actual->id);
        
        $defaultAttributes = [
            'name',
            'description',
            'status_id',
            'policy_id',
            'loss_id',
            'claimant_id',
            'producer_id',
        ];
        
        $attributesToCheck = empty($attributes) ? $defaultAttributes : $attributes;
        
        foreach ($attributesToCheck as $attribute) {
            $this->assertEquals(
                $expected->{$attribute},
                $actual->{$attribute},
                "The '$attribute' attribute does not match."
            );
        }
        
        // Check relationships if specified
        if (isset($attributes['relationships'])) {
            foreach ($attributes['relationships'] as $relationship) {
                $this->assertEquals(
                    $expected->{$relationship}->id,
                    $actual->{$relationship}->id,
                    "The '$relationship' relationship does not match."
                );
            }
        }
    }
}