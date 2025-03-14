<?php

namespace App\Services;

use App\Models\Loss;
use App\Models\Policy;
use App\Models\Claimant;
use App\Models\MapPolicyLoss;
use App\Models\MapLossClaimant;
use Illuminate\Support\Facades\DB; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Exception; // php 8.2
use Carbon\Carbon; // ^2.0

/**
 * Service class responsible for managing loss-related operations including retrieval, 
 * filtering, and relationship management with policies and claimants. This service 
 * provides methods to retrieve losses, manage loss metadata, and handle relationships 
 * with policies and claimants.
 */
class LossManager
{
    /**
     * Constructor for the LossManager service
     */
    public function __construct()
    {
        // Initialize the service
    }

    /**
     * Retrieves a loss by ID with optional relationship loading
     *
     * @param int $lossId
     * @param array $relations
     * @return \App\Models\Loss|null The loss instance or null if not found
     */
    public function getLoss(int $lossId, array $relations = [])
    {
        try {
            $loss = Loss::find($lossId);
            
            if ($loss && !empty($relations)) {
                $loss->load($relations);
            }
            
            return $loss;
        } catch (Exception $e) {
            Log::error('Error retrieving loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'relations' => $relations,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Retrieves a paginated list of losses with optional filtering
     *
     * @param array $filters
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDirection
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of losses
     */
    public function getLosses(
        array $filters = [],
        int $perPage = 15,
        string $sortBy = 'date',
        string $sortDirection = 'desc'
    ) {
        try {
            $query = Loss::query();
            
            // Apply filters if provided
            if (!empty($filters)) {
                $query = $this->applyFilters($query, $filters);
            }
            
            // Apply search filter if provided
            if (isset($filters['search']) && !empty($filters['search'])) {
                $query = $this->applySearch($query, $filters['search']);
            }
            
            // Apply sorting
            $query = $this->applySorting($query, $sortBy, $sortDirection);
            
            // Eager load common relationships
            $query->with(['policies', 'claimants']);
            
            // Paginate the results
            return $query->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error retrieving losses: ' . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);
            
            // Return an empty paginator in case of error
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }
    }

    /**
     * Retrieves losses associated with a specific policy for dropdown selection
     *
     * @param int $policyId
     * @param string $search
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection Collection of Loss instances
     */
    public function getLossesForPolicy(int $policyId, string $search = '', int $limit = 10)
    {
        try {
            // Verify the policy exists
            $policy = Policy::find($policyId);
            
            if (!$policy) {
                return collect();
            }
            
            // Retrieve losses for the policy
            $query = Loss::active()
                ->forPolicy($policyId)
                ->chronological();
            
            // Apply search if provided
            if (!empty($search)) {
                $query->search($search);
            }
            
            // Limit the results
            if ($limit > 0) {
                $query->limit($limit);
            }
            
            return $query->get();
        } catch (Exception $e) {
            Log::error('Error retrieving losses for policy: ' . $e->getMessage(), [
                'policy_id' => $policyId,
                'search' => $search,
                'exception' => $e
            ]);
            return collect();
        }
    }

    /**
     * Retrieves loss options for dropdown selection with optional filtering
     *
     * @param int $policyId
     * @param string $search
     * @param int $limit
     * @return array Array of loss options formatted for dropdown
     */
    public function getLossOptions(int $policyId, string $search = '', int $limit = 10)
    {
        $losses = $this->getLossesForPolicy($policyId, $search, $limit);
        
        // Map losses to dropdown options format
        return $losses->map(function ($loss) {
            return [
                'id' => $loss->id,
                'label' => $loss->display_name,
                'value' => $loss->id,
                'metadata' => [
                    'date' => $loss->formatted_date,
                    'name' => $loss->name,
                    'description' => $loss->description
                ]
            ];
        })->toArray();
    }

    /**
     * Retrieves claimants associated with a specific loss
     *
     * @param int $lossId
     * @param string $search
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection Collection of Claimant instances
     */
    public function getClaimantsForLoss(int $lossId, string $search = '', int $limit = 10)
    {
        try {
            // Verify the loss exists
            $loss = Loss::find($lossId);
            
            if (!$loss) {
                return collect();
            }
            
            // Retrieve claimants for the loss
            $query = Claimant::active()->forLoss($lossId);
            
            // Apply search if provided
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('name', function ($q) use ($search) {
                        $q->where('value', 'like', "%{$search}%");
                    });
                });
            }
            
            // Limit the results
            if ($limit > 0) {
                $query->limit($limit);
            }
            
            return $query->get();
        } catch (Exception $e) {
            Log::error('Error retrieving claimants for loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'search' => $search,
                'exception' => $e
            ]);
            return collect();
        }
    }

    /**
     * Retrieves claimant options for dropdown selection with optional filtering
     *
     * @param int $lossId
     * @param string $search
     * @param int $limit
     * @return array Array of claimant options formatted for dropdown
     */
    public function getClaimantOptions(int $lossId, string $search = '', int $limit = 10)
    {
        $claimants = $this->getClaimantsForLoss($lossId, $search, $limit);
        
        // Map claimants to dropdown options format
        return $claimants->map(function ($claimant) {
            return [
                'id' => $claimant->id,
                'label' => $claimant->display_name,
                'value' => $claimant->id,
                'metadata' => [
                    'type' => $claimant->claimant_type_id,
                    'policy_id' => $claimant->policy_id
                ]
            ];
        })->toArray();
    }

    /**
     * Creates a new loss with the provided data
     *
     * @param array $data
     * @param int $userId
     * @return \App\Models\Loss|null The created loss instance or null if creation failed
     */
    public function createLoss(array $data, int $userId)
    {
        try {
            DB::beginTransaction();
            
            // Set created_by and updated_by
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;
            
            // Create the loss
            $loss = Loss::create($data);
            
            // Create policy-loss relationship if policy_id is provided
            if (isset($data['policy_id']) && !empty($data['policy_id'])) {
                $this->addPolicyToLoss($loss->id, $data['policy_id'], $userId);
            }
            
            DB::commit();
            return $loss;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating loss: ' . $e->getMessage(), [
                'data' => $data,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Updates a loss with the provided data
     *
     * @param int $lossId
     * @param array $data
     * @param int $userId
     * @return \App\Models\Loss|null The updated loss instance or null if update failed
     */
    public function updateLoss(int $lossId, array $data, int $userId)
    {
        try {
            $loss = Loss::find($lossId);
            
            if (!$loss) {
                return null;
            }
            
            DB::beginTransaction();
            
            // Set updated_by
            $data['updated_by'] = $userId;
            
            // Update the loss
            $loss->update($data);
            
            // Handle policy relationship changes if policy_id is provided
            if (isset($data['policy_id'])) {
                $currentPolicyIds = $loss->mapPolicyLosses()->pluck('policy_id')->toArray();
                
                // If policy_id changed, update the relationship
                if (!in_array($data['policy_id'], $currentPolicyIds)) {
                    // Remove existing policy relationships
                    foreach ($currentPolicyIds as $currentPolicyId) {
                        $this->removePolicyFromLoss($loss->id, $currentPolicyId, $userId);
                    }
                    
                    // Add new policy relationship
                    if (!empty($data['policy_id'])) {
                        $this->addPolicyToLoss($loss->id, $data['policy_id'], $userId);
                    }
                }
            }
            
            DB::commit();
            return $loss;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'data' => $data,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Deletes a loss (soft delete)
     *
     * @param int $lossId
     * @param int $userId
     * @return bool True if the operation was successful, false otherwise
     */
    public function deleteLoss(int $lossId, int $userId)
    {
        try {
            $loss = Loss::find($lossId);
            
            if (!$loss) {
                return false;
            }
            
            // Check if the loss has associated documents
            if ($loss->hasDocuments()) {
                // Cannot delete a loss with associated documents
                return false;
            }
            
            DB::beginTransaction();
            
            // Soft delete the loss
            $loss->update(['updated_by' => $userId]);
            $loss->delete();
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Restores a deleted loss
     *
     * @param int $lossId
     * @param int $userId
     * @return bool True if the operation was successful, false otherwise
     */
    public function restoreLoss(int $lossId, int $userId)
    {
        try {
            $loss = Loss::withTrashed()->find($lossId);
            
            if (!$loss || !$loss->trashed()) {
                return false;
            }
            
            DB::beginTransaction();
            
            // Restore the loss
            $loss->restore();
            $loss->update(['updated_by' => $userId]);
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error restoring loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Associates a policy with a loss
     *
     * @param int $lossId
     * @param int $policyId
     * @param int $userId
     * @return bool True if the operation was successful, false otherwise
     */
    public function addPolicyToLoss(int $lossId, int $policyId, int $userId)
    {
        try {
            // Verify both entities exist
            $loss = Loss::find($lossId);
            $policy = Policy::find($policyId);
            
            if (!$loss || !$policy) {
                return false;
            }
            
            DB::beginTransaction();
            
            // Check if relationship already exists
            $exists = MapPolicyLoss::where('policy_id', $policyId)
                ->where('loss_id', $lossId)
                ->exists();
            
            if (!$exists) {
                // Create the relationship
                MapPolicyLoss::create([
                    'policy_id' => $policyId,
                    'loss_id' => $lossId,
                    'description' => 'Associated via LossManager',
                    'status_id' => 1, // Active status
                    'created_by' => $userId,
                    'updated_by' => $userId
                ]);
            }
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error adding policy to loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'policy_id' => $policyId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Removes a policy association from a loss
     *
     * @param int $lossId
     * @param int $policyId
     * @param int $userId
     * @return bool True if the operation was successful, false otherwise
     */
    public function removePolicyFromLoss(int $lossId, int $policyId, int $userId)
    {
        try {
            // Verify both entities exist
            $loss = Loss::find($lossId);
            $policy = Policy::find($policyId);
            
            if (!$loss || !$policy) {
                return false;
            }
            
            DB::beginTransaction();
            
            // Delete the relationship
            MapPolicyLoss::where('policy_id', $policyId)
                ->where('loss_id', $lossId)
                ->delete();
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error removing policy from loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'policy_id' => $policyId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Associates a claimant with a loss
     *
     * @param int $lossId
     * @param int $claimantId
     * @param int $userId
     * @return bool True if the operation was successful, false otherwise
     */
    public function addClaimantToLoss(int $lossId, int $claimantId, int $userId)
    {
        try {
            // Verify both entities exist
            $loss = Loss::find($lossId);
            $claimant = Claimant::find($claimantId);
            
            if (!$loss || !$claimant) {
                return false;
            }
            
            DB::beginTransaction();
            
            // Check if relationship already exists
            $exists = MapLossClaimant::where('loss_id', $lossId)
                ->where('claimant_id', $claimantId)
                ->exists();
            
            if (!$exists) {
                // Create the relationship
                MapLossClaimant::create([
                    'loss_id' => $lossId,
                    'claimant_id' => $claimantId,
                    'description' => 'Associated via LossManager',
                    'status_id' => 1, // Active status
                    'created_by' => $userId,
                    'updated_by' => $userId
                ]);
            }
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error adding claimant to loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'claimant_id' => $claimantId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Removes a claimant association from a loss
     *
     * @param int $lossId
     * @param int $claimantId
     * @param int $userId
     * @return bool True if the operation was successful, false otherwise
     */
    public function removeClaimantFromLoss(int $lossId, int $claimantId, int $userId)
    {
        try {
            // Verify both entities exist
            $loss = Loss::find($lossId);
            $claimant = Claimant::find($claimantId);
            
            if (!$loss || !$claimant) {
                return false;
            }
            
            DB::beginTransaction();
            
            // Delete the relationship
            MapLossClaimant::where('loss_id', $lossId)
                ->where('claimant_id', $claimantId)
                ->delete();
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error removing claimant from loss: ' . $e->getMessage(), [
                'loss_id' => $lossId,
                'claimant_id' => $claimantId,
                'user_id' => $userId,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Applies filters to the loss query based on provided filters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder with filters applied
     */
    public function applyFilters($query, array $filters)
    {
        // Filter by policy_id
        if (isset($filters['policy_id']) && !empty($filters['policy_id'])) {
            $query->forPolicy($filters['policy_id']);
        }
        
        // Filter by date range
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->where('date', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->where('date', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
        
        // Filter by status
        if (isset($filters['status_id']) && !empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }
        
        return $query;
    }

    /**
     * Applies search filter to the loss query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder with search filter applied
     */
    public function applySearch($query, string $search)
    {
        if (empty($search)) {
            return $query;
        }
        
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Applies sorting to the loss query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortBy
     * @param string $sortDirection
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder with sorting applied
     */
    public function applySorting($query, string $sortBy, string $sortDirection)
    {
        // Validate sort direction
        $sortDirection = strtolower($sortDirection);
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc'; // Default to descending if invalid
        }
        
        // Handle special sort fields
        switch ($sortBy) {
            case 'date':
            case 'name':
            case 'created_at':
            case 'updated_at':
                $query->orderBy($sortBy, $sortDirection);
                break;
                
            case 'policy':
                // Join with policies and sort by policy number
                $query->join('map_policy_loss', 'loss.id', '=', 'map_policy_loss.loss_id')
                    ->join('policy', 'map_policy_loss.policy_id', '=', 'policy.id')
                    ->orderBy('policy.number', $sortDirection)
                    ->select('loss.*'); // Ensure we only get loss columns
                break;
                
            default:
                // Default sort by date descending
                $query->orderBy('date', 'desc');
                break;
        }
        
        return $query;
    }
}