<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'user_type_id',
        'user_group_id',
        'description',
        'status_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status_id' => 'integer',
        'user_type_id' => 'integer',
        'user_group_id' => 'integer',
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
        'email_verified_at',
    ];

    /**
     * User role constants.
     */
    public const ROLE_ADMIN = 1;
    public const ROLE_MANAGER = 2;
    public const ROLE_ADJUSTER = 3;
    public const ROLE_UNDERWRITER = 4;
    public const ROLE_SUPPORT = 5;
    public const ROLE_READONLY = 6;

    /**
     * Defines a belongs-to relationship between User and UserGroup models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userGroup()
    {
        return $this->belongsTo(\App\Models\UserGroup::class, 'user_group_id');
    }

    /**
     * Defines a many-to-many relationship between User and Document models through the map_user_document pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function documents()
    {
        return $this->belongsToMany(\App\Models\Document::class, 'map_user_document', 'user_id', 'document_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a has-many relationship between User and Document models for documents created by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function createdDocuments()
    {
        return $this->hasMany(\App\Models\Document::class, 'created_by');
    }

    /**
     * Defines a has-many relationship between User and Document models for documents last updated by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function updatedDocuments()
    {
        return $this->hasMany(\App\Models\Document::class, 'updated_by');
    }

    /**
     * Defines a has-many relationship between User and UserGroup models for groups created by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function createdGroups()
    {
        return $this->hasMany(\App\Models\UserGroup::class, 'created_by');
    }

    /**
     * Defines a has-many relationship between User and UserGroup models for groups last updated by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function updatedGroups()
    {
        return $this->hasMany(\App\Models\UserGroup::class, 'updated_by');
    }

    /**
     * Checks if the user has administrator role.
     *
     * @return bool True if the user is an administrator, false otherwise
     */
    public function isAdmin()
    {
        return $this->user_type_id === self::ROLE_ADMIN;
    }

    /**
     * Checks if the user has manager role.
     *
     * @return bool True if the user is a manager, false otherwise
     */
    public function isManager()
    {
        return $this->user_type_id === self::ROLE_MANAGER;
    }

    /**
     * Checks if the user has claims adjuster role.
     *
     * @return bool True if the user is a claims adjuster, false otherwise
     */
    public function isAdjuster()
    {
        return $this->user_type_id === self::ROLE_ADJUSTER;
    }

    /**
     * Checks if the user has underwriter role.
     *
     * @return bool True if the user is an underwriter, false otherwise
     */
    public function isUnderwriter()
    {
        return $this->user_type_id === self::ROLE_UNDERWRITER;
    }

    /**
     * Checks if the user has support staff role.
     *
     * @return bool True if the user is support staff, false otherwise
     */
    public function isSupport()
    {
        return $this->user_type_id === self::ROLE_SUPPORT;
    }

    /**
     * Checks if the user has read-only role.
     *
     * @return bool True if the user has read-only access, false otherwise
     */
    public function isReadOnly()
    {
        return $this->user_type_id === self::ROLE_READONLY;
    }

    /**
     * Checks if the user has a specific role.
     *
     * @param int|array $roleId The role ID or array of role IDs to check against
     * @return bool True if the user has the specified role, false otherwise
     */
    public function hasRole($roleId)
    {
        if (is_array($roleId)) {
            return in_array($this->user_type_id, $roleId);
        }
        
        return $this->user_type_id === $roleId;
    }

    /**
     * Accessor that returns the user's role name.
     *
     * @return string The name of the user's role
     */
    public function getRoleNameAttribute()
    {
        $roles = [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_ADJUSTER => 'Claims Adjuster',
            self::ROLE_UNDERWRITER => 'Underwriter',
            self::ROLE_SUPPORT => 'Support Staff',
            self::ROLE_READONLY => 'Read-Only User',
        ];

        return $roles[$this->user_type_id] ?? 'Unknown Role';
    }

    /**
     * Accessor that returns the user's full name.
     *
     * @return string The full name of the user
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Accessor that returns the count of documents assigned to this user.
     *
     * @return int The number of documents assigned to this user
     */
    public function getDocumentCountAttribute()
    {
        return $this->documents()->count();
    }

    /**
     * Query scope to filter active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', 1); // Assuming 1 is the active status ID
    }

    /**
     * Query scope to filter users by role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array $roleId The role ID or array of role IDs to filter by
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeByRole($query, $roleId)
    {
        if (is_array($roleId)) {
            return $query->whereIn('user_type_id', $roleId);
        }
        
        return $query->where('user_type_id', $roleId);
    }

    /**
     * Query scope to filter users in a specific group.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $groupId The group ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeInGroup($query, $groupId)
    {
        return $query->where('user_group_id', $groupId);
    }

    /**
     * Query scope to filter users assigned to a specific document.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $documentId The document ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeAssignedToDocument($query, $documentId)
    {
        return $query->join('map_user_document', 'user.id', '=', 'map_user_document.user_id')
            ->where('map_user_document.document_id', $documentId)
            ->select('user.*');
    }

    /**
     * Query scope to search users by username, email, or name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search The search term
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where(function ($query) use ($search) {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }
}