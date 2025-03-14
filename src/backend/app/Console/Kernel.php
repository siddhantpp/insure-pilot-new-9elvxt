<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use App\Jobs\ArchiveDocuments;
use App\Jobs\CleanupTrashedDocuments;
use App\Jobs\ProcessDocumentIndex;
use App\Services\DocumentManager;
use App\Services\AuditLogger;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule document archiving job to run daily at 1:00 AM
        // This job identifies processed documents older than the configured retention period
        // (default 2 years) and moves them to archive storage to maintain compliance with
        // document retention policies while keeping the active document storage optimized
        $schedule->call(function () {
            ArchiveDocuments::dispatch(
                DocumentManager::getInstance(),
                AuditLogger::getInstance()
            );
            Log::info('Document archiving job dispatched');
        })->dailyAt('01:00')
          ->withoutOverlapping()
          ->runInBackground();

        // Schedule trash cleanup job to run daily at 2:00 AM
        // This job permanently deletes documents that have been in the trash longer than 
        // the configured retention period (default 90 days), implementing the "trash"
        // retention policy from Section 6.2.3 COMPLIANCE CONSIDERATIONS/Data Retention Rules
        $schedule->call(function () {
            CleanupTrashedDocuments::dispatch(
                DocumentManager::getInstance(),
                AuditLogger::getInstance()
            );
            Log::info('Document trash cleanup job dispatched');
        })->dailyAt('02:00')
          ->withoutOverlapping()
          ->runInBackground();

        // Schedule search index maintenance to run hourly
        // This job updates the document search index to ensure it remains accurate and 
        // up-to-date, making document search results more relevant and responsive
        $schedule->call(function () {
            ProcessDocumentIndex::dispatch(0, 'update_index');
            Log::info('Document search index maintenance job dispatched');
        })->hourly()
          ->withoutOverlapping()
          ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}