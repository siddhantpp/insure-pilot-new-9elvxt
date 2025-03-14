<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0
use Carbon\Carbon; // ^2.0
use Illuminate\Support\Facades\DB; // ^10.0
use App\Models\Policy;
use App\Models\User;

/**
 * Eloquent model representing insurance loss events in the system.
 * Losses are incidents that may result in insurance claims and are associated with policies and claimants.
 */
class Loss extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loss';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'date',
        'description',
        'status_id',
        'loss_type_id',
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
        'date' => 'date',
        'status_id' => 'integer',
        'loss_type_id' => 'integer',
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
        'date',
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
        'formatted_date',
        'display_name',
        'related_documents_count',
    ];

    /**
     * Defines a many-to-many relationship between Loss and Policy models through the map_policy_loss pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The relationship instance
     */
    public function policies()
    {
        return $this->belongsToMany(Policy::class, 'map_policy_loss', 'loss_id', 'policy_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a many-to-many relationship between Loss and Claimant models through the map_loss_claimant pivot table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The relationship instance
     */
    public function claimants()
    {
        return $this->belongsToMany('App\Models\Claimant', 'map_loss_claimant', 'loss_id', 'claimant_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a has-many relationship with MapLossClaimant records for this loss
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance
     */
    public function mapLossClaimants()
    {
        return $this->hasMany('App\Models\MapLossClaimant', 'loss_id');
    }

    /**
     * Defines a has-many relationship with MapPolicyLoss records for this loss
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance
     */
    public function mapPolicyLosses()
    {
        return $this->hasMany('App\Models\MapPolicyLoss', 'loss_id');
    }

    /**
     * Defines a belongs-to relationship with the User who created this loss
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship with the User who last updated this loss
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor that returns the loss date formatted for display
     *
     * @return string The formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format(config('app.date_format', 'm/d/Y')) : '';
    }

    /**
     * Accessor that returns a formatted display name for dropdown menus, including sequence number and date
     *
     * @return string The formatted display name
     */
    public function getDisplayNameAttribute()
    {
        // Get sequence number (based on chronological order within policy)
        $sequence = 0;
        if ($this->relationLoaded('mapPolicyLosses') && $this->mapPolicyLosses->isNotEmpty()) {
            $policyLoss = $this->mapPolicyLosses->first();
            $sequence = DB::table('map_policy_loss')
                ->where('policy_id', $policyLoss->policy_id)
                ->where('created_at', '<=', $policyLoss->created_at)
                ->count();
        }

        $formattedDate = $this->formatted_date;
        if (!$sequence) {
            $sequence = 1; // Default to 1 if sequence cannot be determined
        }

        return "$sequence - {$this->name} ($formattedDate)";
    }

    /**
     * Accessor that returns the count of documents associated with this loss
     *
     * @return int The count of related documents
     */
    public function getRelatedDocumentsCountAttribute()
    {
        return DB::table('document')
            ->where('loss_id', $this->id)
            ->count();
    }

    /**
     * Query scope to filter active losses
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
     * Query scope to filter losses by policy ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $policyId
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeForPolicy($query, $policyId)
    {
        return $query->join('map_policy_loss', 'loss.id', '=', 'map_policy_loss.loss_id')
            ->where('map_policy_loss.policy_id', $policyId)
            ->select('loss.*')
            ->distinct();
    }

    /**
     * Query scope to order losses chronologically by date
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
     */
    public function scopeChronological($query, $direction = 'asc')
    {
        return $query->orderBy('date', $direction);
    }

    /**
     * Query scope to search losses by name or other attributes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder
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
     * Check if this loss has any associated documents
     *
     * @return bool True if there are documents associated with this loss, false otherwise
     */
    public function hasDocuments()
    {
        return DB::table('document')
            ->where('loss_id', $this->id)
            ->count() > 0;
    }
}