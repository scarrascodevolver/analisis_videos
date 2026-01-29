<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class ResetVideoSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:reset-sync
                            {video_id? : ID del video a resetear (opcional)}
                            {--group= : Resetear todos los videos del grupo}
                            {--show : Solo mostrar el estado sin modificar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resetear sincronización de videos multi-cámara';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('show')) {
            return $this->showSyncStatus();
        }

        if ($this->option('group')) {
            return $this->resetGroup($this->option('group'));
        }

        if ($this->argument('video_id')) {
            return $this->resetSingleVideo($this->argument('video_id'));
        }

        $this->error('Debes especificar un video_id o --group');
        $this->info('Ejemplos:');
        $this->line('  php artisan video:reset-sync 117');
        $this->line('  php artisan video:reset-sync --group=GROUP_ID');
        $this->line('  php artisan video:reset-sync --show');

        return Command::FAILURE;
    }

    protected function showSyncStatus()
    {
        $videos = Video::whereNotNull('video_group_id')
            ->orderBy('video_group_id')
            ->orderBy('is_master', 'desc')
            ->get();

        if ($videos->isEmpty()) {
            $this->info('No hay videos con multi-cámara configurados');
            return Command::SUCCESS;
        }

        $this->info('Estado de sincronización de videos:');
        $this->newLine();

        $currentGroup = null;
        foreach ($videos as $video) {
            if ($currentGroup !== $video->video_group_id) {
                $this->newLine();
                $this->info("Grupo: {$video->video_group_id}");
                $currentGroup = $video->video_group_id;
            }

            $role = $video->is_master ? 'MASTER' : 'SLAVE ';
            $synced = $video->is_synced ? '✓' : '✗';
            $offset = $video->sync_offset ?? '0.00';

            $this->line(sprintf(
                "  [%s] ID: %d | %s | %s | Offset: %ss | %s",
                $synced,
                $video->id,
                $role,
                $video->camera_angle ?? 'Sin ángulo',
                $offset,
                $video->title
            ));
        }

        return Command::SUCCESS;
    }

    protected function resetSingleVideo($videoId)
    {
        $video = Video::find($videoId);

        if (!$video) {
            $this->error("Video ID {$videoId} no encontrado");
            return Command::FAILURE;
        }

        $this->info("Video: {$video->title}");
        $this->info("Offset actual: " . ($video->sync_offset ?? '0') . " segundos");
        $this->info("Is synced: " . ($video->is_synced ? 'Sí' : 'No'));

        if (!$this->confirm('¿Resetear sincronización?', true)) {
            $this->info('Operación cancelada');
            return Command::SUCCESS;
        }

        $video->sync_offset = null;
        $video->is_synced = false;
        $video->sync_reference_event = null;
        $video->save();

        $this->info('✓ Sincronización reseteada');

        return Command::SUCCESS;
    }

    protected function resetGroup($groupId)
    {
        $videos = Video::where('video_group_id', $groupId)
            ->where('is_master', false) // Solo slaves
            ->get();

        if ($videos->isEmpty()) {
            $this->error("No se encontraron videos slave en el grupo: {$groupId}");
            return Command::FAILURE;
        }

        $this->info("Se resetearán {$videos->count()} videos del grupo {$groupId}:");
        foreach ($videos as $video) {
            $this->line("  - ID: {$video->id} | {$video->camera_angle} | Offset: {$video->sync_offset}s");
        }

        if (!$this->confirm('¿Continuar?', true)) {
            $this->info('Operación cancelada');
            return Command::SUCCESS;
        }

        foreach ($videos as $video) {
            $video->sync_offset = null;
            $video->is_synced = false;
            $video->sync_reference_event = null;
            $video->save();

            $this->info("✓ Video {$video->id} reseteado");
        }

        $this->info("✓ Grupo {$groupId} reseteado completamente");

        return Command::SUCCESS;
    }
}
