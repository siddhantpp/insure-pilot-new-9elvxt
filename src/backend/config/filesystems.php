<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. This configuration is used by the Documents View
    | feature for storing and retrieving insurance documents.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'documents'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [
        // Default local storage for application files
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        // Public storage for user-accessible files
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Primary storage for insurance documents
        'documents' => [
            'driver' => 'local',
            'root' => storage_path('app/documents'),
            'visibility' => 'private',
            'throw' => false,
        ],

        // Backup storage for insurance documents
        'documents_backup' => [
            'driver' => 'local',
            'root' => storage_path('app/documents_backup'),
            'visibility' => 'private',
            'throw' => false,
        ],

        // Cloud storage for document backups and disaster recovery
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        // Network File System for shared document storage across services
        'nfs' => [
            'driver' => 'local',
            'root' => '/mnt/documents',
            'visibility' => 'private',
            'throw' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Types and Size Limits
    |--------------------------------------------------------------------------
    |
    | Here you may specify the allowed file types and maximum file size for
    | document uploads. These settings are used by the Documents View feature.
    |
    */

    'file_types' => [
        'allowed' => ['pdf', 'docx', 'xlsx', 'pptx'],
        'default' => 'pdf',
    ],

    'max_file_size' => env('MAX_FILE_SIZE', '50MB'),

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the disks used for document backups and recovery.
    | These settings are used by the Documents View backup system.
    |
    */

    'backup_disk' => env('BACKUP_DISK', 'documents_backup'),
    'cloud_disk' => env('CLOUD_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Visibility Settings
    |--------------------------------------------------------------------------
    |
    | Define the default visibility setting and supported options for files.
    | The default is set to private to ensure insurance documents are not
    | publicly accessible without explicit permission.
    |
    */
    
    'visibility_settings' => [
        'default' => 'private',
        'supported_options' => [
            'public',
            'private',
        ],
    ],
];