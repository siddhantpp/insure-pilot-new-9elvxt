<?php

namespace App\Http\Resources;

use App\Models\MapDocumentAction;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource; // ^10.0
use Carbon\Carbon; // ^2.0

/**
 * API resource class that transforms document history data into JSON responses for the API.
 * This resource is used in the Documents View feature to provide a standardized format for
 * document action history, including user attribution, timestamps, and action details.
 */
class DocumentHistoryResource extends JsonResource
{
    /**
     * Default constructor for the DocumentHistoryResource
     * 
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }
    
    /**
     * Transform the resource into an array for API response
     *
     * @param \Illuminate\Http\Request $request
     * @return array The transformed resource array
     */
    public function toArray($request)
    {
        // Extract the MapDocumentAction model from the resource
        $documentAction = $this->resource;
        
        // Ensure necessary relationships are loaded
        if (!$documentAction->relationLoaded('action')) {
            $documentAction->load('action.actionType', 'action.createdBy');
        } elseif ($documentAction->action && !$documentAction->action->relationLoaded('actionType')) {
            $documentAction->action->load('actionType', 'createdBy');
        }

        // Get the action record
        $action = $documentAction->action;
        
        // Format the timestamp and get user information
        $timestamp = $this->formatTimestamp($action ? $action->created_at : null);
        $userInfo = $this->withUserInfo($action ? $action->createdBy : null);
        
        // Build the response array
        return [
            'id' => $documentAction->id,
            'action_type' => $action ? $this->getActionTypeInfo($action) : null,
            'description' => $action ? $action->description : null,
            'timestamp' => $timestamp,
            'user' => $userInfo,
            'created_at' => $this->formatTimestamp($documentAction->created_at),
            'updated_at' => $this->formatTimestamp($documentAction->updated_at),
            'document_id' => $documentAction->document_id,
            'action_id' => $action ? $action->id : null,
        ];
    }

    /**
     * Format a timestamp for display
     *
     * @param ?\\Carbon\\Carbon $timestamp
     * @return string|null Formatted timestamp string or null
     */
    protected function formatTimestamp(?Carbon $timestamp)
    {
        if (!$timestamp) {
            return null;
        }

        return $timestamp->format(config('app.datetime_format', 'Y-m-d H:i:s'));
    }

    /**
     * Add user information to the resource
     *
     * @param ?\\App\\Models\\User $user
     * @return array|null User information array or null
     */
    protected function withUserInfo(?User $user)
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username,
            // Don't include sensitive user information
        ];
    }

    /**
     * Get formatted action type information
     *
     * @param \\App\\Models\\Action $action
     * @return array Formatted action type information
     */
    protected function getActionTypeInfo(Action $action)
    {
        // Load the actionType relationship if not already loaded
        if (!$action->relationLoaded('actionType')) {
            $action->load('actionType');
        }
        
        $actionType = $action->actionType;
        
        return [
            'id' => $actionType ? $actionType->id : null,
            'name' => $actionType ? $actionType->name : null,
        ];
    }
}