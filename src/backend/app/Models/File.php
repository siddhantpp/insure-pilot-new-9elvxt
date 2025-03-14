<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // ^10.0
use Illuminate\Database\Eloquent\Model; // ^10.0
use Illuminate\Database\Eloquent\SoftDeletes; // ^10.0
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'file';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'path',
        'mime_type',
        'size',
        'status_id',
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
        'size' => 'integer',
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
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'url',
        'full_path',
        'formatted_size',
        'file_extension',
        'is_image',
        'is_pdf',
    ];

    /**
     * Defines a many-to-many relationship between File and Document models through the map_document_file pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function documents()
    {
        return $this->belongsToMany('App\Models\Document', 'map_document_file', 'file_id', 'document_id')
            ->withTimestamps()
            ->withPivot('description', 'status_id', 'created_by', 'updated_by');
    }

    /**
     * Defines a belongs-to relationship between File and User models for the user who created the file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Defines a belongs-to relationship between File and User models for the user who last updated the file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor that generates a URL for accessing the file.
     *
     * @return string|null The URL to the file or null if generation fails
     */
    public function getUrlAttribute()
    {
        if (empty($this->path)) {
            return null;
        }

        try {
            return Storage::url($this->path);
        } catch (\Exception $e) {
            // Log exception if needed
            return null;
        }
    }

    /**
     * Accessor that returns the full storage path to the file.
     *
     * @return string|null The full storage path to the file or null if not available
     */
    public function getFullPathAttribute()
    {
        if (empty($this->path)) {
            return null;
        }

        try {
            return Storage::path($this->path);
        } catch (\Exception $e) {
            // Log exception if needed
            return null;
        }
    }

    /**
     * Accessor that returns the file size in a human-readable format.
     *
     * @return string The formatted file size (e.g., '2.5 MB')
     */
    public function getFormattedSizeAttribute()
    {
        $size = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Accessor that returns the file extension.
     *
     * @return string The file extension (e.g., 'pdf', 'docx')
     */
    public function getFileExtensionAttribute()
    {
        $extension = pathinfo($this->name, PATHINFO_EXTENSION);
        return strtolower($extension);
    }

    /**
     * Accessor that determines if the file is an image based on mime type.
     *
     * @return bool True if the file is an image, false otherwise
     */
    public function getIsImageAttribute()
    {
        return strpos($this->mime_type, 'image/') === 0;
    }

    /**
     * Accessor that determines if the file is a PDF based on mime type.
     *
     * @return bool True if the file is a PDF, false otherwise
     */
    public function getIsPdfAttribute()
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Query scope to filter files by mime type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $mimeType
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeOfType($query, $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Query scope to filter files associated with a specific document.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $documentId
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeForDocument($query, $documentId)
    {
        return $query->join('map_document_file', 'file.id', '=', 'map_document_file.file_id')
            ->where('map_document_file.document_id', $documentId)
            ->select('file.*');
    }

    /**
     * Query scope to filter image files.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Query scope to filter PDF files.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopePdfs($query)
    {
        return $query->where('mime_type', 'application/pdf');
    }

    /**
     * Query scope to search files by name or mime type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder Modified query builder instance
     */
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('mime_type', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }
}