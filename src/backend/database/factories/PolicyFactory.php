<?php

namespace Database\Factories;

use App\Models\Policy;
use App\Models\PolicyPrefix;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory; // laravel/framework ^10.0

/**
 * Laravel factory for generating test instances of the Policy model.
 * This factory creates realistic policy data for testing the Documents View feature.
 */
class PolicyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Policy::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $effectiveDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $inceptionDate = $this->faker->dateTimeBetween('-2 years', $effectiveDate);
        $expirationDate = $this->faker->dateTimeBetween('+1 day', '+1 year');
        $renewalDate = $this->faker->dateTimeBetween($expirationDate, '+1 month');

        return [
            'policy_prefix_id' => PolicyPrefix::factory(),
            'number' => $this->faker->numerify('#####'),
            'policy_type_id' => $this->faker->numberBetween(1, 3), // 1=auto, 2=home, 3=life
            'effective_date' => $effectiveDate,
            'inception_date' => $inceptionDate,
            'expiration_date' => $expirationDate,
            'renewal_date' => $renewalDate,
            'status_id' => 1, // Active status
            'term_id' => $this->faker->numberBetween(1, 3), // Valid term types
            'description' => $this->faker->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * State method to associate the policy with a specific policy prefix.
     *
     * @param int $policyPrefixId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withPolicyPrefix($policyPrefixId)
    {
        return $this->state(function (array $attributes) use ($policyPrefixId) {
            return [
                'policy_prefix_id' => $policyPrefixId,
            ];
        });
    }

    /**
     * State method to create an active policy.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            $now = now();
            return [
                'status_id' => 1,
                'effective_date' => $now->copy()->subMonths(6),
                'expiration_date' => $now->copy()->addMonths(6),
            ];
        });
    }

    /**
     * State method to create an expired policy.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function expired()
    {
        return $this->state(function (array $attributes) {
            $now = now();
            return [
                'effective_date' => $now->copy()->subYears(2),
                'expiration_date' => $now->copy()->subYears(1),
            ];
        });
    }

    /**
     * State method to create a future policy.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function future()
    {
        return $this->state(function (array $attributes) {
            $now = now();
            return [
                'effective_date' => $now->copy()->addMonths(1),
                'expiration_date' => $now->copy()->addMonths(13),
            ];
        });
    }

    /**
     * State method to associate the policy with a specific user as creator and updater.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
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

    /**
     * State method to configure the policy to have associated producers.
     *
     * @param int $count
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withProducers($count = 1)
    {
        return $this->afterCreating(function (Policy $policy) use ($count) {
            // In a real application, this would create producers and establish 
            // relationships in the map_producer_policy table
            $producerFactory = \Database\Factories\ProducerFactory::new();
            $producers = $producerFactory->count($count)->create();
            
            foreach ($producers as $producer) {
                $policy->producers()->attach($producer->id, [
                    'description' => $this->faker->sentence(),
                    'status_id' => 1,
                    'created_by' => $policy->created_by,
                    'updated_by' => $policy->updated_by
                ]);
            }
        });
    }

    /**
     * State method to configure the policy to have associated losses.
     *
     * @param int $count
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withLosses($count = 1)
    {
        return $this->afterCreating(function (Policy $policy) use ($count) {
            // In a real application, this would create losses and establish
            // relationships in the map_policy_loss table
            $lossFactory = \Database\Factories\LossFactory::new();
            $losses = $lossFactory->count($count)->create();
            
            foreach ($losses as $loss) {
                $policy->losses()->attach($loss->id, [
                    'description' => $this->faker->sentence(),
                    'status_id' => 1,
                    'created_by' => $policy->created_by,
                    'updated_by' => $policy->updated_by
                ]);
            }
        });
    }

    /**
     * State method to configure the policy to have associated documents.
     *
     * @param int $count
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withDocuments($count = 1)
    {
        return $this->afterCreating(function (Policy $policy) use ($count) {
            // In a real application, this would create documents with this policy_id
            $documentFactory = \Database\Factories\DocumentFactory::new();
            $documentFactory->count($count)->create([
                'policy_id' => $policy->id,
                'created_by' => $policy->created_by,
                'updated_by' => $policy->updated_by
            ]);
        });
    }

    /**
     * State method to create an auto insurance policy.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function autoPolicy()
    {
        return $this->state(function (array $attributes) {
            return [
                'policy_type_id' => 1, // Auto insurance
                'description' => $this->faker->randomElement([
                    'Auto insurance policy covering personal vehicles',
                    'Comprehensive auto coverage with collision protection',
                    'Liability auto insurance for primary driver',
                    'Multi-vehicle auto insurance policy'
                ]),
            ];
        });
    }

    /**
     * State method to create a home insurance policy.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function homePolicy()
    {
        return $this->state(function (array $attributes) {
            return [
                'policy_type_id' => 2, // Home insurance
                'description' => $this->faker->randomElement([
                    'Homeowners insurance with flood coverage',
                    'Standard home insurance policy',
                    'Residential property insurance with liability',
                    'Home insurance with additional riders'
                ]),
            ];
        });
    }

    /**
     * State method to create a life insurance policy.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function lifePolicy()
    {
        return $this->state(function (array $attributes) {
            return [
                'policy_type_id' => 3, // Life insurance
                'description' => $this->faker->randomElement([
                    'Term life insurance policy',
                    'Whole life insurance with investment component',
                    'Universal life insurance policy',
                    'Group life insurance for employees'
                ]),
            ];
        });
    }
}