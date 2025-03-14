<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

use App\Services\AuditLogger;
use App\Services\DocumentManager;
use App\Services\FileStorage;
use App\Services\MetadataService;
use App\Services\NotificationService;
use App\Services\PdfViewerService;

/**
 * Service provider responsible for registering and bootstrapping application services for the Documents View feature.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var array
     */
    protected $defer = true;

    /**
     * Register services in the application service container
     *
     * @return void
     */
    public function register(): void
    {
        // Register services in dependency order
        $this->registerAuditLogger();
        $this->registerFileStorage();
        $this->registerMetadataService();
        $this->registerPdfViewerService();
        $this->registerDocumentManager();
        $this->registerNotificationService();
    }

    /**
     * Bootstrap any application services after registration
     *
     * @return void
     */
    public function boot(): void
    {
        // Register custom Blade directives for document viewing
        $this->registerBladeDirectives();

        // Configure URL generation for document files
        URL::macro('documentFile', function ($documentId) {
            return url("/documents/{$documentId}/file");
        });

        // Configure URL generation for document viewer
        URL::macro('documentViewer', function ($documentId) {
            return url("/documents/view/{$documentId}");
        });
    }

    /**
     * Get the services provided by the provider for deferred loading
     *
     * @return array Array of service names provided by this provider
     */
    public function provides(): array
    {
        return [
            AuditLogger::class,
            DocumentManager::class,
            FileStorage::class,
            MetadataService::class,
            NotificationService::class,
            PdfViewerService::class,
        ];
    }

    /**
     * Register the AuditLogger service in the container
     *
     * @return void
     */
    protected function registerAuditLogger(): void
    {
        $this->app->singleton(AuditLogger::class, function ($app) {
            return new AuditLogger();
        });
    }

    /**
     * Register the FileStorage service in the container
     *
     * @return void
     */
    protected function registerFileStorage(): void
    {
        $this->app->singleton(FileStorage::class, function ($app) {
            return new FileStorage();
        });
    }

    /**
     * Register the DocumentManager service in the container
     *
     * @return void
     */
    protected function registerDocumentManager(): void
    {
        $this->app->singleton(DocumentManager::class, function ($app) {
            return new DocumentManager(
                $app->make(FileStorage::class),
                $app->make(MetadataService::class),
                $app->make(AuditLogger::class),
                $app->make(PdfViewerService::class)
            );
        });
    }

    /**
     * Register the MetadataService in the container
     *
     * @return void
     */
    protected function registerMetadataService(): void
    {
        $this->app->singleton(MetadataService::class, function ($app) {
            return new MetadataService(
                $app->make(AuditLogger::class)
            );
        });
    }

    /**
     * Register the PdfViewerService in the container
     *
     * @return void
     */
    protected function registerPdfViewerService(): void
    {
        $this->app->singleton(PdfViewerService::class, function ($app) {
            return new PdfViewerService(
                $app->make(FileStorage::class)
            );
        });
    }

    /**
     * Register the NotificationService in the container
     *
     * @return void
     */
    protected function registerNotificationService(): void
    {
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
    }

    /**
     * Register custom Blade directives for document viewing
     *
     * @return void
     */
    protected function registerBladeDirectives(): void
    {
        // @documentViewer directive for embedding the document viewer
        Blade::directive('documentViewer', function ($expression) {
            return "<?php echo view('components.document-viewer', {$expression})->render(); ?>";
        });
        
        // @pdfViewer directive for embedding the PDF viewer component
        Blade::directive('pdfViewer', function ($expression) {
            return "<?php echo view('components.pdf-viewer', {$expression})->render(); ?>";
        });
        
        // @documentMetadata directive for displaying document metadata
        Blade::directive('documentMetadata', function ($expression) {
            return "<?php echo view('components.document-metadata', {$expression})->render(); ?>";
        });
    }
}