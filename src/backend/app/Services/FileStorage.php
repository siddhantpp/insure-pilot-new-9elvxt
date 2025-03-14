<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage; // ^10.0
use Illuminate\Support\Facades\Config; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Illuminate\Support\Facades\URL; // ^10.0
use Exception; // 8.2

/**
 * Service class responsible for handling document file storage operations in the Insure Pilot system.
 */
class FileStorage
{
    /**
     * The default disk for file storage.
     */
    protected string $defaultDisk;

    /**
     * The backup disk for file storage.
     */
    protected string $backupDisk;

    /**
     * The allowed MIME types for file storage.
     */
    protected array $allowedMimeTypes;

    /**
     * The maximum file size in bytes.
     */
    protected int $maxFileSize;

    /**
     * Constructor for the FileStorage service
     */
    public function __construct()
    {
        // Initialize the default disk from configuration
        $this->defaultDisk = Config::get('filesystems.document_disk', 'documents');
        
        // Initialize the backup disk from configuration
        $this->backupDisk = Config::get('filesystems.document_backup_disk', 'documents_backup');
        
        // Initialize the allowed MIME types from configuration
        $this->allowedMimeTypes = Config::get('filesystems.allowed_mime_types', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
        ]);
        
        // Initialize the maximum file size from configuration (default: 50MB)
        $this->maxFileSize = Config::get('filesystems.max_file_size', 50 * 1024 * 1024);
    }

    /**
     * Retrieves a file by its ID from the storage system
     *
     * @param int $fileId
     * @return string|null File contents as a string or null if not found
     */
    public function getFile(int $fileId): ?string
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                Log::info("File not found with ID: {$fileId}");
                return null;
            }
            
            // Get the file path
            $filePath = $file->getFullPathAttribute();
            
            // Check if file exists in storage
            if (!$filePath || !Storage::disk($this->defaultDisk)->exists($file->path)) {
                Log::error("File exists in database but not in storage: {$fileId}");
                return null;
            }
            
            // Return the file contents
            return Storage::disk($this->defaultDisk)->get($file->path);
        } catch (Exception $e) {
            Log::error("Error retrieving file {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generates a temporary URL for accessing a file
     *
     * @param int $fileId
     * @param int $expirationMinutes
     * @return string|null Temporary URL for file access or null if not found
     */
    public function getFileUrl(int $fileId, int $expirationMinutes = 60): ?string
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                Log::info("File not found with ID: {$fileId}");
                return null;
            }
            
            // Check if file exists in storage
            if (!Storage::disk($this->defaultDisk)->exists($file->path)) {
                Log::error("File exists in database but not in storage: {$fileId}");
                return null;
            }

            // Generate temporary URL with expiration
            $expirationTime = now()->addMinutes($expirationMinutes);
            return Storage::disk($this->defaultDisk)->temporaryUrl(
                $file->path,
                $expirationTime
            );
        } catch (Exception $e) {
            Log::error("Error generating URL for file {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Checks if a file exists in the storage system
     *
     * @param int $fileId
     * @return bool True if the file exists, false otherwise
     */
    public function fileExists(int $fileId): bool
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                return false;
            }
            
            // Check if file exists in storage
            return Storage::disk($this->defaultDisk)->exists($file->path);
        } catch (Exception $e) {
            Log::error("Error checking file existence {$fileId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the MIME type of a file
     *
     * @param int $fileId
     * @return string|null MIME type of the file or null if not found
     */
    public function getFileMimeType(int $fileId): ?string
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                return null;
            }
            
            // Return the mime_type attribute
            return $file->mime_type;
        } catch (Exception $e) {
            Log::error("Error getting file MIME type {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Gets the size of a file in bytes
     *
     * @param int $fileId
     * @return int|null Size of the file in bytes or null if not found
     */
    public function getFileSize(int $fileId): ?int
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                return null;
            }
            
            // Return the size attribute
            return $file->size;
        } catch (Exception $e) {
            Log::error("Error getting file size {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Gets the name of a file
     *
     * @param int $fileId
     * @return string|null Name of the file or null if not found
     */
    public function getFileName(int $fileId): ?string
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                return null;
            }
            
            // Return the name attribute
            return $file->name;
        } catch (Exception $e) {
            Log::error("Error getting file name {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Stores a file in the storage system and creates a database record
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $name
     * @param int $userId
     * @return \App\Models\File|null Created File model instance or null if storage fails
     */
    public function storeFile(\Illuminate\Http\UploadedFile $file, string $name = '', int $userId = 0): ?\App\Models\File
    {
        try {
            // Validate the file (size, MIME type)
            if (!$this->validateFile($file)) {
                Log::warning("File validation failed: " . $file->getClientOriginalName());
                return null;
            }
            
            // Generate a unique file path
            $path = $this->generateFilePath($file);
            
            // Store the file in the default disk
            $storedPath = Storage::disk($this->defaultDisk)->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );
            
            if (!$storedPath) {
                Log::error("Failed to store file: " . $file->getClientOriginalName());
                return null;
            }
            
            // Create a new File record in the database
            $fileModel = new File();
            $fileModel->name = $name ?: $file->getClientOriginalName();
            $fileModel->path = $storedPath;
            $fileModel->mime_type = $file->getMimeType();
            $fileModel->size = $file->getSize();
            $fileModel->status_id = 1; // Active status
            $fileModel->created_by = $userId;
            $fileModel->updated_by = $userId;
            $fileModel->save();
            
            return $fileModel;
        } catch (Exception $e) {
            Log::error("Error storing file: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Deletes a file from the storage system
     *
     * @param int $fileId
     * @return bool True if deletion was successful, false otherwise
     */
    public function deleteFile(int $fileId): bool
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                return false;
            }
            
            // Get the file path
            $filePath = $file->path;
            
            // Delete the file from storage
            if (Storage::disk($this->defaultDisk)->exists($filePath)) {
                Storage::disk($this->defaultDisk)->delete($filePath);
            }
            
            // Soft delete the file record from the database
            $file->delete();
            
            return true;
        } catch (Exception $e) {
            Log::error("Error deleting file {$fileId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a backup copy of a file in the backup disk
     *
     * @param int $fileId
     * @return bool True if backup was successful, false otherwise
     */
    public function backupFile(int $fileId): bool
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                return false;
            }
            
            // Check if the file exists in the default disk
            if (!Storage::disk($this->defaultDisk)->exists($file->path)) {
                Log::error("Cannot backup file that doesn't exist in primary storage: {$fileId}");
                return false;
            }
            
            // Copy the file to the backup disk
            $fileContents = Storage::disk($this->defaultDisk)->get($file->path);
            Storage::disk($this->backupDisk)->put($file->path, $fileContents);
            
            return true;
        } catch (Exception $e) {
            Log::error("Error backing up file {$fileId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validates a file against size and MIME type restrictions
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return bool True if file is valid, false otherwise
     */
    public function validateFile(\Illuminate\Http\UploadedFile $file): bool
    {
        // Check if the file size is within the maximum allowed size
        if ($file->getSize() > $this->maxFileSize) {
            return false;
        }
        
        // Check if the file MIME type is in the allowed MIME types list
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            return false;
        }
        
        return true;
    }

    /**
     * Generates a unique file path for storing a file
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string Generated file path
     */
    public function generateFilePath(\Illuminate\Http\UploadedFile $file): string
    {
        // Get the original file name
        $originalName = $file->getClientOriginalName();
        
        // Generate a unique identifier (UUID)
        $uniqueId = uniqid();
        
        // Get current date for folder structure
        $date = now();
        
        // Organize into year/month/day folder structure
        $path = $date->format('Y/m/d') . '/' . $uniqueId . '_' . $originalName;
        
        return $path;
    }

    /**
     * Gets the list of allowed MIME types for file storage
     *
     * @return array List of allowed MIME types
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Gets the maximum allowed file size in bytes
     *
     * @return int Maximum file size in bytes
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Gets the name of the default storage disk
     *
     * @return string Default disk name
     */
    public function getDefaultDisk(): string
    {
        return $this->defaultDisk;
    }

    /**
     * Gets the name of the backup storage disk
     *
     * @return string Backup disk name
     */
    public function getBackupDisk(): string
    {
        return $this->backupDisk;
    }
}