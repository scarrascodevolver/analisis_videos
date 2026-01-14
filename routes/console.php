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

// Limpiar archivos temporales locales (storage/app/temp)
// Se ejecuta diariamente a las 3:00 AM
Schedule::command('videos:cleanup-temp --hours=24')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cleanup-temp.log'));

// Limpiar archivos de video huérfanos en DigitalOcean Spaces (sin registro en BD)
// Se ejecuta diariamente a las 3:30 AM
// Solo elimina archivos con más de 6 horas de antigüedad
Schedule::command('videos:cleanup-orphaned --hours=6')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cleanup-orphaned.log'));
