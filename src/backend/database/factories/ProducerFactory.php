<?php

namespace Database\Factories;

use App\Models\Producer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producer>
 */
class ProducerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Producer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'producer_code_id' => $this->faker->numberBetween(1, 5),
            'number' => 'AG-' . $this->faker->numerify('######'),
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'status_id' => 1, // Active by default
            'producer_type_id' => $this->faker->numberBetween(1, 3),
            'signature_required' => $this->faker->boolean(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * State method to associate the producer with a specific producer code.
     *
     * @param int $producerCodeId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withProducerCode(int $producerCodeId)
    {
        return $this->state(function (array $attributes) use ($producerCodeId) {
            return [
                'producer_code_id' => $producerCodeId,
            ];
        });
    }

    /**
     * State method to create an active producer.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => 1,
            ];
        });
    }

    /**
     * State method to create an inactive producer.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => 2,
            ];
        });
    }

    /**
     * State method to associate the producer with a specific user as creator and updater.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
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
     * State method to configure the producer to have associated policies.
     *
     * @param int $count
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withPolicies(int $count = 1)
    {
        return $this->afterCreating(function (Producer $producer) use ($count) {
            $producer->policies()->attach(
                \App\Models\Policy::factory()->count($count)->create()->pluck('id'),
                [
                    'description' => $this->faker->sentence(),
                    'status_id' => 1,
                    'created_by' => $producer->created_by,
                    'updated_by' => $producer->updated_by,
                ]
            );
        });
    }

    /**
     * State method to configure the producer to have associated documents.
     *
     * @param int $count
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withDocuments(int $count = 1)
    {
        return $this->afterCreating(function (Producer $producer) use ($count) {
            \App\Models\Document::factory()->count($count)->create([
                'producer_id' => $producer->id,
                'created_by' => $producer->created_by,
                'updated_by' => $producer->updated_by,
            ]);
        });
    }

    /**
     * State method to create a producer that requires signature on documents.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function requiresSignature()
    {
        return $this->state(function (array $attributes) {
            return [
                'signature_required' => true,
            ];
        });
    }

    /**
     * State method to create a producer of agency type.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function agencyType()
    {
        return $this->state(function (array $attributes) {
            return [
                'producer_type_id' => 1,
                'name' => $this->faker->lastName() . ' Insurance Agency',
            ];
        });
    }

    /**
     * State method to create a producer of broker type.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function brokerType()
    {
        return $this->state(function (array $attributes) {
            return [
                'producer_type_id' => 2,
                'name' => $this->faker->lastName() . ' Insurance Brokers',
            ];
        });
    }
}