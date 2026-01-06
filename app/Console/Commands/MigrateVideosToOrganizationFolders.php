<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateVideosToOrganizationFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:migrate-to-org-folders
                            {--dry-run : Simular la migración sin mover archivos}
                            {--org= : Migrar solo videos de una organización específica (slug)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los videos existentes a carpetas por organización en DigitalOcean Spaces';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $orgSlug = $this->option('org');

        if ($dryRun) {
            $this->warn('=== MODO DRY-RUN: No se moverán archivos realmente ===');
        }

        $this->info('Iniciando migración de videos a carpetas por organización...');
        $this->newLine();

        // Obtener organizaciones a procesar
        $orgsQuery = Organization::query();
        if ($orgSlug) {
            $orgsQuery->where('slug', $orgSlug);
        }
        $organizations = $orgsQuery->get();

        if ($organizations->isEmpty()) {
            $this->error('No se encontraron organizaciones para procesar.');
            return 1;
        }

        $totalMoved = 0;
        $totalErrors = 0;
        $totalSkipped = 0;

        foreach ($organizations as $org) {
            $this->info("Procesando organización: {$org->name} (slug: {$org->slug})");
            $this->line(str_repeat('-', 60));

            // Obtener videos de esta organización que NO están en la carpeta correcta
            $videos = Video::withoutGlobalScope('organization')
                ->where('organization_id', $org->id)
                ->get();

            if ($videos->isEmpty()) {
                $this->line("  No hay videos para esta organización.");
                $this->newLine();
                continue;
            }

            $bar = $this->output->createProgressBar($videos->count());
            $bar->start();

            foreach ($videos as $video) {
                $result = $this->migrateVideo($video, $org, $dryRun);

                switch ($result) {
                    case 'moved':
                        $totalMoved++;
                        break;
                    case 'skipped':
                        $totalSkipped++;
                        break;
                    case 'error':
                        $totalErrors++;
                        break;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
        }

        // Resumen
        $this->newLine();
        $this->info('=== RESUMEN DE MIGRACIÓN ===');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Videos movidos', $totalMoved],
                ['Videos ya en carpeta correcta', $totalSkipped],
                ['Errores', $totalErrors],
            ]
        );

        if ($dryRun) {
            $this->warn('Este fue un DRY-RUN. Ejecuta sin --dry-run para aplicar los cambios.');
        }

        return $totalErrors > 0 ? 1 : 0;
    }

    /**
     * Migrar un video individual
     */
    private function migrateVideo(Video $video, Organization $org, bool $dryRun): string
    {
        $currentPath = $video->file_path;
        $orgFolder = "videos/{$org->slug}";

        // Verificar si ya está en la carpeta de la organización
        if (str_starts_with($currentPath, $orgFolder)) {
            return 'skipped';
        }

        // Determinar el nuevo path
        // Casos posibles:
        // 1. videos/filename.mp4 → videos/{org-slug}/filename.mp4
        // 2. videos/player-uploads/filename.mp4 → videos/{org-slug}/player-uploads/filename.mp4

        $filename = basename($currentPath);
        $isPlayerUpload = str_contains($currentPath, 'player-uploads');

        if ($isPlayerUpload) {
            $newPath = "videos/{$org->slug}/player-uploads/{$filename}";
        } else {
            $newPath = "videos/{$org->slug}/{$filename}";
        }

        if ($dryRun) {
            $this->line("  [DRY-RUN] {$currentPath} → {$newPath}");
            return 'moved';
        }

        try {
            // Mover archivo en DigitalOcean Spaces
            if (Storage::disk('spaces')->exists($currentPath)) {
                // Copiar al nuevo path
                Storage::disk('spaces')->copy($currentPath, $newPath);

                // Verificar que se copió correctamente
                if (Storage::disk('spaces')->exists($newPath)) {
                    // Actualizar path en base de datos
                    DB::table('videos')
                        ->where('id', $video->id)
                        ->update(['file_path' => $newPath]);

                    // Eliminar archivo original
                    Storage::disk('spaces')->delete($currentPath);

                    $this->line("  Movido: {$currentPath} → {$newPath}");

                    // Migrar thumbnail si existe
                    $this->migrateThumbnail($video, $org);

                    return 'moved';
                } else {
                    $this->error("  Error: No se pudo verificar la copia de {$currentPath}");
                    return 'error';
                }
            } else {
                // Intentar en storage local
                $localPath = 'public/' . $currentPath;
                if (Storage::disk('local')->exists($localPath)) {
                    $this->warn("  Video en storage local, no en Spaces: {$currentPath}");
                    return 'skipped';
                } else {
                    $this->error("  Archivo no encontrado: {$currentPath}");
                    return 'error';
                }
            }
        } catch (\Exception $e) {
            $this->error("  Error moviendo {$currentPath}: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Migrar thumbnail si existe
     */
    private function migrateThumbnail(Video $video, Organization $org): void
    {
        if (empty($video->thumbnail_path)) {
            return;
        }

        $currentThumbPath = $video->thumbnail_path;
        $orgFolder = "thumbnails/{$org->slug}";

        // Verificar si ya está en la carpeta correcta
        if (str_starts_with($currentThumbPath, $orgFolder)) {
            return;
        }

        $filename = basename($currentThumbPath);
        $newThumbPath = "thumbnails/{$org->slug}/{$filename}";

        try {
            if (Storage::disk('spaces')->exists($currentThumbPath)) {
                Storage::disk('spaces')->copy($currentThumbPath, $newThumbPath);

                if (Storage::disk('spaces')->exists($newThumbPath)) {
                    DB::table('videos')
                        ->where('id', $video->id)
                        ->update(['thumbnail_path' => $newThumbPath]);

                    Storage::disk('spaces')->delete($currentThumbPath);
                }
            }
        } catch (\Exception $e) {
            // Log pero no fallar por thumbnails
            $this->warn("    No se pudo migrar thumbnail: " . $e->getMessage());
        }
    }
}
