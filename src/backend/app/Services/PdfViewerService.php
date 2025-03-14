<?php

namespace App\Services;

use App\Models\File;
use App\Services\FileStorage;
use Illuminate\Support\Facades\Config; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Exception; // 8.2
use Illuminate\Support\Facades\URL; // ^10.0

/**
 * Service class responsible for integrating with Adobe Acrobat PDF viewer and providing 
 * configuration for document viewing in the Insure Pilot system.
 */
class PdfViewerService
{
    /**
     * FileStorage service for handling document file operations
     */
    protected FileStorage $fileStorage;
    
    /**
     * URL to the Adobe Acrobat PDF viewer SDK
     */
    protected string $sdkUrl;
    
    /**
     * Default zoom level for the PDF viewer
     */
    protected string $defaultZoom;
    
    /**
     * Additional viewer options
     */
    protected array $viewerOptions;
    
    /**
     * List of MIME types supported by the PDF viewer
     */
    protected array $supportedMimeTypes;

    /**
     * Constructor for the PdfViewerService
     *
     * @param FileStorage $fileStorage Service for handling document file storage operations
     */
    public function __construct(FileStorage $fileStorage)
    {
        $this->fileStorage = $fileStorage;
        
        // Load configuration values from config/documents.php
        $this->sdkUrl = Config::get('documents.pdf_viewer.sdk_url', 'https://documentcloud.adobe.com/view-sdk/main.js');
        $this->defaultZoom = Config::get('documents.pdf_viewer.default_zoom', 'FitWidth');
        $this->viewerOptions = Config::get('documents.pdf_viewer.viewer_options', [
            'embedMode' => 'LIGHT_BOX',
            'showDownloadPDF' => false,
            'showPrintPDF' => true,
            'showLeftHandPanel' => false,
            'enableFormFilling' => false,
        ]);
        $this->supportedMimeTypes = Config::get('documents.pdf_viewer.supported_mime_types', [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }

    /**
     * Generates a URL for viewing a document in the PDF viewer
     *
     * @param int $fileId The ID of the file to view
     * @param int $expirationMinutes The number of minutes until the URL expires
     * @return string|null Viewer URL or null if file not found or not supported
     */
    public function getDocumentViewUrl(int $fileId, int $expirationMinutes = 60): ?string
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                return null;
            }
            
            // Check if the file's mime type is supported by the PDF viewer
            if (!$this->isMimeTypeSupported($file->mime_type)) {
                Log::warning("File mime type not supported by PDF viewer: {$file->mime_type}");
                return null;
            }
            
            // Get a secure file URL from the FileStorage service
            $fileUrl = $this->fileStorage->getFileUrl($fileId, $expirationMinutes);
            
            if (!$fileUrl) {
                return null;
            }
            
            // Generate and return a viewer URL with the file URL as a parameter
            return $this->generateViewerUrl($fileUrl);
        } catch (Exception $e) {
            Log::error("Error generating document view URL for file {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves the configuration for the PDF viewer
     *
     * @param int $fileId The ID of the file to view
     * @return array Viewer configuration options
     */
    public function getViewerConfig(int $fileId): array
    {
        try {
            // Find the file record in the database
            $file = File::find($fileId);
            
            if (!$file) {
                // Return default configuration if file not found
                return [
                    'sdkUrl' => $this->sdkUrl,
                    'defaultZoom' => $this->defaultZoom,
                    'viewerOptions' => $this->viewerOptions,
                ];
            }
            
            // Create a configuration array with basic settings
            $config = [
                'sdkUrl' => $this->sdkUrl,
                'defaultZoom' => $this->defaultZoom,
                'viewerOptions' => $this->viewerOptions,
                'fileId' => $fileId,
                'fileName' => $file->name,
                'mimeType' => $file->mime_type,
            ];
            
            return $config;
        } catch (Exception $e) {
            Log::error("Error retrieving PDF viewer configuration for file {$fileId}: " . $e->getMessage());
            
            // Return default configuration on error
            return [
                'sdkUrl' => $this->sdkUrl,
                'defaultZoom' => $this->defaultZoom,
                'viewerOptions' => $this->viewerOptions,
            ];
        }
    }

    /**
     * Gets the URL to the Adobe Acrobat PDF viewer SDK
     *
     * @return string SDK URL
     */
    public function getViewerSdkUrl(): string
    {
        return $this->sdkUrl;
    }

    /**
     * Gets the default zoom level for the PDF viewer
     *
     * @return string Default zoom level
     */
    public function getDefaultZoom(): string
    {
        return $this->defaultZoom;
    }

    /**
     * Gets the viewer options for the PDF viewer
     *
     * @return array Viewer options
     */
    public function getViewerOptions(): array
    {
        return $this->viewerOptions;
    }

    /**
     * Gets the list of supported MIME types for the PDF viewer
     *
     * @return array List of supported MIME types
     */
    public function getSupportedMimeTypes(): array
    {
        return $this->supportedMimeTypes;
    }

    /**
     * Checks if a MIME type is supported by the PDF viewer
     *
     * @param string $mimeType The MIME type to check
     * @return bool True if the MIME type is supported, false otherwise
     */
    public function isMimeTypeSupported(string $mimeType): bool
    {
        return in_array($mimeType, $this->supportedMimeTypes);
    }

    /**
     * Generates a URL for the PDF viewer with the provided file URL
     *
     * @param string $fileUrl The URL to the file to view
     * @return string Viewer URL
     */
    protected function generateViewerUrl(string $fileUrl): string
    {
        // Generate a URL to the document viewer route, adding the file URL as a parameter
        return URL::route('documents.viewer', ['fileUrl' => urlencode($fileUrl)]);
    }
}