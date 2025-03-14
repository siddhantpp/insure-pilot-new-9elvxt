<?php

namespace App\Services;

use App\Models\Producer;
use App\Models\Policy;
use App\Models\MapProducerPolicy;
use Illuminate\Support\Facades\DB; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Exception; // 8.2
use Illuminate\Support\Collection; // ^10.0

/**
 * Service responsible for managing producer-related operations in the Insure Pilot system.
 * This service provides methods for retrieving, searching, and filtering producer data
 * for the Documents View feature.
 */
class ProducerManager
{
    /**
     * Creates a new ProducerManager instance
     */
    public function __construct()
    {
        // Initialize the service with no dependencies
    }

    /**
     * Retrieves a paginated list of producers with optional filtering and sorting
     *
     * @param int $perPage
     * @param string|null $sortBy
     * @param string|null $sortDirection
     * @param array|null $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of producers
     */
    public function getProducers(int $perPage = 15, ?string $sortBy = null, ?string $sortDirection = null, ?array $filters = null)
    {
        try {
            // Start with active producers query
            $query = Producer::active();

            // Apply filters if provided
            if ($filters) {
                // Apply search filter
                if (isset($filters['search']) && !empty($filters['search'])) {
                    $query->search($filters['search']);
                }

                // Filter by producers with policies
                if (isset($filters['withPolicies']) && $filters['withPolicies']) {
                    $query->withPolicies();
                }
            }

            // Apply sorting (default: number ASC)
            $sortBy = $sortBy ?? 'number';
            $sortDirection = $sortDirection ?? 'asc';
            $query->orderBy($sortBy, $sortDirection);

            // Paginate results with specified perPage value
            return $query->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error retrieving producers: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieves a specific producer by ID with optional relationships
     *
     * @param int $id
     * @param array|null $relations
     * @return \App\Models\Producer|null Producer model instance or null if not found
     */
    public function getProducer(int $id, ?array $relations = null): ?\App\Models\Producer
    {
        try {
            // Start with a query for the producer with the given ID
            $query = Producer::where('id', $id);

            // If relations are specified, eager load them
            if ($relations && is_array($relations)) {
                $query->with($relations);
            }

            // Return the producer or null if not found
            return $query->first();
        } catch (Exception $e) {
            Log::error("Error retrieving producer with ID {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves producer options formatted for dropdown selection
     *
     * @param string|null $search
     * @param bool|null $withPolicies
     * @param int $limit
     * @return array Array of producer options formatted for dropdown
     */
    public function getProducerOptions(?string $search = null, ?bool $withPolicies = false, int $limit = 100): array
    {
        try {
            // Start with active producers query
            $query = Producer::active();

            // If withPolicies is true, filter producers that have associated policies
            if ($withPolicies) {
                $query->withPolicies();
            }

            // If search term provided, filter producers by search term
            if ($search) {
                $query->search($search);
            }

            // Limit results to specified limit
            $producers = $query->limit($limit)->get();

            // Format results for dropdown with id, value, and label
            return $this->formatProducerOptions($producers);
        } catch (Exception $e) {
            Log::error('Error retrieving producer options: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves policies associated with a specific producer
     *
     * @param int $producerId
     * @param string|null $search
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection Collection of policies
     */
    public function getProducerPolicies(int $producerId, ?string $search = null, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        try {
            // Verify producer exists
            if (!$this->validateProducerExists($producerId)) {
                return collect([]);
            }

            // Query policies using scopeForProducer
            $query = Policy::forProducer($producerId);

            // If search term provided, filter policies by search term
            if ($search) {
                $query->search($search);
            }

            // Limit results to specified limit
            return $query->limit($limit)->get();
        } catch (Exception $e) {
            Log::error("Error retrieving policies for producer ID {$producerId}: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Generates a URL for the producer view page
     *
     * @param int $producerId
     * @return string|null URL for the producer view or null if producer not found
     */
    public function getProducerUrl(int $producerId): ?string
    {
        try {
            // Verify producer exists
            if (!$this->validateProducerExists($producerId)) {
                return null;
            }

            // Generate URL for producer view page
            return route('producers.show', ['id' => $producerId]);
        } catch (Exception $e) {
            Log::error("Error generating URL for producer ID {$producerId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Searches for producers based on search term and filters
     *
     * @param string $searchTerm
     * @param array|null $filters
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection Collection of matching producers
     */
    public function searchProducers(string $searchTerm, ?array $filters = null, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        try {
            // Start with active producers query
            $query = Producer::active();

            // Apply search term to filter by number, name, or description
            $query->search($searchTerm);

            // Apply additional filters if provided
            if ($filters) {
                // Filter by producers with policies
                if (isset($filters['withPolicies']) && $filters['withPolicies']) {
                    $query->withPolicies();
                }
            }

            // Limit results to specified limit
            return $query->limit($limit)->get();
        } catch (Exception $e) {
            Log::error("Error searching producers with term '{$searchTerm}': " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Formats producer query results into standardized dropdown options
     *
     * @param \Illuminate\Database\Eloquent\Collection $producers
     * @return array Formatted dropdown options
     */
    public function formatProducerOptions(\Illuminate\Database\Eloquent\Collection $producers): array
    {
        // Initialize empty options array
        $options = [];

        // For each producer, create an option with id, value, and label
        foreach ($producers as $producer) {
            $options[] = [
                'id' => $producer->id,
                'value' => $producer->number,
                'label' => $producer->display_name
            ];
        }

        // Return formatted options array
        return $options;
    }

    /**
     * Validates that a producer with the given ID exists
     *
     * @param int $producerId
     * @return bool True if producer exists, false otherwise
     */
    public function validateProducerExists(int $producerId): bool
    {
        // Query the database for a producer with the given ID
        return Producer::where('id', $producerId)->exists();
    }
}