<?php

namespace Database\Factories;

use App\Models\Loss;  // laravel/framework ^10.0
use App\Models\User;  // laravel/framework ^10.0
use Illuminate\Database\Eloquent\Factories\Factory;  // laravel/framework ^10.0
use Faker\Generator as Faker;  // fakerphp/faker ^1.9.1
use Carbon\Carbon;  // nesbot/carbon ^2.0

/**
 * Factory for generating test instances of the Loss model.
 * 
 * This factory creates realistic loss data for testing the Documents View feature,
 * particularly for loss-related metadata fields in document management and the
 * dynamic dropdown controls where Loss Sequence options are filtered based on the selected Policy Number.
 */
class LossFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loss::class;

    /**
     * Define the model's default state.
     *
     * @return array Array of attributes for creating a Loss model instance
     */
    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['Vehicle Accident', 'Property Damage', 'Liability Incident', 'Medical Claim']),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'description' => $this->faker->sentence(10),
            'status_id' => 1, // Active status
            'loss_type_id' => $this->faker->numberBetween(1, 4),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * State method to associate the loss with a specific policy through the pivot table
     *
     * @param int $policyId
     * @return Factory Modified factory instance with policy association configuration
     */
    public function forPolicy($policyId)
    {
        return $this->afterCreating(function (Loss $loss) use ($policyId) {
            $loss->policies()->attach($policyId, [
                'description' => 'Loss-Policy association created for testing',
                'status_id' => 1,
                'created_by' => $loss->created_by,
                'updated_by' => $loss->updated_by,
            ]);
        });
    }

    /**
     * State method to configure the loss to have associated claimants
     *
     * @param int $count
     * @return Factory Modified factory instance with claimant association configuration
     */
    public function withClaimants($count = 1)
    {
        return $this->afterCreating(function (Loss $loss) use ($count) {
            // Assumes Claimant model exists and has a factory
            $claimants = \App\Models\Claimant::factory()->count($count)->create();
            
            foreach ($claimants as $claimant) {
                $loss->claimants()->attach($claimant->id, [
                    'description' => 'Loss-Claimant association created for testing',
                    'status_id' => 1,
                    'created_by' => $loss->created_by,
                    'updated_by' => $loss->updated_by,
                ]);
            }
        });
    }

    /**
     * State method to create a vehicle accident loss
     *
     * @return Factory Modified factory instance for creating a vehicle accident loss
     */
    public function vehicleAccident()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Vehicle Accident',
                'loss_type_id' => 1, // Vehicle type
                'description' => 'Vehicle accident involving ' . $this->faker->randomElement(['car', 'truck', 'motorcycle', 'SUV']) . ' on ' . $this->faker->date(),
            ];
        });
    }

    /**
     * State method to create a property damage loss
     *
     * @return Factory Modified factory instance for creating a property damage loss
     */
    public function propertyDamage()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Property Damage',
                'loss_type_id' => 2, // Property type
                'description' => 'Property damage to ' . $this->faker->randomElement(['home', 'office', 'building', 'warehouse']) . ' due to ' . $this->faker->randomElement(['fire', 'water', 'wind', 'vandalism']),
            ];
        });
    }

    /**
     * State method to create a liability incident loss
     *
     * @return Factory Modified factory instance for creating a liability incident loss
     */
    public function liabilityIncident()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Liability Incident',
                'loss_type_id' => 3, // Liability type
                'description' => 'Liability incident involving ' . $this->faker->randomElement(['slip and fall', 'defective product', 'professional negligence', 'workplace injury']),
            ];
        });
    }

    /**
     * State method to create a medical claim loss
     *
     * @return Factory Modified factory instance for creating a medical claim loss
     */
    public function medicalClaim()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Medical Claim',
                'loss_type_id' => 4, // Medical type
                'description' => 'Medical claim for ' . $this->faker->randomElement(['surgery', 'treatment', 'therapy', 'medication']) . ' related to ' . $this->faker->randomElement(['illness', 'injury', 'accident', 'condition']),
            ];
        });
    }

    /**
     * State method to set a custom date for the loss
     *
     * @param string $date
     * @return Factory Modified factory instance with custom date
     */
    public function withCustomDate($date)
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'date' => new Carbon($date),
            ];
        });
    }

    /**
     * State method to create a recent loss (within the last month)
     *
     * @return Factory Modified factory instance for creating a recent loss
     */
    public function recent()
    {
        return $this->state(function (array $attributes) {
            return [
                'date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }

    /**
     * State method to associate the loss with a specific user as creator and updater
     *
     * @param int $userId
     * @return Factory Modified factory instance with specified user
     */
    public function withUser($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'created_by' => $userId,
                'updated_by' => $userId,
            ];
        });
    }
}