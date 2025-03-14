<?php

use Illuminate\Support\Facades\env; // illuminate/support ^10.0

/*
|--------------------------------------------------------------------------
| Session Driver
|--------------------------------------------------------------------------
|
| This option controls the default session "driver" that will be used on
| requests. The available driver options are: file, cookie, database,
| redis, and array. Generally, we utilize the redis driver.
|
*/

return [

    'driver' => env('SESSION_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to immediately expire on the browser closing, set expire_on_close to true.
    |
    */

    'lifetime' => env('SESSION_LIFETIME', 30),

    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to determine whether the session data should be
    | encrypted while at rest. By default, Laravel will encrypt your
    | session data using the encryption key specified in your .env file.
    |
    */

    'encrypt' => true,

    /*
    |--------------------------------------------------------------------------
    | Session Files Storage Path
    |--------------------------------------------------------------------------
    |
    | When using the "file" session driver, you need to specify the path
    | where the session files should be stored. A sensible default has
    | already been setup for you.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you should configure the database
    | connection that should be used to store your session records. Of course,
    | you will first need to create a migration for the session table.
    |
    */

    'connection' => env('SESSION_CONNECTION', 'session'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table we
    | should use to store the session data. By default, we will use a table
    | named "sessions".
    |
    */

    'table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | While using the "database" or "redis" session drivers, you may specify a
    | cache store that should be used for caching the session data for faster
    | retrieval. This can improve the overall performance of your application.
    |
    */

    'store' => env('SESSION_STORE', null),

    /*
    |--------------------------------------------------------------------------
    | Session Lottery Chance
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, there is a chance
    | that each incoming request will kick off a "garbage collection" routine.
    | Typically, this routine will simply delete the expired session records
    | from your storage.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the cookie that will be used to store
    | the session ID in the user's browser. This allows you to run multiple
    | applications which use Laravel on the same domain.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        str_slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | Here you may define the path that should be used for the session cookie.
    | Typically, this will be '/', unless you are running your entire
    | Laravel application from within a nested sub-directory.
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | Here you may define the domain that should be used for the session cookie.
    | This is useful for sharing session variables between subdomains.
    |
    */

    'domain' => env('SESSION_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Secure Flag
    |--------------------------------------------------------------------------
    |
    | When transferring sensitive data between the client and server, encryption
    | may be utilized. This option defines whether the session cookie will only
    | be sent back to the server if the connection has been made via HTTPS.
    |
    */

    'secure' => true,

    /*
    |--------------------------------------------------------------------------
    | Session Cookie HTTP Only Flag
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, the session cookie will only be accessible
    | through the HTTP protocol. This means that the cookie won't be accessible
    | by scripting languages, such as JavaScript.
    |
    */

    'http_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Session Cookie SameSite Flag
    |--------------------------------------------------------------------------
    |
    | This configuration option determines how cross-site cookies should be handled.
    | It has significant implications for the security of your application.
    |
    | Supported: 'lax', 'strict', 'none', null
    */

    'same_site' => 'lax',

];