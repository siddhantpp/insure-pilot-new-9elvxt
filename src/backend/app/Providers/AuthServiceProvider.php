<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider; // ^10.0
use Illuminate\Support\Facades\Gate; // ^10.0
use App\Models\Document;
use App\Policies\DocumentPolicy;

/**
 * Service provider responsible for registering authorization policies and gates 
 * for the Documents View feature.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Document::class => DocumentPolicy::class,
    ];

    /**
     * Bootstrap the application services, including registering policies and defining gates
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        $this->defineDocumentGates();
        $this->defineRoleGates();
    }

    /**
     * Register the application's policies
     *
     * @return void
     */
    public function registerPolicies()
    {
        parent::registerPolicies();
    }

    /**
     * Define authorization gates specific to document operations
     *
     * @return void
     */
    protected function defineDocumentGates()
    {
        // Define 'view-documents' gate to check if user can view documents
        Gate::define('view-documents', function ($user) {
            // All authenticated users can view documents
            return true;
        });

        // Define 'process-documents' gate to check if user can mark documents as processed
        Gate::define('process-documents', function ($user) {
            // Admins, managers, adjusters, and underwriters can process documents
            return $user->isAdmin() || $user->isManager() || 
                   $user->isAdjuster() || $user->isUnderwriter();
        });

        // Define 'trash-documents' gate to check if user can trash documents
        Gate::define('trash-documents', function ($user) {
            // Only administrators and managers can trash documents
            return $user->isAdmin() || $user->isManager();
        });

        // Define 'override-document-locks' gate to check if user can override document locks
        Gate::define('override-document-locks', function ($user) {
            // Only administrators and managers can override document locks
            return $user->isAdmin() || $user->isManager();
        });
    }

    /**
     * Define authorization gates based on user roles
     *
     * @return void
     */
    protected function defineRoleGates()
    {
        // Define 'is-admin' gate to check if user is an administrator
        Gate::define('is-admin', function ($user) {
            return $user->isAdmin();
        });

        // Define 'is-manager' gate to check if user is a manager
        Gate::define('is-manager', function ($user) {
            return $user->isManager();
        });

        // Define 'is-adjuster' gate to check if user is a claims adjuster
        Gate::define('is-adjuster', function ($user) {
            return $user->isAdjuster();
        });

        // Define 'is-underwriter' gate to check if user is an underwriter
        Gate::define('is-underwriter', function ($user) {
            return $user->isUnderwriter();
        });

        // Define 'is-support' gate to check if user is support staff
        Gate::define('is-support', function ($user) {
            return $user->isSupport();
        });

        // Define 'is-readonly' gate to check if user has read-only access
        Gate::define('is-readonly', function ($user) {
            return $user->isReadOnly();
        });
    }
}