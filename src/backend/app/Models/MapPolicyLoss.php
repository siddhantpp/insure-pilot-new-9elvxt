<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0

/**
 * Eloquent model representing the pivot table that establishes the many-to-many relationship between policies and losses.
 * This model provides access to the relationship metadata and maintains audit information about who created and updated the relationship.
 */
class MapPolicyLoss extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'map_policy_loss';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'policy_id',
        'loss_id',
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
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'policy_id' => 'integer',
        'loss_id' => 'integer',
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
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Defines a belongs-to relationship between MapPolicyLoss and Policy models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }

    /**
     * Defines a belongs-to relationship between MapPolicyLoss and Loss models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function loss()
    {
        return $this->belongsTo(Loss::class, 'loss_id');
    }

    /**
     * Defines a belongs-to relationship with the User who created this record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship with the User who last updated this record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Query scope to filter records by policy ID
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
     * Query scope to filter records by loss ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $lossId
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeForLoss($query, $lossId)
    {
        return $query->where('loss_id', $lossId);
    }

    /**
     * Query scope to filter active records
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeActive($query)
    {
        // Assuming status_id=1 represents 'active' status
        return $query->where('status_id', 1);
    }
}