<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0

/**
 * PolicyPrefix model representing standardized codes that appear before policy numbers
 * and help categorize different types of insurance policies.
 */
class PolicyPrefix extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'policy_prefix';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'status_id' => 'integer',
    ];

    /**
     * Get the policies associated with this policy prefix.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function policies()
    {
        return $this->hasMany(Policy::class, 'policy_prefix_id');
    }

    /**
     * Get the user who created this policy prefix.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this policy prefix.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active policy prefixes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        // Assuming status_id=1 represents 'active' status
        // This should be adjusted based on the actual status management approach in the system
        return $query->where('status_id', 1);
    }

    /**
     * Scope a query to search policy prefixes by name or description.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            $searchTerm = '%' . $search . '%';
            return $query->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', $searchTerm)
                      ->orWhere('description', 'LIKE', $searchTerm);
            });
        }
        
        return $query;
    }
}