<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Validator;
use Mockery;
use App\Services\MetadataService;
use App\Http\Requests\DocumentUpdateRequest;
use App\Models\Policy;
use App\Models\Loss;
use App\Models\Claimant;
use App\Models\MapPolicyLoss;
use App\Models\MapLossClaimant;

/**
 * Unit tests for validating document metadata fields and their relationships
 * in the Documents View feature.
 */
class FieldValidatorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var MetadataService
     */
    protected $metadataService;

    /**
     * Set up the test environment before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Create a new instance of MetadataService with a mocked AuditLogger
        $auditLogger = Mockery::mock('App\Services\AuditLogger');
        $this->metadataService = new MetadataService($auditLogger);
    }

    /**
     * Test that empty metadata passes validation
     *
     * @return void
     */
    public function testValidateEmptyMetadata()
    {
        $metadata = [];
        $errors = $this->metadataService->validateMetadataRelationships($metadata);
        
        $this->assertEmpty($errors, 'Empty metadata should pass validation');
    }

    /**
     * Test that a loss_id without a policy_id fails validation
     *
     * @return void
     */
    public function testValidateLossWithoutPolicy()
    {
        $metadata = [
            'loss_id' => 123,
            'policy_id' => null
        ];
        
        $errors = $this->metadataService->validateMetadataRelationships($metadata);
        
        $this->assertArrayHasKey('loss_id', $errors, 'Loss without a policy should fail validation');
    }

    /**
     * Test that a claimant_id without a loss_id fails validation
     *
     * @return void
     */
    public function testValidateClaimantWithoutLoss()
    {
        $metadata = [
            'claimant_id' => 456,
            'loss_id' => null
        ];
        
        $errors = $this->metadataService->validateMetadataRelationships($metadata);
        
        $this->assertArrayHasKey('claimant_id', $errors, 'Claimant without a loss should fail validation');
    }

    /**
     * Test that a loss_id not belonging to the specified policy_id fails validation
     *
     * @return void
     */
    public function testValidateLossNotBelongingToPolicy()
    {
        // Create a policy and a loss (not associated with each other)
        $policy = Policy::factory()->create();
        $loss = Loss::factory()->create();
        
        $metadata = [
            'policy_id' => $policy->id,
            'loss_id' => $loss->id
        ];
        
        $errors = $this->metadataService->validateMetadataRelationships($metadata);
        
        $this->assertArrayHasKey('loss_id', $errors, 'Loss not belonging to policy should fail validation');
        $this->assertStringContainsString('not belong', $errors['loss_id'], 'Error message should mention relationship');
    }

    /**
     * Test that a claimant_id not belonging to the specified loss_id fails validation
     *
     * @return void
     */
    public function testValidateClaimantNotBelongingToLoss()
    {
        // Create a policy
        $policy = Policy::factory()->create();
        
        // Create a loss
        $loss = Loss::factory()->create();
        
        // Associate the loss with the policy
        MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Create a claimant (not associated with the loss)
        $claimant = Claimant::factory()->create();
        
        $metadata = [
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
            'claimant_id' => $claimant->id
        ];
        
        $errors = $this->metadataService->validateMetadataRelationships($metadata);
        
        $this->assertArrayHasKey('claimant_id', $errors, 'Claimant not belonging to loss should fail validation');
        $this->assertStringContainsString('not belong', $errors['claimant_id'], 'Error message should mention relationship');
    }

    /**
     * Test that valid relationships pass validation
     *
     * @return void
     */
    public function testValidateValidRelationships()
    {
        // Create a policy
        $policy = Policy::factory()->create();
        
        // Create a loss
        $loss = Loss::factory()->create();
        
        // Associate the loss with the policy
        MapPolicyLoss::create([
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        // Create a claimant
        $claimant = Claimant::factory()->create();
        
        // Associate the claimant with the loss
        MapLossClaimant::create([
            'loss_id' => $loss->id,
            'claimant_id' => $claimant->id,
            'status_id' => 1,
            'created_by' => 1,
            'updated_by' => 1
        ]);
        
        $metadata = [
            'policy_id' => $policy->id,
            'loss_id' => $loss->id,
            'claimant_id' => $claimant->id
        ];
        
        $errors = $this->metadataService->validateMetadataRelationships($metadata);
        
        $this->assertEmpty($errors, 'Valid relationships should pass validation');
    }

    /**
     * Test that DocumentUpdateRequest contains the expected validation rules
     *
     * @return void
     */
    public function testDocumentUpdateRequestValidationRules()
    {
        $request = new DocumentUpdateRequest();
        $rules = $request->rules();
        
        // Check that key fields have validation rules
        $this->assertArrayHasKey('policy_id', $rules, 'Should have validation rule for policy_id');
        $this->assertArrayHasKey('loss_id', $rules, 'Should have validation rule for loss_id');
        $this->assertArrayHasKey('claimant_id', $rules, 'Should have validation rule for claimant_id');
        $this->assertArrayHasKey('producer_id', $rules, 'Should have validation rule for producer_id');
        
        // Check that loss_id depends on policy_id (required_with claimant_id)
        $this->assertStringContainsString('required_with:claimant_id', $rules['loss_id'], 'Loss should be required when claimant is specified');
        
        // Check that fields use the exists validation rule
        $this->assertStringContainsString('exists:policy,id', $rules['policy_id'], 'Should validate that policy exists');
        $this->assertStringContainsString('exists:loss,id', $rules['loss_id'], 'Should validate that loss exists');
        $this->assertStringContainsString('exists:claimant,id', $rules['claimant_id'], 'Should validate that claimant exists');
        $this->assertStringContainsString('exists:producer,id', $rules['producer_id'], 'Should validate that producer exists');
    }

    /**
     * Test that DocumentUpdateRequest adds additional validation rules via withValidator
     *
     * @return void
     */
    public function testDocumentUpdateRequestWithValidatorHook()
    {
        // Create a mock validator
        $validator = Mockery::mock(Validator::class);
        
        // The validator should receive an 'after' method call
        $validator->shouldReceive('after')
            ->once()
            ->andReturnUsing(function ($callback) use ($validator) {
                // The validator errors object
                $errors = Mockery::mock('Illuminate\Support\MessageBag');
                $errors->shouldReceive('add')->times(0);
                
                // Setup the validator to have methods used in the callback
                $validator->shouldReceive('errors')->andReturn($errors);
                
                // Call the callback with our mocked validator
                $callback($validator);
                
                return $validator;
            });
        
        // Create a new DocumentUpdateRequest
        $request = new DocumentUpdateRequest();
        
        // Call the withValidator method
        $request->withValidator($validator);
        
        // No assertions needed, if the mock expectations are met, the test passes
        $this->assertTrue(true);
    }
}