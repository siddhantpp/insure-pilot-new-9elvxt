<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => true,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Queue Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may define specific settings for document-related queues.
    | These settings will be used by the document processing system to
    | efficiently manage different types of document operations based on priority.
    |
    */

    'document_queues' => [
        'document-critical' => [
            'description' => 'High-priority queue for user-facing document operations',
            'worker_count' => 3,
            'retry_after' => 60,
        ],
        'document-background' => [
            'description' => 'Medium-priority queue for background tasks like indexing and notifications',
            'worker_count' => 2,
            'retry_after' => 300,
        ],
        'document-maintenance' => [
            'description' => 'Low-priority queue for maintenance tasks like cleanup and archiving',
            'worker_count' => 1,
            'retry_after' => 600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Job-Specific Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may define specific settings for individual job classes.
    | These settings will override the default queue settings for each job.
    |
    */

    'job_configurations' => [
        'App\\Jobs\\ArchiveDocuments' => [
            'queue' => 'document-maintenance',
            'tries' => 3,
            'backoff' => [60, 300, 600], // Progressive backoff: 1 min, 5 mins, 10 mins
        ],
        'App\\Jobs\\CleanupTrashedDocuments' => [
            'queue' => 'document-maintenance',
            'tries' => 3,
            'backoff' => [60, 300, 600],
        ],
        'App\\Jobs\\ProcessDocumentIndex' => [
            'queue' => 'document-background',
            'tries' => 5,
            'backoff' => [30, 60, 120, 300, 600],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Global retry settings for queue jobs across the application.
    | These settings provide default behavior for job retry attempts.
    |
    */

    'retry_settings' => [
        'max_tries' => 5,
        'backoff_strategy' => 'exponential',
        'max_exceptions' => 3,
    ],

];