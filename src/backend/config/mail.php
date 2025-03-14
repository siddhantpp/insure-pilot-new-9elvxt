<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    | Default configuration for Documents View uses SendGrid for reliability.
    |
    */

    'default' => env('MAIL_MAILER', 'sendgrid'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "sendgrid",
    |            "postmark", "log", "array", "failover"
    |
    */

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'auth_mode' => null,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'sendgrid' => [
            'transport' => 'sendgrid',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'sendgrid',
                'mailgun',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    | For Documents View, this is the sender address for all notifications.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'notifications@insurepilot.com'),
        'name' => env('MAIL_FROM_NAME', 'Insure Pilot'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "Reply-To" Address
    |--------------------------------------------------------------------------
    |
    | Here you may specify the reply-to address that should be used for all
    | e-mails sent by the application. This is particularly useful for 
    | ensuring users reply to the correct support address.
    |
    */

    'reply_to' => [
        'address' => env('MAIL_REPLY_TO_ADDRESS', 'support@insurepilot.com'),
        'name' => env('MAIL_REPLY_TO_NAME', 'Insure Pilot Support'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Notification Settings
    |--------------------------------------------------------------------------
    |
    | Here you may configure the email notifications sent for various document
    | actions within the Documents View feature. These settings define the
    | subjects and templates used for each notification type.
    |
    */

    'document_notifications' => [
        'processed' => [
            'subject' => 'Document Processed: {document_name}',
            'template' => 'emails.documents.processed',
        ],
        'unprocessed' => [
            'subject' => 'Document Unprocessed: {document_name}',
            'template' => 'emails.documents.unprocessed',
        ],
        'trashed' => [
            'subject' => 'Document Moved to Trash: {document_name}',
            'template' => 'emails.documents.trashed',
        ],
        'updated' => [
            'subject' => 'Document Updated: {document_name}',
            'template' => 'emails.documents.updated',
        ],
        'assigned' => [
            'subject' => 'Document Assigned: {document_name}',
            'template' => 'emails.documents.assigned',
        ],
    ],

];