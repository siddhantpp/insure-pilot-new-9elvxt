<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0

class Producer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'producer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'producer_code_id',
        'number',
        'name',
        'description',
        'status_id',
        'producer_type_id',
        'signature_required',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // No sensitive fields to hide
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'producer_code_id' => 'integer',
        'status_id' => 'integer',
        'producer_type_id' => 'integer',
        'signature_required' => 'boolean',
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
        'display_name',
        'related_policies_count',
        'related_documents_count',
    ];

    /**
     * Defines a many-to-many relationship between Producer and Policy models through the map_producer_policy pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function policies()
    {
        return $this->belongsToMany(\App\Models\Policy::class, 'map_producer_policy', 'producer_id', 'policy_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a has-many relationship between Producer and Document models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents()
    {
        return $this->hasMany(\App\Models\Document::class, 'producer_id');
    }

    /**
     * Defines a has-many relationship with MapProducerPolicy records for this producer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mapProducerPolicies()
    {
        return $this->hasMany(\App\Models\MapProducerPolicy::class, 'producer_id');
    }

    /**
     * Defines a belongs-to relationship with the User who created this producer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship with the User who last updated this producer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor that returns a formatted display name for dropdown menus.
     *
     * @return string The formatted display name
     */
    public function getDisplayNameAttribute()
    {
        return "{$this->number} - {$this->name}";
    }

    /**
     * Accessor that returns the count of policies associated with this producer.
     *
     * @return int The count of related policies
     */
    public function getRelatedPoliciesCountAttribute()
    {
        return $this->policies()->count();
    }

    /**
     * Accessor that returns the count of documents associated with this producer.
     *
     * @return int The count of related documents
     */
    public function getRelatedDocumentsCountAttribute()
    {
        return $this->documents()->count();
    }

    /**
     * Query scope to filter active producers.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', 1); // Assuming 1 is the active status ID
    }

    /**
     * Query scope to include producers that have associated policies.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeWithPolicies($query)
    {
        return $query->join('map_producer_policy', 'producer.id', '=', 'map_producer_policy.producer_id')
            ->groupBy('producer.id')
            ->select('producer.*');
    }

    /**
     * Query scope to search producers by number, name, or other attributes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    /**
     * Check if this producer has any associated policies.
     *
     * @return bool True if there are policies associated with this producer, false otherwise
     */
    public function hasPolicies()
    {
        return $this->related_policies_count > 0;
    }

    /**
     * Check if this producer has any associated documents.
     *
     * @return bool True if there are documents associated with this producer, false otherwise
     */
    public function hasDocuments()
    {
        return $this->related_documents_count > 0;
    }

    /**
     * Check if this producer requires signature on documents.
     *
     * @return bool True if signature is required, false otherwise
     */
    public function requiresSignature()
    {
        return $this->signature_required;
    }
}