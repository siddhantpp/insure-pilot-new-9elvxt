<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_group';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'status_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // No sensitive fields to hide for user groups
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
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
     * Defines a has-many relationship between UserGroup and User models
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'user_group_id');
    }

    /**
     * Defines a many-to-many relationship between UserGroup and Document models through the map_user_group_document pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function documents()
    {
        return $this->belongsToMany(\App\Models\Document::class, 'map_user_group_document', 'user_group_id', 'document_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a belongs-to relationship between UserGroup and User models for the user who created the group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship between UserGroup and User models for the user who last updated the group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Query scope to filter active user groups
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', 1); // Assuming 1 is the active status ID
    }

    /**
     * Query scope to filter groups that have users
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeWithUsers($query)
    {
        return $query->whereHas('users');
    }

    /**
     * Query scope to filter groups assigned to a specific document
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $documentId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeAssignedToDocument($query, $documentId)
    {
        return $query->join('map_user_group_document', 'user_group.id', '=', 'map_user_group_document.user_group_id')
            ->where('map_user_group_document.document_id', $documentId)
            ->select('user_group.*');
    }

    /**
     * Query scope to filter groups created by a specific user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Query scope to search groups by name or description
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    /**
     * Accessor that returns the count of users in this group
     *
     * @return int The number of users in this group
     */
    public function getUserCountAttribute()
    {
        return $this->users()->count();
    }

    /**
     * Accessor that returns the count of documents assigned to this group
     *
     * @return int The number of documents assigned to this group
     */
    public function getDocumentCountAttribute()
    {
        return $this->documents()->count();
    }

    /**
     * Checks if a specific user belongs to this group
     *
     * @param int $userId
     * @return bool True if the user belongs to this group, false otherwise
     */
    public function hasUser($userId)
    {
        return $this->users()->where('id', $userId)->exists();
    }
}