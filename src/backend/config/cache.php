<?php

use Illuminate\Support\Facades\Env;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    | Redis is used as the default cache driver to provide optimal performance
    | for document metadata, dropdown options, and other frequently accessed data.
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "apc", "array", "database", "file",
    |         "memcached", "redis", "dynamodb", "octane", "null"
    |
    | The Documents View feature primarily uses Redis for caching document 
    | metadata, dropdown options, and user permissions. File cache is 
    | configured as a fallback for critical operations if Redis is unavailable.
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => env('DB_CONNECTION', 'mysql'),
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            // Used as fallback for critical operations if Redis is unavailable
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_LOCK_CONNECTION', 'default'),
            // Redis 7.x is used for optimal performance in caching document data
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    | The prefix allows clear separation of Insure Pilot cache entries from
    | other applications that might share the same cache infrastructure.
    |
    */

    'prefix' => env('CACHE_PREFIX', 'insurepilot_cache'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Settings
    |--------------------------------------------------------------------------
    |
    | The following TTL (Time-To-Live) settings are used for different types of
    | cached data within the Documents View feature. These values can be 
    | overridden in the environment file for specific environments.
    |
    | document_metadata: 30 minutes (1800 seconds)
    | dropdown_options: 60 minutes (3600 seconds)
    | user_permissions: 15 minutes (900 seconds)
    | query_results: 10 minutes (600 seconds)
    |
    */

    // TTL values are in minutes for Laravel cache methods that accept minutes
    'ttl' => [
        'document_metadata' => env('CACHE_TTL_DOCUMENT_METADATA', 30),
        'dropdown_options' => env('CACHE_TTL_DROPDOWN_OPTIONS', 60),
        'user_permissions' => env('CACHE_TTL_USER_PERMISSIONS', 15),
        'query_results' => env('CACHE_TTL_QUERY_RESULTS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Resilience
    |--------------------------------------------------------------------------
    |
    | The Documents View feature implements cache resilience with fallback
    | mechanisms to ensure reliable operation even during cache failures.
    |
    | For distributed environments, consistent hashing is used to distribute
    | cache entries across multiple Redis instances for better resilience.
    |
    */

    'resilience' => [
        'enable_fallback' => env('CACHE_ENABLE_FALLBACK', true),
        'fallback_driver' => env('CACHE_FALLBACK_DRIVER', 'file'),
        'consistent_hashing' => env('CACHE_CONSISTENT_HASHING', true),
    ],

];