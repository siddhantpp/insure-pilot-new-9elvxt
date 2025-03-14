<?php

namespace Database\Factories;

use App\Models\Claimant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory; // laravel/framework ^10.0

/**
 * Laravel factory for generating test instances of the Claimant model.
 * This factory creates realistic claimant data for testing the Documents View feature,
 * particularly for the claimant-related metadata fields in document management.
 */
class ClaimantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Claimant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name_id' => $this->faker->numberBetween(1, 10), // Assuming name_id references existing names
            'policy_id' => \App\Models\Policy::factory(),
            'loss_id' => \App\Models\Loss::factory(),
            'description' => $this->faker->paragraph(1),
            'status_id' => 1, // Active
            'claimant_type_id' => $this->faker->randomElement([
                Claimant::TYPE_INDIVIDUAL,
                Claimant::TYPE_BUSINESS,
                Claimant::TYPE_OTHER
            ]),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * State method to associate the claimant with a specific loss through the pivot table.
     *
     * @param int $lossId
     * @return Factory
     */
    public function forLoss(int $lossId)
    {
        // Create a description outside the callbacks to avoid $this context issues
        $description = $this->faker->sentence();
        
        return $this->state(function (array $attributes) use ($lossId) {
            return [
                'loss_id' => $lossId,
            ];
        })->afterCreating(function (Claimant $claimant) use ($lossId, $description) {
            // Ensure the relationship is established in the map_loss_claimant pivot table
            $claimant->losses()->attach($lossId, [
                'description' => $description,
                'status_id' => 1,
                'created_by' => $claimant->created_by,
                'updated_by' => $claimant->updated_by,
            ]);
        });
    }

    /**
     * State method to associate the claimant with a specific policy.
     *
     * @param int $policyId
     * @return Factory
     */
    public function forPolicy(int $policyId)
    {
        return $this->state(function (array $attributes) use ($policyId) {
            return [
                'policy_id' => $policyId,
            ];
        });
    }

    /**
     * State method to create an individual claimant.
     *
     * @return Factory
     */
    public function individual()
    {
        return $this->state(function (array $attributes) {
            return [
                'claimant_type_id' => Claimant::TYPE_INDIVIDUAL,
                'description' => 'Individual claimant - ' . $this->faker->name(),
            ];
        });
    }

    /**
     * State method to create a business claimant.
     *
     * @return Factory
     */
    public function business()
    {
        return $this->state(function (array $attributes) {
            return [
                'claimant_type_id' => Claimant::TYPE_BUSINESS,
                'description' => 'Business claimant - ' . $this->faker->company(),
            ];
        });
    }

    /**
     * State method to create a claimant of type 'other'.
     *
     * @return Factory
     */
    public function other()
    {
        return $this->state(function (array $attributes) {
            return [
                'claimant_type_id' => Claimant::TYPE_OTHER,
                'description' => 'Other claimant type - ' . $this->faker->word(),
            ];
        });
    }

    /**
     * State method to associate the claimant with a specific user as creator and updater.
     *
     * @param int $userId
     * @return Factory
     */
    public function withUser(int $userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'created_by' => $userId,
                'updated_by' => $userId,
            ];
        });
    }

    /**
     * State method to set a custom name for the claimant.
     *
     * @param string $name
     * @return Factory
     */
    public function withCustomName(string $name)
    {
        return $this->state(function (array $attributes) use ($name) {
            // Create or find a name record with the provided name value
            $nameRecord = \DB::table('name')->firstOrCreate(['value' => $name]);
            
            return [
                'name_id' => $nameRecord->id,
            ];
        });
    }
}