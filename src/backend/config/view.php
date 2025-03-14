<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. For the Documents
    | View feature, we're using the standard Laravel views directory structure.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Expires
    |--------------------------------------------------------------------------
    |
    | Set to false to disable view expiration for the Documents View feature.
    | This prevents views from being regenerated unless they're modified.
    |
    */
    
    'expires' => false,

    /*
    |--------------------------------------------------------------------------
    | Documents View Feature Configuration
    |--------------------------------------------------------------------------
    |
    | The following is documentation for how the Documents View feature extends
    | the view system. These are not actual configuration values but serve as
    | documentation for developers. The actual implementation of these features
    | is handled in service providers and other components of the application.
    |
    | View Namespaces:
    | - document: resources/views/document (Document viewer templates)
    | - metadata: resources/views/metadata (Metadata panel templates)
    | - history: resources/views/history (Document history panel templates)
    | - shared: resources/views/shared (Shared components across feature)
    |
    | Blade Components Path: resources/views/components
    | Blade Components Namespace: App\View\Components
    |
    | Custom Blade Directives:
    | - @canProcessDocument - Checks if user can process the document
    | - @canTrashDocument - Checks if user can trash the document
    | - @canViewDocumentHistory - Checks if user can view document history
    | - @documentIsProcessed - Checks if document is in processed state
    |
    | Integration Points:
    | - Adobe Acrobat PDF viewer integration via JS components
    | - React component mounting points in Blade templates
    | - Metadata form templates for document processing
    |
    */
];