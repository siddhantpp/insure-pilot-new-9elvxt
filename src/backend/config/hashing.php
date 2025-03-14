<?php

/**
 * Hashing Configuration
 * 
 * This file configures the hashing algorithms and settings used throughout
 * the Insure Pilot application for password storage and verification.
 * 
 * Secure password hashing is critical for protecting user credentials
 * even in the event of a database breach.
 * 
 * @version 1.0.0
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for the application. By default, the bcrypt algorithm is
    | used; however, you may specify any of the other supported drivers.
    |
    | Supported: "bcrypt", "argon2id", "argon2i"
    |
    */

    'default' => env('HASHING_DRIVER', 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Bcrypt algorithm. This will allow you
    | to control the amount of time it takes to hash the given password.
    |
    | The work factor (rounds) represents the computational cost required to 
    | verify a password. Higher values are more secure but take longer to process.
    | 
    | For the Insure Pilot application, we use 12 rounds to align with
    | industry standards and security requirements.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2id Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Argon2id algorithm. This algorithm is
    | more secure than bcrypt but may require more processing resources.
    |
    | memory: The amount of memory (in kibibytes) to use when computing the hash
    | threads: The number of threads to use when computing the hash
    | time: The number of iterations to perform when computing the hash
    |
    */

    'argon2id' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2i Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Argon2i algorithm. This variant is less
    | resistant to side-channel attacks than Argon2id but may be required
    | for compatibility with certain systems.
    |
    */

    'argon2i' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
    ],

];