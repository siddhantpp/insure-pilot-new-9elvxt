<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\Model; // ^10.0

/**
 * Model representing the pivot relationship between documents and actions in the Insure Pilot system.
 * This model tracks which actions have been performed on which documents, with additional metadata about the relationship.
 * It is a critical component of the Document History & Audit Trail feature, enabling comprehensive tracking 
 * of all document-related activities with user attribution.
 */
class MapDocumentAction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'map_document_action';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'action_id',
        'description',
        'status_id',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'document_id' => 'integer',
        'action_id' => 'integer',
        'status_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Defines a belongs-to relationship between MapDocumentAction and Document models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the document associated with this action record
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Defines a belongs-to relationship between MapDocumentAction and Action models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the action associated with this document record
     */
    public function action()
    {
        return $this->belongsTo(Action::class, 'action_id');
    }

    /**
     * Defines a belongs-to relationship between MapDocumentAction and User models for the user who created the record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the user who created this record
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship between MapDocumentAction and User models for the user who last updated the record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the user who last updated this record
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Query scope to filter action records for a specific document
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $documentId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForDocument($query, $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    /**
     * Query scope to filter document records for a specific action
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $actionId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForAction($query, $actionId)
    {
        return $query->where('action_id', $actionId);
    }

    /**
     * Query scope to filter records created by a specific user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Query scope to order records chronologically by creation date
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeChronological($query, $direction = 'desc')
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Query scope to filter records by action type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $actionTypeId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeWithActionType($query, $actionTypeId)
    {
        return $query->join('action', 'map_document_action.action_id', '=', 'action.id')
                    ->where('action.action_type_id', $actionTypeId);
    }

    /**
     * Query scope to get recent action records within a specified time period
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Accessor to get the action type name through the action relationship
     *
     * @return string The name of the action type
     */
    public function getActionTypeAttribute()
    {
        if (!$this->relationLoaded('action')) {
            $this->load('action');
        }
        
        if (!$this->action) {
            return '';
        }
        
        if (!$this->action->relationLoaded('actionType')) {
            $this->action->load('actionType');
        }
        
        return $this->action->actionType ? $this->action->actionType->name : '';
    }

    /**
     * Accessor to get the formatted creation date
     *
     * @return string The formatted creation date
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format(config('app.datetime_format', 'Y-m-d H:i:s'));
    }

    /**
     * Accessor to get the action description through the action relationship
     *
     * @return string The description of the action
     */
    public function getActionDescriptionAttribute()
    {
        if (!$this->relationLoaded('action')) {
            $this->load('action');
        }
        
        return $this->action ? $this->action->description : '';
    }
}