<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0
use Carbon\Carbon; // ^2.0

/**
 * Eloquent model representing insurance policies in the Insure Pilot system.
 * Policies are contracts between insurers and policyholders that define coverage, terms, and conditions.
 */
class Policy extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'policy';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'policy_prefix_id',
        'number',
        'policy_type_id',
        'effective_date',
        'inception_date',
        'expiration_date',
        'renewal_date',
        'status_id',
        'term_id',
        'description',
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
        'policy_prefix_id' => 'integer',
        'policy_type_id' => 'integer',
        'status_id' => 'integer',
        'term_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'effective_date' => 'date',
        'inception_date' => 'date',
        'expiration_date' => 'date',
        'renewal_date' => 'date',
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
        'effective_date',
        'inception_date',
        'expiration_date',
        'renewal_date',
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
        'formatted_number',
        'formatted_effective_date',
        'formatted_expiration_date',
        'display_name',
        'related_documents_count',
    ];

    /**
     * Defines a belongs-to relationship between Policy and PolicyPrefix models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function policyPrefix()
    {
        return $this->belongsTo(PolicyPrefix::class, 'policy_prefix_id');
    }

    /**
     * Defines a many-to-many relationship between Policy and Producer models through the map_producer_policy pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function producers()
    {
        return $this->belongsToMany('App\Models\Producer', 'map_producer_policy', 'policy_id', 'producer_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a many-to-many relationship between Policy and Loss models through the map_policy_loss pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function losses()
    {
        return $this->belongsToMany('App\Models\Loss', 'map_policy_loss', 'policy_id', 'loss_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a has-many relationship between Policy and Document models
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents()
    {
        return $this->hasMany('App\Models\Document', 'policy_id');
    }

    /**
     * Defines a has-many relationship with MapProducerPolicy records for this policy
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mapProducerPolicies()
    {
        return $this->hasMany('App\Models\MapProducerPolicy', 'policy_id');
    }

    /**
     * Defines a has-many relationship with MapPolicyLoss records for this policy
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mapPolicyLosses()
    {
        return $this->hasMany('App\Models\MapPolicyLoss', 'policy_id');
    }

    /**
     * Defines a belongs-to relationship with the User who created this policy
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship with the User who last updated this policy
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor that returns the policy number formatted with its prefix
     *
     * @return string The formatted policy number
     */
    public function getFormattedNumberAttribute()
    {
        if ($this->relationLoaded('policyPrefix')) {
            return $this->policyPrefix->name . $this->number;
        }
        
        // Load the relationship if not already loaded
        return $this->policyPrefix ? $this->policyPrefix->name . $this->number : $this->number;
    }

    /**
     * Accessor that returns the effective date formatted for display
     *
     * @return string The formatted effective date
     */
    public function getFormattedEffectiveDateAttribute()
    {
        return $this->effective_date ? $this->effective_date->format(config('app.date_format', 'm/d/Y')) : '';
    }

    /**
     * Accessor that returns the expiration date formatted for display
     *
     * @return string The formatted expiration date
     */
    public function getFormattedExpirationDateAttribute()
    {
        return $this->expiration_date ? $this->expiration_date->format(config('app.date_format', 'm/d/Y')) : '';
    }

    /**
     * Accessor that returns a formatted display name for dropdown menus
     *
     * @return string The formatted display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->formatted_number;
    }

    /**
     * Accessor that returns the count of documents associated with this policy
     *
     * @return int The count of related documents
     */
    public function getRelatedDocumentsCountAttribute()
    {
        return $this->documents()->count();
    }

    /**
     * Query scope to filter active policies
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeActive($query)
    {
        // Assuming status_id=1 represents 'active' status
        return $query->where('status_id', 1);
    }

    /**
     * Query scope to filter policies by producer ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $producerId
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeForProducer($query, $producerId)
    {
        return $query->join('map_producer_policy', 'policy.id', '=', 'map_producer_policy.policy_id')
            ->where('map_producer_policy.producer_id', $producerId)
            ->select('policy.*')
            ->distinct();
    }

    /**
     * Query scope to include policies that have associated losses
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeWithLosses($query)
    {
        return $query->join('map_policy_loss', 'policy.id', '=', 'map_policy_loss.policy_id')
            ->select('policy.*')
            ->groupBy('policy.id');
    }

    /**
     * Query scope to search policies by number or other attributes
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
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }

    /**
     * Check if this policy has any associated documents
     *
     * @return bool True if there are documents associated with this policy, false otherwise
     */
    public function hasDocuments()
    {
        return $this->documents()->count() > 0;
    }

    /**
     * Check if this policy has any associated losses
     *
     * @return bool True if there are losses associated with this policy, false otherwise
     */
    public function hasLosses()
    {
        return $this->losses()->count() > 0;
    }

    /**
     * Check if this policy is active based on effective and expiration dates
     *
     * @return bool True if the policy is active, false otherwise
     */
    public function isActive()
    {
        $now = Carbon::now();
        
        // Check if the current date is between effective and expiration dates
        if ($this->effective_date && $this->expiration_date) {
            return $now->between($this->effective_date, $this->expiration_date);
        }
        
        // If only effective date is set, check if current date is after effective date
        if ($this->effective_date && !$this->expiration_date) {
            return $now->gte($this->effective_date);
        }
        
        // Default to inactive if no dates are set
        return false;
    }
}