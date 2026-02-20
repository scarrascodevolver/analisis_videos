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

// Limpiar videos huérfanos en Bunny (pendingupload > 24h sin completar)
Schedule::command('videos:clean-orphans --hours=24')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/clean-orphans.log'));

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

// Compresión automática de videos (horario nocturno por organización)
// Se ejecuta cada hora y respeta el timezone y horario de cada organización
// Procesa 1 video pendiente por organización que esté en su ventana de compresión
Schedule::call(function () {
    $orgs = \App\Models\Organization::whereIn('compression_strategy', ['nocturnal', 'hybrid'])->get();

    foreach ($orgs as $org) {
        $now = \Carbon\Carbon::now($org->timezone);
        $currentHour = $now->hour;

        if ($currentHour >= $org->compression_start_hour &&
            $currentHour < $org->compression_end_hour) {

            $video = \App\Models\Video::where('organization_id', $org->id)
                ->where('processing_status', 'pending')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($video) {
                \Illuminate\Support\Facades\Log::info("Nocturnal compression for {$org->name}: Processing video {$video->id} - {$video->title} (timezone: {$org->timezone}, hour: {$currentHour})");
                \App\Jobs\CompressVideoJob::dispatch($video->id);
            } else {
                \Illuminate\Support\Facades\Log::info("Nocturnal compression for {$org->name}: No pending videos in queue (timezone: {$org->timezone}, hour: {$currentHour})");
            }
        }
    }
})
    ->name('nocturnal-video-compression-multi-org')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/nocturnal-compression.log'));
