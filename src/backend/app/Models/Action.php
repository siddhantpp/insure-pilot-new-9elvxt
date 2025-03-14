<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Laravel Framework ^10.0
use Illuminate\Database\Eloquent\Model; // Laravel Framework ^10.0
use App\Models\ActionType;

class Action extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'action';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'record_id',
        'action_type_id',
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Defines a belongs-to relationship between Action and ActionType models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function actionType()
    {
        return $this->belongsTo(ActionType::class, 'action_type_id');
    }

    /**
     * Defines a many-to-many relationship between Action and Document models
     * through the map_document_action pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function documents()
    {
        return $this->belongsToMany(
            \App\Models\Document::class,
            'map_document_action',
            'action_id',
            'document_id'
        );
    }

    /**
     * Defines a belongs-to relationship between Action and User models
     * for the user who performed the action
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Query scope to filter actions by action type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $actionTypeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $actionTypeId)
    {
        return $query->where('action_type_id', $actionTypeId);
    }

    /**
     * Query scope to filter actions performed by a specific user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Query scope to filter actions for a specific document through the pivot table
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $documentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDocument($query, $documentId)
    {
        return $query->join('map_document_action', 'action.id', '=', 'map_document_action.action_id')
                    ->where('map_document_action.document_id', $documentId);
    }

    /**
     * Query scope to order actions chronologically by creation date
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChronological($query, $direction = 'desc')
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Query scope to get recent actions within a specified time period
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Accessor to get the action type name
     *
     * @return string
     */
    public function getActionTypeAttribute()
    {
        if (!$this->relationLoaded('actionType')) {
            $this->load('actionType');
        }
        
        return $this->actionType->name;
    }

    /**
     * Accessor to get the formatted creation date
     *
     * @return string
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format(config('app.datetime_format', 'Y-m-d H:i:s'));
    }
}