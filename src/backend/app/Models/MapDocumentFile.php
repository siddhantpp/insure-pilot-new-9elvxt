<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\Model; // ^10.0

/**
 * Model representing the pivot table that links documents to files in the Insure Pilot system.
 * This model manages the many-to-many relationship between documents and their associated files.
 */
class MapDocumentFile extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'map_document_file';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'file_id',
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
        'file_id' => 'integer',
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
     * Defines a belongs-to relationship between MapDocumentFile and Document models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the document associated with this mapping
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Defines a belongs-to relationship between MapDocumentFile and File models
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the file associated with this mapping
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Defines a belongs-to relationship between MapDocumentFile and User models for the user who created the mapping
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relationship instance for the user who created this mapping
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship between MapDocumentFile and User models for the user who last updated the mapping
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
     * Query scope to filter mappings by file ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $fileId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForFile($query, $fileId)
    {
        return $query->where('file_id', $fileId);
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
}