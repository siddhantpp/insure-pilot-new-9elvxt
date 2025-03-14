<?php

/**
 * Laravel - Documents View Feature Development Server
 *
 * This script provides a simple development server for the Documents View feature,
 * allowing developers to test the application without a full web server setup.
 * 
 * @package  InsurePilot
 * @version  1.0.0
 */

// Normalize and decode the requested URI to prevent directory traversal attacks
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Get the full path to the requested file in the public directory
$requested = __DIR__.'/public'.$uri;

// This emulates Apache's "mod_rewrite" functionality for the PHP built-in server.
// If the URI points to an existing file in the public directory, the server will
// serve that file directly with the appropriate MIME type.
if ($uri !== '/' && file_exists($requested)) {
    return false;
}

/**
 * Handles the incoming HTTP request by bootstrapping the Laravel application
 * and passing the request to the HTTP kernel.
 */
function handleRequest()
{
    // Bootstrap the Laravel application
    $app = require __DIR__.'/bootstrap/app.php';

    // Get the HTTP kernel from the application container
    // Laravel framework ^10.0
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    // Create a request instance from the server globals
    $request = Illuminate\Http\Request::capture();

    // Send the request through the kernel and get the response
    $response = $kernel->handle($request);

    // Send the response back to the client
    $response->send();

    // Perform any cleanup tasks
    $kernel->terminate($request, $response);
}

// If we reach this point, the requested file doesn't exist, so we'll
// route the request through the Laravel application
handleRequest();