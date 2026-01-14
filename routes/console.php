<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Clean up orphan temporary video files older than 24 hours
// Runs daily at 3:00 AM to avoid peak usage times
Schedule::command('videos:cleanup-temp --hours=24')
    ->daily()
    ->at('03:00')
    ->appendOutputTo(storage_path('logs/cleanup-temp.log'))
    ->withoutOverlapping();
