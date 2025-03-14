<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mariadb'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    | MariaDB 10.6+ is used as the primary database with read/write splitting
    | for optimal performance. Read queries are distributed across replicas
    | while write operations are directed to the primary database server.
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mariadb' => [
            'driver' => 'mysql', // MariaDB uses the MySQL driver
            'url' => env('DATABASE_URL'),
            'read' => [
                'host' => [
                    env('DB_READ_HOST1', env('DB_HOST', '127.0.0.1')),
                    env('DB_READ_HOST2', env('DB_HOST', '127.0.0.1')),
                ],
            ],
            'write' => [
                'host' => env('DB_WRITE_HOST', env('DB_HOST', '127.0.0.1')),
            ],
            'sticky' => true, // Use write connection for reads in the same request after a write
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', true), // Connection pooling
                PDO::ATTR_EMULATE_PREPARES => true, // Improve performance for prepared statements
                PDO::ATTR_TIMEOUT => env('DB_TIMEOUT', 60), // Connection timeout
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'path' => database_path('migrations'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    | Redis 7.x is used for caching, session storage, and message queuing
    | to improve application performance and scalability.
    |
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'), // phpredis v5.3+
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', 'insurepilot_database_'),
            'persistent' => env('REDIS_PERSISTENT', true), // Connection pooling for Redis
            'read_timeout' => env('REDIS_READ_TIMEOUT', 60),
            'retry_interval' => env('REDIS_RETRY_INTERVAL', 100),
            'timeout' => env('REDIS_TIMEOUT', 5),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'read_write_timeout' => 60,
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'read_write_timeout' => 60,
        ],

        'session' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
            'read_write_timeout' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Logging and Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Here you can configure query logging for debugging and performance
    | monitoring. Slow queries are logged when they exceed the specified
    | threshold. This helps identify bottlenecks in database operations.
    |
    */

    'query_log' => [
        'enabled' => env('DB_QUERY_LOG', env('APP_DEBUG', false)),
        'slow_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 100), // in milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Pool Settings
    |--------------------------------------------------------------------------
    |
    | Connection pooling improves performance by reusing database connections
    | instead of creating new ones for each request. These settings define
    | the behavior of the connection pool.
    |
    */

    'pool' => [
        'min_connections' => env('DB_POOL_MIN', 5),
        'max_connections' => env('DB_POOL_MAX', 50),
        'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 60), // seconds
        'max_lifetime' => env('DB_POOL_MAX_LIFETIME', 1800), // seconds
    ],

];