<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\User;

/**
 * ActionFactory creates test instances of the Action model for testing the Document History & Audit Trail feature.
 * 
 * This factory generates realistic action data for testing document-related actions such as viewing,
 * editing, processing, trashing, and restoring documents.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Action>
 */
class ActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Action::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'record_id' => $this->faker->randomNumber(5),
            'action_type_id' => ActionType::factory(),
            'description' => $this->faker->sentence(),
            'status_id' => 1, // Active status
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Configure the action with a specific action type.
     *
     * @param int $actionTypeId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withActionType(int $actionTypeId)
    {
        return $this->state(function (array $attributes) use ($actionTypeId) {
            return [
                'action_type_id' => $actionTypeId,
            ];
        });
    }

    /**
     * Configure the action for a specific document.
     *
     * This will set the record_id to the document ID and create a relationship record
     * in the map_document_action pivot table.
     *
     * @param int $documentId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forDocument(int $documentId)
    {
        return $this->state(function (array $attributes) use ($documentId) {
            return [
                'record_id' => $documentId,
            ];
        })->afterCreating(function (Action $action, $attributes) use ($documentId) {
            // Create relationship in map_document_action
            $action->documents()->attach($documentId, [
                'status_id' => 1,
                'created_by' => $action->created_by,
                'updated_by' => $action->updated_by,
            ]);
        });
    }

    /**
     * Configure the action to be created by a specific user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function byUser(int $userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'created_by' => $userId,
                'updated_by' => $userId,
            ];
        });
    }

    /**
     * Configure the action as a document view action.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function viewAction()
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => 'Document viewed',
                'action_type_id' => ActionType::factory()->state([
                    'name' => 'view',
                    'description' => 'Document view action',
                ]),
            ];
        });
    }

    /**
     * Configure the action as a document edit action.
     *
     * @param string|null $fieldName The name of the field that was edited
     * @param string|null $oldValue The original value before the edit
     * @param string|null $newValue The new value after the edit
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function editAction(?string $fieldName = null, ?string $oldValue = null, ?string $newValue = null)
    {
        return $this->state(function (array $attributes) use ($fieldName, $oldValue, $newValue) {
            $description = 'Document metadata updated';
            
            if ($fieldName && $oldValue && $newValue) {
                $description = 'Changed ' . $fieldName . ' from "' . $oldValue . '" to "' . $newValue . '"';
            }
            
            return [
                'description' => $description,
                'action_type_id' => ActionType::factory()->state([
                    'name' => 'edit',
                    'description' => 'Document edit action',
                ]),
            ];
        });
    }

    /**
     * Configure the action as a document process action.
     *
     * @param bool $processed True if marking as processed, false if marking as unprocessed
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function processAction(bool $processed = true)
    {
        return $this->state(function (array $attributes) use ($processed) {
            return [
                'description' => $processed ? 'Marked as processed' : 'Marked as unprocessed',
                'action_type_id' => ActionType::factory()->state([
                    'name' => 'process',
                    'description' => 'Document processing action',
                ]),
            ];
        });
    }

    /**
     * Configure the action as a document trash action.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function trashAction()
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => 'Document moved to trash',
                'action_type_id' => ActionType::factory()->state([
                    'name' => 'trash',
                    'description' => 'Document trash action',
                ]),
            ];
        });
    }

    /**
     * Configure the action as a document restore action.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function restoreAction()
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => 'Document restored from trash',
                'action_type_id' => ActionType::factory()->state([
                    'name' => 'restore',
                    'description' => 'Document restore action',
                ]),
            ];
        });
    }
}