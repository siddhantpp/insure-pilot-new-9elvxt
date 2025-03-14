<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0

/**
 * Eloquent model representing claimants in the insurance system.
 * A claimant is an individual or entity making an insurance claim,
 * associated with losses and policies, and referenced in document metadata.
 */
class Claimant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'claimant';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name_id',
        'policy_id',
        'loss_id',
        'description',
        'status_id',
        'claimant_type_id',
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
        'name_id' => 'integer',
        'policy_id' => 'integer',
        'loss_id' => 'integer',
        'status_id' => 'integer',
        'claimant_type_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'full_name',
        'display_name',
    ];

    /**
     * Claimant type constants.
     */
    public const TYPE_INDIVIDUAL = 1;
    public const TYPE_BUSINESS = 2;
    public const TYPE_OTHER = 3;

    /**
     * Defines a many-to-many relationship between Claimant and Loss models through the map_loss_claimant pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The relationship instance
     */
    public function losses()
    {
        return $this->belongsToMany(Loss::class, 'map_loss_claimant', 'claimant_id', 'loss_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a belongs-to relationship between Claimant and Policy models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }

    /**
     * Defines a has-many relationship between Claimant and Document models
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance
     */
    public function documents()
    {
        return $this->hasMany('App\Models\Document', 'claimant_id');
    }

    /**
     * Defines a belongs-to relationship between Claimant and User models for the user who created the claimant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship between Claimant and User models for the user who last updated the claimant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor that returns the full name of the claimant
     *
     * @return string The full name of the claimant
     */
    public function getFullNameAttribute()
    {
        // Load the name relationship if not already loaded
        if ($this->name_id && !$this->relationLoaded('name')) {
            $this->load('name');
        }

        // Return the name value from the related name record
        if (isset($this->name)) {
            return $this->name->value;
        }

        // Return a default value if no name is available
        return 'Unnamed Claimant';
    }

    /**
     * Accessor that returns a formatted display name with sequence number for dropdown display
     *
     * @return string The formatted display name with sequence number
     */
    public function getDisplayNameAttribute()
    {
        // Get sequence number from relationship order
        $sequence = 0;
        if ($this->relationLoaded('losses') && $this->losses->isNotEmpty()) {
            $lossRelation = $this->losses->first()->pivot;
            $sequence = \DB::table('map_loss_claimant')
                ->where('loss_id', $lossRelation->loss_id)
                ->where('created_at', '<=', $lossRelation->created_at)
                ->count();
        }

        if (!$sequence) {
            $sequence = 1; // Default to 1 if sequence cannot be determined
        }

        return "$sequence - {$this->full_name}";
    }

    /**
     * Query scope to filter claimants by policy ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $policyId
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeForPolicy($query, $policyId)
    {
        return $query->where('policy_id', $policyId);
    }

    /**
     * Query scope to filter claimants by loss ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $lossId
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeForLoss($query, $lossId)
    {
        return $query->join('map_loss_claimant', 'claimant.id', '=', 'map_loss_claimant.claimant_id')
            ->where('map_loss_claimant.loss_id', $lossId)
            ->select('claimant.*')
            ->distinct();
    }

    /**
     * Query scope to filter active claimants
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
     * Checks if the claimant is an individual
     *
     * @return bool True if the claimant is an individual, false otherwise
     */
    public function isIndividual()
    {
        return $this->claimant_type_id === self::TYPE_INDIVIDUAL;
    }

    /**
     * Checks if the claimant is a business
     *
     * @return bool True if the claimant is a business, false otherwise
     */
    public function isBusiness()
    {
        return $this->claimant_type_id === self::TYPE_BUSINESS;
    }
}