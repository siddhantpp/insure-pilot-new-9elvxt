<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\Model; // ^10.0
use App\Models\Document;
use App\Models\User;

/**
 * Model representing the pivot table that links users to documents in the Insure Pilot system.
 * This model manages the many-to-many relationship between users and documents, enabling user assignment functionality.
 */
class MapUserDocument extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'map_user_document';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'user_id',
        'description',
        'status_id',
        'created_by',
        'updated_by',
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
        'user_id' => 'integer',
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
     * Defines a belongs-to relationship between MapUserDocument and Document models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the document associated with this mapping
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Defines a belongs-to relationship between MapUserDocument and User models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the user associated with this mapping
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Defines a belongs-to relationship between MapUserDocument and User models for the user who created the mapping
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the user who created this mapping
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship between MapUserDocument and User models for the user who last updated the mapping
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the user who last updated this mapping
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Query scope to filter mappings by document ID
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
     * Query scope to filter mappings by user ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Query scope to filter mappings by creator user ID
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
     * Query scope to filter mappings created after a specific date
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeCreatedAfter($query, $date)
    {
        return $query->where('created_at', '>', $date);
    }

    /**
     * Query scope to filter active user-document assignments
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', 1); // Assuming 1 is the active status ID
    }

    /**
     * Accessor to get the user's name through the user relationship
     *
     * @return string The name of the assigned user
     */
    public function getUserNameAttribute()
    {
        if (!$this->relationLoaded('user')) {
            $this->load('user');
        }
        
        return $this->user ? $this->user->username : null;
    }
}