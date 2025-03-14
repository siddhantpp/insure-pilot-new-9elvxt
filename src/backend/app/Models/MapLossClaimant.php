<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0

/**
 * Eloquent model representing the pivot table that maps the many-to-many relationship between losses and claimants.
 * This model provides access to the relationship metadata and enables efficient querying of related entities.
 */
class MapLossClaimant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'map_loss_claimant';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loss_id',
        'claimant_id',
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
        'loss_id' => 'integer',
        'claimant_id' => 'integer',
        'status_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    protected $timestamps = true;

    /**
     * Defines a belongs-to relationship with the Loss model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function loss()
    {
        return $this->belongsTo(Loss::class, 'loss_id');
    }

    /**
     * Defines a belongs-to relationship with the Claimant model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function claimant()
    {
        return $this->belongsTo(Claimant::class, 'claimant_id');
    }

    /**
     * Defines a belongs-to relationship with the User who created this mapping
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship with the User who last updated this mapping
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Query scope to filter active mappings
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
     * Query scope to filter mappings by loss ID
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
     * Query scope to filter mappings by claimant ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $claimantId
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeForClaimant($query, $claimantId)
    {
        return $query->where('claimant_id', $claimantId);
    }
}