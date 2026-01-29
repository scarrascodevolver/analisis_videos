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

// Compresión automática de videos (horario nocturno)
// Se ejecuta cada hora entre 3:00 AM y 7:00 AM
// Procesa 1 video pendiente por ejecución para no saturar el servidor
Schedule::call(function () {
    $video = \App\Models\Video::where('processing_status', 'pending')
        ->orderBy('created_at', 'asc') // FIFO: primero en entrar, primero en salir
        ->first();

    if ($video) {
        \Illuminate\Support\Facades\Log::info("Nocturnal compression: Processing video {$video->id} - {$video->title}");
        \App\Jobs\CompressVideoJob::dispatch($video->id);
    } else {
        \Illuminate\Support\Facades\Log::info("Nocturnal compression: No pending videos in queue");
    }
})
    ->name('nocturnal-video-compression')
    ->hourly()
    ->between('03:00', '07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/nocturnal-compression.log'));
