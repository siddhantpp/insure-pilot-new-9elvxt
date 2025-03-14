<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0

/**
 * Eloquent model representing documents in the Insure Pilot system.
 * This model is the core entity for the Documents View feature, managing document metadata,
 * relationships to policies, losses, claimants, producers, and files, as well as document status and processing state.
 */
class Document extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'document';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'date_received',
        'description',
        'policy_id',
        'loss_id',
        'claimant_id',
        'producer_id',
        'signature_required',
        'status_id',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_received' => 'date',
        'policy_id' => 'integer',
        'loss_id' => 'integer',
        'claimant_id' => 'integer',
        'producer_id' => 'integer',
        'signature_required' => 'boolean',
        'status_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'date_received',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'main_file',
        'file_url',
        'is_processed',
        'is_trashed',
        'policy_number',
        'loss_sequence',
        'claimant_name',
        'producer_number',
        'assigned_to',
    ];

    /**
     * Document status constants.
     */
    public const STATUS_UNPROCESSED = 1;
    public const STATUS_PROCESSED = 2;
    public const STATUS_TRASHED = 3;

    /**
     * Defines a many-to-many relationship between Document and File models through the map_document_file pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany Relationship instance for files associated with this document
     */
    public function files()
    {
        return $this->belongsToMany(File::class, 'map_document_file', 'document_id', 'file_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a many-to-many relationship between Document and Action models through the map_document_action pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany Relationship instance for actions associated with this document
     */
    public function actions()
    {
        return $this->belongsToMany(Action::class, 'map_document_action', 'document_id', 'action_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a belongs-to relationship between Document and Policy models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the policy associated with this document
     */
    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * Defines a belongs-to relationship between Document and Loss models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the loss associated with this document
     */
    public function loss()
    {
        return $this->belongsTo(Loss::class);
    }

    /**
     * Defines a belongs-to relationship between Document and Claimant models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the claimant associated with this document
     */
    public function claimant()
    {
        return $this->belongsTo(Claimant::class);
    }

    /**
     * Defines a belongs-to relationship between Document and Producer models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the producer associated with this document
     */
    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    /**
     * Defines a many-to-many relationship between Document and User models through the map_user_document pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany Relationship instance for users assigned to this document
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'map_user_document', 'document_id', 'user_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a many-to-many relationship between Document and UserGroup models through the map_user_group_document pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany Relationship instance for user groups assigned to this document
     */
    public function userGroups()
    {
        return $this->belongsToMany(UserGroup::class, 'map_user_group_document', 'document_id', 'user_group_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a belongs-to relationship between Document and User models for the user who created the document
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the user who created this document
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship between Document and User models for the user who last updated the document
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the user who last updated this document
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor that returns the main file associated with the document
     *
     * @return ?\\App\\Models\\File The main file or null if no files exist
     */
    public function getMainFileAttribute()
    {
        if (!$this->relationLoaded('files')) {
            $this->load('files');
        }
        
        return $this->files->first();
    }

    /**
     * Accessor that returns the URL to the main file for viewing
     *
     * @return ?string The URL to the main file or null if no main file exists
     */
    public function getFileUrlAttribute()
    {
        $mainFile = $this->getMainFileAttribute();
        
        if (!$mainFile) {
            return null;
        }
        
        return $mainFile->url;
    }

    /**
     * Accessor that checks if the document is marked as processed
     *
     * @return bool True if the document is processed, false otherwise
     */
    public function getIsProcessedAttribute()
    {
        return $this->status_id === self::STATUS_PROCESSED;
    }

    /**
     * Accessor that checks if the document is in the trash
     *
     * @return bool True if the document is trashed, false otherwise
     */
    public function getIsTrashedAttribute()
    {
        return $this->status_id === self::STATUS_TRASHED || $this->trashed();
    }

    /**
     * Accessor that returns the formatted policy number
     *
     * @return ?string The formatted policy number or null if no policy is associated
     */
    public function getPolicyNumberAttribute()
    {
        if (!$this->relationLoaded('policy')) {
            $this->load('policy');
        }
        
        if (!$this->policy) {
            return null;
        }
        
        return $this->policy->formatted_number;
    }

    /**
     * Accessor that returns the formatted loss sequence
     *
     * @return ?string The formatted loss sequence or null if no loss is associated
     */
    public function getLossSequenceAttribute()
    {
        if (!$this->relationLoaded('loss')) {
            $this->load('loss');
        }
        
        if (!$this->loss) {
            return null;
        }
        
        return $this->loss->display_name;
    }

    /**
     * Accessor that returns the claimant name
     *
     * @return ?string The claimant name or null if no claimant is associated
     */
    public function getClaimantNameAttribute()
    {
        if (!$this->relationLoaded('claimant')) {
            $this->load('claimant');
        }
        
        if (!$this->claimant) {
            return null;
        }
        
        return $this->claimant->display_name;
    }

    /**
     * Accessor that returns the producer number
     *
     * @return ?string The producer number or null if no producer is associated
     */
    public function getProducerNumberAttribute()
    {
        if (!$this->relationLoaded('producer')) {
            $this->load('producer');
        }
        
        if (!$this->producer) {
            return null;
        }
        
        return $this->producer->number;
    }

    /**
     * Accessor that returns the assigned users and groups as a formatted string
     *
     * @return string Comma-separated list of assigned users and groups
     */
    public function getAssignedToAttribute()
    {
        if (!$this->relationLoaded('users')) {
            $this->load('users');
        }
        
        if (!$this->relationLoaded('userGroups')) {
            $this->load('userGroups');
        }
        
        $assignedUsers = $this->users->pluck('username')->toArray();
        $assignedGroups = $this->userGroups->pluck('name')->toArray();
        
        $assigned = array_merge($assignedUsers, $assignedGroups);
        
        return empty($assigned) ? '' : implode(', ', $assigned);
    }

    /**
     * Marks the document as processed
     *
     * @return bool True if the operation was successful, false otherwise
     */
    public function markAsProcessed()
    {
        $this->status_id = self::STATUS_PROCESSED;
        return $this->save();
    }

    /**
     * Marks the document as unprocessed
     *
     * @return bool True if the operation was successful, false otherwise
     */
    public function markAsUnprocessed()
    {
        $this->status_id = self::STATUS_UNPROCESSED;
        return $this->save();
    }

    /**
     * Moves the document to the trash (soft delete)
     *
     * @return bool True if the operation was successful, false otherwise
     */
    public function moveToTrash()
    {
        $this->status_id = self::STATUS_TRASHED;
        if ($this->save()) {
            return $this->delete();
        }
        
        return false;
    }

    /**
     * Restores the document from the trash
     *
     * @return bool True if the operation was successful, false otherwise
     */
    public function restore()
    {
        if (parent::restore()) {
            $this->status_id = self::STATUS_UNPROCESSED;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Query scope to filter processed documents
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeProcessed($query)
    {
        return $query->where('status_id', self::STATUS_PROCESSED);
    }

    /**
     * Query scope to filter unprocessed documents
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('status_id', self::STATUS_UNPROCESSED);
    }

    /**
     * Query scope to filter trashed documents
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeTrashed($query)
    {
        return $query->where('status_id', self::STATUS_TRASHED)->withTrashed();
    }

    /**
     * Query scope to filter documents by policy ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $policyId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForPolicy($query, $policyId)
    {
        return $query->where('policy_id', $policyId);
    }

    /**
     * Query scope to filter documents by loss ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $lossId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForLoss($query, $lossId)
    {
        return $query->where('loss_id', $lossId);
    }

    /**
     * Query scope to filter documents by claimant ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $claimantId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForClaimant($query, $claimantId)
    {
        return $query->where('claimant_id', $claimantId);
    }

    /**
     * Query scope to filter documents by producer ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $producerId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForProducer($query, $producerId)
    {
        return $query->where('producer_id', $producerId);
    }

    /**
     * Query scope to filter documents assigned to a specific user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeAssignedToUser($query, $userId)
    {
        return $query->join('map_user_document', 'document.id', '=', 'map_user_document.document_id')
            ->where('map_user_document.user_id', $userId)
            ->select('document.*');
    }

    /**
     * Query scope to filter documents assigned to a specific user group
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $groupId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeAssignedToGroup($query, $groupId)
    {
        return $query->join('map_user_group_document', 'document.id', '=', 'map_user_group_document.document_id')
            ->where('map_user_group_document.user_group_id', $groupId)
            ->select('document.*');
    }

    /**
     * Query scope to search documents by name, description, or related entities
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }
        
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('policy', function ($query) use ($search) {
                    $query->where('number', 'like', "%{$search}%");
                })
                ->orWhereHas('loss', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('claimant', function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%");
                })
                ->orWhereHas('producer', function ($query) use ($search) {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
        });
    }
}