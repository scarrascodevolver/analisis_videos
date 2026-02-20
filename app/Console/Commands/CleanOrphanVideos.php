<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanOrphanVideos extends Command
{
    protected $signature   = 'videos:clean-orphans {--dry-run : Solo mostrar sin eliminar} {--hours=24 : Horas de antigüedad mínima}';
    protected $description = 'Elimina videos en estado pendingupload que nunca completaron la subida';

    public function handle(): void
    {
        $hours   = (int) $this->option('hours');
        $dryRun  = $this->option('dry-run');

        $orphans = Video::withoutGlobalScopes()
            ->where('bunny_status', 'pendingupload')
            ->where('created_at', '<', now()->subHours($hours))
            ->with('organization')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('No hay videos huérfanos.');
            return;
        }

        $this->info("Encontrados {$orphans->count()} videos huérfanos (>{$hours}h en pendingupload):");

        foreach ($orphans as $video) {
            $this->line("  #{$video->id} — {$video->title} — org:{$video->organization_id} — creado:{$video->created_at}");

            if ($dryRun) {
                continue;
            }

            // Eliminar de Bunny si tiene GUID
            if ($video->bunny_video_id && $video->organization) {
                try {
                    BunnyStreamService::forOrganization($video->organization)
                        ->deleteVideo($video->bunny_video_id);
                } catch (\Throwable $e) {
                    Log::warning("CleanOrphanVideos: no se pudo eliminar de Bunny video #{$video->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $video->forceDelete();
        }

        if (! $dryRun) {
            $this->info("Eliminados {$orphans->count()} videos huérfanos.");
            Log::info('CleanOrphanVideos completado', ['deleted' => $orphans->count()]);
        } else {
            $this->warn('Modo dry-run: ningún video fue eliminado.');
        }
    }
}
