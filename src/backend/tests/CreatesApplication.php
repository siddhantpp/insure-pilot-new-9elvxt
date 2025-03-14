<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates and bootstraps a new Laravel application instance for testing
     *
     * @return \Illuminate\Foundation\Application The bootstrapped application instance
     */
    public function createApplication(): Application
    {
        // Create a new Laravel application instance with the base path
        $app = new Application(
            $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
        );

        // Bootstrap the application by loading environment variables and configuration
        $app->make(Kernel::class)->bootstrap();

        // Bind the exception handler and kernels to the application container
        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );

        // Load service providers and register facades
        // This happens automatically during the bootstrap process

        // Configure the application for the testing environment
        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', 'testing');
        
        return $app;
    }
}