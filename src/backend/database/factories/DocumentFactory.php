<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating test instances of the Document model.
 * This factory creates realistic document data for testing the Documents View feature,
 * including document metadata, relationships to policies, losses, claimants, producers, and document status.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->randomElement([
                'Policy Renewal Notice',
                'Claim Form',
                'Insurance Certificate',
                'Policy Change Request',
                'Cancellation Notice',
                'Appraisal Report',
                'Accident Report',
                'Medical Report',
                'Coverage Summary',
                'Premium Statement',
            ]),
            'date_received' => $this->faker->dateTimeBetween('-90 days', 'now'),
            'description' => $this->faker->sentence(),
            'signature_required' => $this->faker->boolean(30),
            'policy_id' => null, // Default to null, use withPolicy state to set
            'loss_id' => null, // Default to null, use withLoss state to set
            'claimant_id' => null, // Default to null, use withClaimant state to set
            'producer_id' => null, // Default to null, use withProducer state to set
            'status_id' => Document::STATUS_UNPROCESSED, // Default to unprocessed
            'created_by' => function () {
                return User::factory()->create()->id;
            },
            'updated_by' => function (array $attributes) {
                return $attributes['created_by'];
            },
        ];
    }

    /**
     * State method to associate the document with a specific policy.
     *
     * @param int $policyId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withPolicy($policyId)
    {
        return $this->state(function (array $attributes) use ($policyId) {
            return [
                'policy_id' => $policyId,
            ];
        });
    }

    /**
     * State method to associate the document with a specific loss.
     *
     * @param int $lossId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withLoss($lossId)
    {
        return $this->state(function (array $attributes) use ($lossId) {
            return [
                'loss_id' => $lossId,
            ];
        });
    }

    /**
     * State method to associate the document with a specific claimant.
     *
     * @param int $claimantId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withClaimant($claimantId)
    {
        return $this->state(function (array $attributes) use ($claimantId) {
            return [
                'claimant_id' => $claimantId,
            ];
        });
    }

    /**
     * State method to associate the document with a specific producer.
     *
     * @param int $producerId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withProducer($producerId)
    {
        return $this->state(function (array $attributes) use ($producerId) {
            return [
                'producer_id' => $producerId,
            ];
        });
    }

    /**
     * State method to create a processed document.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function processed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => Document::STATUS_PROCESSED,
            ];
        });
    }

    /**
     * State method to create a trashed document.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function trashed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status_id' => Document::STATUS_TRASHED,
            ];
        });
    }

    /**
     * State method to associate the document with a specific user as creator and updater.
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
     * State method to configure the document to have associated files.
     *
     * @param int $count Number of files to associate
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withFiles($count = 1)
    {
        return $this->afterCreating(function (Document $document) use ($count) {
            $document->files()->attach(
                \App\Models\File::factory()->count($count)->create()->pluck('id'),
                [
                    'description' => $this->faker->sentence(),
                    'status_id' => 1,
                    'created_by' => $document->created_by,
                    'updated_by' => $document->updated_by,
                ]
            );
        });
    }

    /**
     * State method to configure the document to have associated actions (history).
     *
     * @param int $count Number of actions to associate
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withActions($count = 1)
    {
        return $this->afterCreating(function (Document $document) use ($count) {
            $document->actions()->attach(
                \App\Models\Action::factory()->count($count)->create()->pluck('id'),
                [
                    'description' => $this->faker->sentence(),
                    'status_id' => 1,
                    'created_by' => $document->created_by,
                    'updated_by' => $document->updated_by,
                ]
            );
        });
    }

    /**
     * State method to create a policy-related document.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function policyDocument()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement([
                    'Policy Renewal Notice',
                    'Insurance Certificate',
                    'Policy Change Request',
                    'Cancellation Notice',
                    'Coverage Summary',
                    'Premium Statement',
                ]),
                'description' => 'Document related to policy details and coverage.',
            ];
        })->afterMaking(function (Document $document) {
            if (!$document->policy_id) {
                $document->policy_id = \App\Models\Policy::factory()->create()->id;
            }
        });
    }

    /**
     * State method to create a claim-related document.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function claimDocument()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement([
                    'Claim Form',
                    'Accident Report',
                    'Medical Report',
                    'Damage Assessment',
                    'Claim Settlement',
                    'Liability Statement',
                ]),
                'description' => 'Document related to a claim or loss event.',
            ];
        })->afterMaking(function (Document $document) {
            if (!$document->policy_id) {
                $document->policy_id = \App\Models\Policy::factory()->create()->id;
            }
            if (!$document->loss_id) {
                $document->loss_id = \App\Models\Loss::factory()->create()->id;
            }
            if (!$document->claimant_id) {
                $document->claimant_id = \App\Models\Claimant::factory()->create()->id;
            }
        });
    }

    /**
     * State method to create a producer-related document.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function producerDocument()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement([
                    'Producer Agreement',
                    'Commission Statement',
                    'Agency License',
                    'Producer Profile',
                    'Marketing Materials',
                ]),
                'description' => 'Document related to producer or agency relationship.',
            ];
        })->afterMaking(function (Document $document) {
            if (!$document->producer_id) {
                $document->producer_id = \App\Models\Producer::factory()->create()->id;
            }
        });
    }

    /**
     * State method to create a document that requires signature.
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
     * State method to create a document with a recent received date.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function recent()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_received' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * State method to create a document with an older received date.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function older()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_received' => $this->faker->dateTimeBetween('-90 days', '-30 days'),
            ];
        });
    }
}