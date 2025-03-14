<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and configuration settings for
    | third-party services used by the Documents View feature. These include
    | Adobe Acrobat PDF viewer for document display, SendGrid for email 
    | notifications, AWS services for storage, and monitoring tools.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Adobe Acrobat PDF Viewer
    |--------------------------------------------------------------------------
    |
    | Configuration for the Adobe Acrobat PDF viewer integration used to display
    | insurance documents in the Documents View feature.
    |
    | Version: Latest (accessed via SDK URL)
    |
    */
    'adobe_pdf' => [
        'sdk_url' => 'https://documentcloud.adobe.com/view-sdk/main.js',
        'client_id' => env('ADOBE_PDF_CLIENT_ID'),
        'default_zoom' => 'FIT_WIDTH',
        'viewer_options' => [
            'embedMode' => 'SIZED_CONTAINER',
            'showDownloadPDF' => false,
            'showPrintPDF' => true,
            'showAnnotationTools' => false,
            'enableFormFilling' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SendGrid
    |--------------------------------------------------------------------------
    |
    | Configuration for SendGrid email notification service used for sending
    | document-related notifications to users.
    |
    | Version: API v3
    |
    */
    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
        'from_email' => env('MAIL_FROM_ADDRESS', 'notifications@insurepilot.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Insure Pilot'),
        'templates' => [
            'document_processed' => 'd-abc123456789',
            'document_updated' => 'd-def123456789',
            'document_trashed' => 'd-ghi123456789',
            'document_assigned' => 'd-jkl123456789',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS Services
    |--------------------------------------------------------------------------
    |
    | Configuration for AWS services used for document storage and delivery,
    | including S3 for document backup and CloudFront for content delivery.
    |
    */
    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'cloudfront' => [
            'enabled' => env('AWS_CLOUDFRONT_ENABLED', false),
            'domain' => env('AWS_CLOUDFRONT_DOMAIN'),
            'key_pair_id' => env('AWS_CLOUDFRONT_KEY_PAIR_ID'),
            'private_key' => env('AWS_CLOUDFRONT_PRIVATE_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | LGTM Monitoring Stack
    |--------------------------------------------------------------------------
    |
    | Configuration for the LGTM monitoring stack (Loki, Grafana, Tempo, Mimir)
    | used for monitoring and logging the Documents View feature.
    |
    | Version: Latest
    |
    */
    'lgtm' => [
        'enabled' => env('LGTM_ENABLED', true),
        'grafana_url' => env('GRAFANA_URL'),
        'loki_url' => env('LOKI_URL'),
        'tempo_url' => env('TEMPO_URL'),
        'mimir_url' => env('MIMIR_URL'),
        'api_key' => env('LGTM_API_KEY'),
    ],

];