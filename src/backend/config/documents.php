<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Viewer Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration for the Adobe Acrobat PDF viewer
    | integration. These settings control how PDF documents are displayed
    | within the Documents View feature.
    |
    */
    'pdf_viewer' => [
        // Adobe Acrobat PDF viewer SDK URL
        'sdk_url' => 'https://documentcloud.adobe.com/view-sdk/main.js', // v3.0

        // Default zoom setting for document viewer
        'default_zoom' => 'FitWidth', // Options: 'FitWidth', 'FitPage', or numeric zoom level

        // Adobe viewer options
        'viewer_options' => [
            'embedMode' => 'SIZED_CONTAINER', // Display mode for the viewer
            'showDownloadPDF' => false,       // Whether to show download button
            'showPrintPDF' => false,          // Whether to show print button
            'showAnnotationTools' => false,   // Whether to show annotation tools
            'enableFormFilling' => false,     // Whether to allow form filling
        ],

        // Supported MIME types for the document viewer
        'supported_mime_types' => [
            'application/pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Processing Options
    |--------------------------------------------------------------------------
    |
    | This section contains configuration for document processing workflows,
    | including trash retention and auto-processing settings.
    |
    */
    'document_processing' => [
        // Number of days to retain trashed documents before permanent deletion
        'trash_retention_days' => 90,

        // Document types that should be automatically processed
        'auto_process_document_types' => [
            'Policy Renewal Notice',
            'Claim Acknowledgment',
        ],

        // Whether to require all metadata fields before a document can be processed
        'require_metadata_for_processing' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Metadata Configuration
    |--------------------------------------------------------------------------
    |
    | This section defines metadata field requirements and dependencies for
    | the Documents View feature.
    |
    */
    'metadata' => [
        // Fields that must be filled before a document can be processed
        'required_fields' => [
            'policy_number',
            'document_description',
        ],

        // Field dependencies (dependent_field => parent_field)
        'dependent_fields' => [
            'loss_sequence' => 'policy_number',
            'claimant' => 'loss_sequence',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | This section controls pagination settings for document listings.
    |
    */
    'pagination' => [
        // Default number of documents to display per page
        'default_per_page' => 15,

        // Maximum number of documents that can be displayed per page
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Audit Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains settings for audit logging and history tracking
    | of document actions.
    |
    */
    'audit' => [
        // Whether to log when documents are viewed
        'log_document_views' => true,

        // Whether to log metadata changes
        'log_metadata_changes' => true,

        // Whether to log document processing actions (process, trash, etc.)
        'log_document_processing' => true,

        // Number of days to retain document history records
        'history_retention_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Storage Configuration
    |--------------------------------------------------------------------------
    |
    | This section defines where and how document files are stored.
    |
    */
    'storage' => [
        // Laravel filesystem disk to use for document storage
        'disk' => 'documents',

        // Path within the disk where documents are stored
        'path' => 'documents',

        // Minutes until document URLs expire (for private storage)
        'url_expiration_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Security Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains security settings for document handling.
    |
    */
    'security' => [
        // Whether to encrypt documents at rest
        'encrypt_documents' => true,

        // File types that are allowed to be uploaded
        'allowed_file_types' => [
            'pdf',
            'docx',
            'xlsx',
            'pptx',
        ],

        // Maximum file size in megabytes
        'max_file_size_mb' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Feature Flags
    |--------------------------------------------------------------------------
    |
    | This section contains feature flags to enable or disable
    | specific document functionality.
    |
    */
    'features' => [
        // Whether to enable the document viewer
        'enable_document_viewer' => true,

        // Whether to enable document history functionality
        'enable_document_history' => true,

        // Whether to enable document processing functionality
        'enable_document_processing' => true,

        // Whether to enable document trash functionality
        'enable_document_trash' => true,
    ],
];