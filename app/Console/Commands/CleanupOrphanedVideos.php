<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class CleanupOrphanedVideos extends Command
{
    protected $signature = 'videos:cleanup-orphaned
                            {--dry-run : Simular sin eliminar archivos}
                            {--hours=3 : Archivos más antiguos que X horas (default: 3)}';

    protected $description = 'Elimina archivos de video huérfanos (sin registro en BD) de DigitalOcean Spaces';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $hours = (int) $this->option('hours');

        if ($dryRun) {
            $this->warn('=== MODO DRY-RUN: No se eliminarán archivos ===');
        }

        $this->info("Buscando archivos huérfanos (más antiguos que {$hours} horas)...");
        $this->newLine();

        $orphanedFiles = [];
        $orphanedSize = 0;

        // 1. Limpiar uploads pendientes en cache que expiraron
        $this->info('1. Verificando uploads pendientes en cache...');
        $pendingCleaned = $this->cleanPendingUploads($dryRun);
        $this->line("   Uploads pendientes limpiados: {$pendingCleaned}");

        // 2. Buscar archivos en Spaces sin registro en BD
        $this->newLine();
        $this->info('2. Escaneando archivos en DigitalOcean Spaces...');

        try {
            $files = Storage::disk('spaces')->allFiles('videos');
            $this->line("   Archivos encontrados en Spaces: " . count($files));

            $bar = $this->output->createProgressBar(count($files));
            $bar->start();

            foreach ($files as $file) {
                $bar->advance();

                // Obtener metadata del archivo
                $lastModified = Storage::disk('spaces')->lastModified($file);
                $ageInHours = (time() - $lastModified) / 3600;

                // Solo procesar archivos más antiguos que el umbral
                if ($ageInHours < $hours) {
                    continue;
                }

                // Verificar si existe en BD
                $existsInDb = Video::withoutGlobalScopes()
                    ->where('file_path', $file)
                    ->orWhere('original_file_path', $file)
                    ->exists();

                if (!$existsInDb) {
                    $size = Storage::disk('spaces')->size($file);
                    $orphanedFiles[] = [
                        'path' => $file,
                        'size' => $size,
                        'age_hours' => round($ageInHours, 1),
                    ];
                    $orphanedSize += $size;
                }
            }

            $bar->finish();
            $this->newLine(2);

        } catch (\Exception $e) {
            $this->error("Error escaneando Spaces: " . $e->getMessage());
            return 1;
        }

        // 3. Mostrar archivos huérfanos encontrados
        if (empty($orphanedFiles)) {
            $this->info('No se encontraron archivos huérfanos.');
            return 0;
        }

        $this->warn("Archivos huérfanos encontrados: " . count($orphanedFiles));
        $this->line("Espacio total a liberar: " . $this->formatBytes($orphanedSize));
        $this->newLine();

        // Mostrar tabla con los archivos
        $tableData = array_map(function ($file) {
            return [
                $file['path'],
                $this->formatBytes($file['size']),
                $file['age_hours'] . 'h',
            ];
        }, array_slice($orphanedFiles, 0, 20)); // Mostrar máximo 20

        $this->table(['Archivo', 'Tamaño', 'Antigüedad'], $tableData);

        if (count($orphanedFiles) > 20) {
            $this->line("... y " . (count($orphanedFiles) - 20) . " archivos más");
        }

        // 4. Eliminar archivos si no es dry-run
        if (!$dryRun) {
            if (!$this->confirm('¿Deseas eliminar estos archivos?')) {
                $this->info('Operación cancelada.');
                return 0;
            }

            $this->newLine();
            $this->info('Eliminando archivos huérfanos...');

            $deleted = 0;
            $errors = 0;

            $bar = $this->output->createProgressBar(count($orphanedFiles));
            $bar->start();

            foreach ($orphanedFiles as $file) {
                try {
                    Storage::disk('spaces')->delete($file['path']);
                    $deleted++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Error eliminando {$file['path']}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("Archivos eliminados: {$deleted}");
            if ($errors > 0) {
                $this->error("Errores: {$errors}");
            }
            $this->info("Espacio liberado: " . $this->formatBytes($orphanedSize));
        }

        // 5. Limpiar thumbnails huérfanos
        $this->newLine();
        $this->info('3. Verificando thumbnails huérfanos...');
        $thumbsCleaned = $this->cleanOrphanedThumbnails($dryRun, $hours);
        $this->line("   Thumbnails huérfanos: {$thumbsCleaned}");

        // Resumen final
        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->table(
            ['Tipo', 'Cantidad'],
            [
                ['Videos huérfanos', count($orphanedFiles)],
                ['Uploads pendientes limpiados', $pendingCleaned],
                ['Thumbnails huérfanos', $thumbsCleaned],
                ['Espacio a liberar', $this->formatBytes($orphanedSize)],
            ]
        );

        if ($dryRun) {
            $this->warn('Ejecuta sin --dry-run para eliminar los archivos.');
        }

        return 0;
    }

    private function cleanPendingUploads(bool $dryRun): int
    {
        // Los uploads pendientes se guardan en cache con prefijo 'pending_upload_'
        // Laravel no permite listar keys de cache fácilmente, así que esto es limitado
        // La limpieza real ocurre cuando el cache expira (3 horas)
        return 0;
    }

    private function cleanOrphanedThumbnails(bool $dryRun, int $hours): int
    {
        $count = 0;

        try {
            $files = Storage::disk('spaces')->allFiles('thumbnails');

            foreach ($files as $file) {
                $lastModified = Storage::disk('spaces')->lastModified($file);
                $ageInHours = (time() - $lastModified) / 3600;

                if ($ageInHours < $hours) {
                    continue;
                }

                // Verificar si existe en BD
                $existsInDb = Video::withoutGlobalScopes()
                    ->where('thumbnail_path', $file)
                    ->exists();

                if (!$existsInDb) {
                    $count++;
                    if (!$dryRun) {
                        Storage::disk('spaces')->delete($file);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn("Error verificando thumbnails: " . $e->getMessage());
        }

        return $count;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
