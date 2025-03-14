<?php

namespace App\Services;

use App\Models\Policy;
use App\Models\Producer;
use App\Models\Loss;
use App\Models\MapProducerPolicy;
use App\Models\MapPolicyLoss;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Exception; // 8.2
use Carbon\Carbon; // ^2.0

/**
 * Service class responsible for managing policy-related operations including retrieval, filtering,
 * and relationship management with producers, losses, and documents. This service provides methods
 * to retrieve policies, manage policy metadata, and handle relationships with other entities.
 */
class PolicyManager
{
    /**
     * The AuditLogger service instance.
     *
     * @var AuditLogger
     */
    protected $auditLogger;

    /**
     * Constructor for the PolicyManager service
     *
     * @param AuditLogger $auditLogger The audit logger service for tracking policy actions
     */
    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Retrieves a policy by ID with optional relationship loading
     *
     * @param int $policyId The ID of the policy to retrieve
     * @param array $relations Optional relationships to eager load
     * @return ?\App\Models\Policy The policy instance or null if not found
     */
    public function getPolicy(int $policyId, array $relations = [])
    {
        try {
            $query = Policy::query();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            return $query->find($policyId);
        } catch (Exception $e) {
            Log::error("PolicyManager: Error retrieving policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId
            ]);
            return null;
        }
    }

    /**
     * Retrieves a paginated list of policies with optional filtering
     *
     * @param array $filters Optional filters to apply (producer_id, effective_date, etc.)
     * @param int $perPage Number of items per page (default: 15)
     * @param string $sortBy Field to sort by (default: 'created_at')
     * @param string $sortDirection Sort direction ('asc' or 'desc', default: 'desc')
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of policies
     */
    public function getPolicies(array $filters = [], int $perPage = 15, string $sortBy = 'created_at', string $sortDirection = 'desc')
    {
        try {
            $query = Policy::query();
            
            // Apply filters
            if (!empty($filters)) {
                $query = $this->applyFilters($query, $filters);
            }
            
            // Apply search if provided
            if (isset($filters['search']) && !empty($filters['search'])) {
                $query = $this->applySearch($query, $filters['search']);
            }
            
            // Apply sorting
            $query = $this->applySorting($query, $sortBy, $sortDirection);
            
            // Eager load common relationships
            $query->with(['policyPrefix', 'producers']);
            
            // Return paginated results
            return $query->paginate($perPage);
        } catch (Exception $e) {
            Log::error("PolicyManager: Error retrieving policies", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            // Return empty paginator in case of error
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }
    }

    /**
     * Retrieves policy options for dropdown selection with optional filtering
     *
     * @param string $search Optional search term to filter policies
     * @param int $producerId Optional producer ID to filter policies
     * @param int $limit Maximum number of options to return (default: 50)
     * @return array Array of policy options formatted for dropdown
     */
    public function getPolicyOptions(string $search = '', ?int $producerId = null, int $limit = 50): array
    {
        try {
            // Start with active policies
            $query = Policy::active();
            
            // Filter by producer if provided
            if ($producerId) {
                $query->forProducer($producerId);
            }
            
            // Apply search if provided
            if (!empty($search)) {
                $query->search($search);
            }
            
            // Eager load policy prefix for formatted display
            $query->with('policyPrefix');
            
            // Limit the results
            $policies = $query->limit($limit)->get();
            
            // Format the results for dropdown
            return $policies->map(function ($policy) {
                return [
                    'id' => $policy->id,
                    'value' => $policy->id,
                    'label' => $policy->formatted_number,
                    'display_name' => $policy->display_name,
                    'policy_prefix_id' => $policy->policy_prefix_id,
                    'number' => $policy->number,
                    'effective_date' => $policy->formatted_effective_date,
                    'expiration_date' => $policy->formatted_expiration_date
                ];
            })->toArray();
        } catch (Exception $e) {
            Log::error("PolicyManager: Error retrieving policy options", [
                'error' => $e->getMessage(),
                'search' => $search,
                'producer_id' => $producerId
            ]);
            
            return [];
        }
    }

    /**
     * Retrieves losses associated with a specific policy
     *
     * @param int $policyId The ID of the policy
     * @return \Illuminate\Database\Eloquent\Collection Collection of Loss instances
     */
    public function getLossesForPolicy(int $policyId)
    {
        try {
            // Verify the policy exists
            $policy = Policy::find($policyId);
            if (!$policy) {
                return collect();
            }
            
            // Retrieve losses using the Loss model's forPolicy scope
            return Loss::forPolicy($policyId)->chronological()->get();
        } catch (Exception $e) {
            Log::error("PolicyManager: Error retrieving losses for policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId
            ]);
            
            return collect();
        }
    }

    /**
     * Retrieves documents associated with a specific policy
     *
     * @param int $policyId The ID of the policy
     * @param int $perPage Number of items per page (default: 15)
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of documents
     */
    public function getDocumentsForPolicy(int $policyId, int $perPage = 15)
    {
        try {
            // Verify the policy exists
            $policy = Policy::with('documents')->find($policyId);
            if (!$policy) {
                return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
            }
            
            // Paginate the documents
            return $policy->documents()->paginate($perPage);
        } catch (Exception $e) {
            Log::error("PolicyManager: Error retrieving documents for policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId
            ]);
            
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }
    }

    /**
     * Retrieves producers associated with a specific policy
     *
     * @param int $policyId The ID of the policy
     * @return \Illuminate\Database\Eloquent\Collection Collection of Producer instances
     */
    public function getProducersForPolicy(int $policyId)
    {
        try {
            // Verify the policy exists
            $policy = Policy::with('producers')->find($policyId);
            if (!$policy) {
                return collect();
            }
            
            return $policy->producers;
        } catch (Exception $e) {
            Log::error("PolicyManager: Error retrieving producers for policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId
            ]);
            
            return collect();
        }
    }

    /**
     * Creates a new policy with the provided data
     *
     * @param array $data The policy data
     * @param int $userId The ID of the user creating the policy
     * @return ?\App\Models\Policy The created policy instance or null if creation failed
     */
    public function createPolicy(array $data, int $userId)
    {
        try {
            // Begin a database transaction
            DB::beginTransaction();
            
            // Set creator and updater
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;
            
            // Create the policy
            $policy = Policy::create($data);
            
            // If producer_id is provided, create the producer-policy relationship
            if (isset($data['producer_id']) && !empty($data['producer_id'])) {
                $this->addProducerToPolicy($policy->id, $data['producer_id'], $userId);
            }
            
            // Commit the transaction
            DB::commit();
            
            return $policy;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            Log::error("PolicyManager: Error creating policy", [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => $userId
            ]);
            
            return null;
        }
    }

    /**
     * Updates a policy with the provided data
     *
     * @param int $policyId The ID of the policy to update
     * @param array $data The updated policy data
     * @param int $userId The ID of the user updating the policy
     * @return ?\App\Models\Policy The updated policy instance or null if update failed
     */
    public function updatePolicy(int $policyId, array $data, int $userId)
    {
        try {
            // Retrieve the policy
            $policy = Policy::find($policyId);
            if (!$policy) {
                return null;
            }
            
            // Begin a database transaction
            DB::beginTransaction();
            
            // Set updater
            $data['updated_by'] = $userId;
            
            // Update the policy
            $policy->update($data);
            
            // Handle producer change if needed
            if (isset($data['producer_id'])) {
                // Get current producers
                $currentProducerIds = $policy->producers()->pluck('producer_id')->toArray();
                
                // If producer is different, update the relationship
                if (!in_array($data['producer_id'], $currentProducerIds)) {
                    // Remove existing producer relationships
                    MapProducerPolicy::where('policy_id', $policyId)->delete();
                    
                    // Add the new producer relationship
                    $this->addProducerToPolicy($policyId, $data['producer_id'], $userId);
                }
            }
            
            // Commit the transaction
            DB::commit();
            
            return $policy->fresh();
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            Log::error("PolicyManager: Error updating policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId,
                'data' => $data,
                'user_id' => $userId
            ]);
            
            return null;
        }
    }

    /**
     * Deletes a policy (soft delete)
     *
     * @param int $policyId The ID of the policy to delete
     * @param int $userId The ID of the user deleting the policy
     * @return bool True if the operation was successful, false otherwise
     */
    public function deletePolicy(int $policyId, int $userId): bool
    {
        try {
            // Retrieve the policy
            $policy = Policy::find($policyId);
            if (!$policy) {
                return false;
            }
            
            // Check if the policy has any associated documents
            if ($policy->hasDocuments()) {
                // Cannot delete a policy with associated documents
                return false;
            }
            
            // Begin a database transaction
            DB::beginTransaction();
            
            // Soft delete the policy
            $policy->delete();
            
            // Commit the transaction
            DB::commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            Log::error("PolicyManager: Error deleting policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId,
                'user_id' => $userId
            ]);
            
            return false;
        }
    }

    /**
     * Restores a deleted policy
     *
     * @param int $policyId The ID of the policy to restore
     * @param int $userId The ID of the user restoring the policy
     * @return bool True if the operation was successful, false otherwise
     */
    public function restorePolicy(int $policyId, int $userId): bool
    {
        try {
            // Retrieve the trashed policy
            $policy = Policy::withTrashed()->find($policyId);
            if (!$policy || !$policy->trashed()) {
                return false;
            }
            
            // Begin a database transaction
            DB::beginTransaction();
            
            // Restore the policy
            $policy->restore();
            
            // Commit the transaction
            DB::commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            Log::error("PolicyManager: Error restoring policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId,
                'user_id' => $userId
            ]);
            
            return false;
        }
    }

    /**
     * Associates a producer with a policy
     *
     * @param int $policyId The ID of the policy
     * @param int $producerId The ID of the producer
     * @param int $userId The ID of the user making the association
     * @return bool True if the operation was successful, false otherwise
     */
    public function addProducerToPolicy(int $policyId, int $producerId, int $userId): bool
    {
        try {
            // Verify the policy and producer exist
            $policy = Policy::find($policyId);
            $producer = Producer::find($producerId);
            
            if (!$policy || !$producer) {
                return false;
            }
            
            // Begin a database transaction
            DB::beginTransaction();
            
            // Create the association
            MapProducerPolicy::create([
                'policy_id' => $policyId,
                'producer_id' => $producerId,
                'description' => 'Producer associated with policy',
                'status_id' => 1, // Assuming 1 is active status
                'created_by' => $userId,
                'updated_by' => $userId
            ]);
            
            // Commit the transaction
            DB::commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            Log::error("PolicyManager: Error adding producer to policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId,
                'producer_id' => $producerId,
                'user_id' => $userId
            ]);
            
            return false;
        }
    }

    /**
     * Removes a producer association from a policy
     *
     * @param int $policyId The ID of the policy
     * @param int $producerId The ID of the producer
     * @param int $userId The ID of the user removing the association
     * @return bool True if the operation was successful, false otherwise
     */
    public function removeProducerFromPolicy(int $policyId, int $producerId, int $userId): bool
    {
        try {
            // Verify the policy and producer exist
            $policy = Policy::find($policyId);
            $producer = Producer::find($producerId);
            
            if (!$policy || !$producer) {
                return false;
            }
            
            // Begin a database transaction
            DB::beginTransaction();
            
            // Remove the association
            MapProducerPolicy::where('policy_id', $policyId)
                ->where('producer_id', $producerId)
                ->delete();
            
            // Commit the transaction
            DB::commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            Log::error("PolicyManager: Error removing producer from policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId,
                'producer_id' => $producerId,
                'user_id' => $userId
            ]);
            
            return false;
        }
    }

    /**
     * Associates a loss with a policy
     *
     * @param int $policyId The ID of the policy
     * @param int $lossId The ID of the loss
     * @param int $userId The ID of the user making the association
     * @return bool True if the operation was successful, false otherwise
     */
    public function addLossToPolicy(int $policyId, int $lossId, int $userId): bool
    {
        try {
            // Verify the policy and loss exist
            $policy = Policy::find($policyId);
            $loss = Loss::find($lossId);
            
            if (!$policy || !$loss) {
                return false;
            }
            
            // Begin a database transaction
            DB::beginTransaction();
            
            // Create the association
            MapPolicyLoss::create([
                'policy_id' => $policyId,
                'loss_id' => $lossId,
                'description' => 'Loss associated with policy',
                'status_id' => 1, // Assuming 1 is active status
                'created_by' => $userId,
                'updated_by' => $userId
            ]);
            
            // Commit the transaction
            DB::commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            Log::error("PolicyManager: Error adding loss to policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId,
                'loss_id' => $lossId,
                'user_id' => $userId
            ]);
            
            return false;
        }
    }

    /**
     * Removes a loss association from a policy
     *
     * @param int $policyId The ID of the policy
     * @param int $lossId The ID of the loss
     * @param int $userId The ID of the user removing the association
     * @return bool True if the operation was successful, false otherwise
     */
    public function removeLossFromPolicy(int $policyId, int $lossId, int $userId): bool
    {
        try {
            // Verify the policy and loss exist
            $policy = Policy::find($policyId);
            $loss = Loss::find($lossId);
            
            if (!$policy || !$loss) {
                return false;
            }
            
            // Begin a database transaction
            DB::beginTransaction();
            
            // Remove the association
            MapPolicyLoss::where('policy_id', $policyId)
                ->where('loss_id', $lossId)
                ->delete();
            
            // Commit the transaction
            DB::commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            
            Log::error("PolicyManager: Error removing loss from policy", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId,
                'loss_id' => $lossId,
                'user_id' => $userId
            ]);
            
            return false;
        }
    }

    /**
     * Generates the URL for the policy view page
     *
     * @param int $policyId The ID of the policy
     * @return ?string The URL for the policy view or null if policy not found
     */
    public function getPolicyUrl(int $policyId): ?string
    {
        try {
            // Verify the policy exists
            $policy = Policy::find($policyId);
            if (!$policy) {
                return null;
            }
            
            // Generate the URL for the policy view
            return route('policies.show', ['policy' => $policyId]);
        } catch (Exception $e) {
            Log::error("PolicyManager: Error generating policy URL", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId
            ]);
            
            return null;
        }
    }

    /**
     * Checks if a policy is currently active based on effective and expiration dates
     *
     * @param int $policyId The ID of the policy
     * @return bool True if the policy is active, false otherwise
     */
    public function isPolicyActive(int $policyId): bool
    {
        try {
            // Retrieve the policy
            $policy = Policy::find($policyId);
            if (!$policy) {
                return false;
            }
            
            // Check if the policy is active based on dates
            return $policy->isActive();
        } catch (Exception $e) {
            Log::error("PolicyManager: Error checking if policy is active", [
                'error' => $e->getMessage(),
                'policy_id' => $policyId
            ]);
            
            return false;
        }
    }

    /**
     * Applies filters to the policy query based on provided filters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance
     * @param array $filters The filters to apply
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder with filters applied
     */
    protected function applyFilters($query, array $filters)
    {
        // Filter by producer
        if (isset($filters['producer_id']) && !empty($filters['producer_id'])) {
            $query->forProducer($filters['producer_id']);
        }
        
        // Filter by effective date range
        if (isset($filters['effective_date_from']) && !empty($filters['effective_date_from'])) {
            $query->where('effective_date', '>=', $filters['effective_date_from']);
        }
        
        if (isset($filters['effective_date_to']) && !empty($filters['effective_date_to'])) {
            $query->where('effective_date', '<=', $filters['effective_date_to']);
        }
        
        // Filter by expiration date range
        if (isset($filters['expiration_date_from']) && !empty($filters['expiration_date_from'])) {
            $query->where('expiration_date', '>=', $filters['expiration_date_from']);
        }
        
        if (isset($filters['expiration_date_to']) && !empty($filters['expiration_date_to'])) {
            $query->where('expiration_date', '<=', $filters['expiration_date_to']);
        }
        
        // Filter by status
        if (isset($filters['status_id']) && !empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }
        
        return $query;
    }

    /**
     * Applies search filter to the policy query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance
     * @param string $search The search term
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder with search filter applied
     */
    protected function applySearch($query, string $search)
    {
        if (empty($search)) {
            return $query;
        }
        
        return $query->where(function ($query) use ($search) {
            $query->where('number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Applies sorting to the policy query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance
     * @param string $sortBy The field to sort by
     * @param string $sortDirection The sort direction ('asc' or 'desc')
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder with sorting applied
     */
    protected function applySorting($query, string $sortBy, string $sortDirection)
    {
        // Validate sort direction
        $direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
        
        // Handle special sorting cases
        switch ($sortBy) {
            case 'formatted_number':
                // Sort by policy prefix and number
                $query->join('policy_prefix', 'policy.policy_prefix_id', '=', 'policy_prefix.id')
                    ->orderBy('policy_prefix.name', $direction)
                    ->orderBy('policy.number', $direction)
                    ->select('policy.*');
                break;
                
            case 'producer':
                // Sort by producer name
                $query->leftJoin('map_producer_policy', 'policy.id', '=', 'map_producer_policy.policy_id')
                    ->leftJoin('producer', 'map_producer_policy.producer_id', '=', 'producer.id')
                    ->orderBy('producer.name', $direction)
                    ->select('policy.*');
                break;
                
            default:
                // Default sorting
                $query->orderBy($sortBy, $direction);
                break;
        }
        
        return $query;
    }
}