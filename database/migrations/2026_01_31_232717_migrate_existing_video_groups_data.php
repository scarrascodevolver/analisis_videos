<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migra datos existentes de video_group_id (columna en videos)
     * a la nueva estructura de tablas (video_groups + video_group_video)
     */
    public function up(): void
    {
        Log::info('Starting video groups data migration...');

        // Obtener todos los grupos únicos existentes
        $existingGroups = DB::table('videos')
            ->whereNotNull('video_group_id')
            ->select('video_group_id', 'organization_id')
            ->groupBy('video_group_id', 'organization_id')
            ->get();

        Log::info("Found {$existingGroups->count()} existing video groups to migrate");

        foreach ($existingGroups as $group) {
            // Crear registro en video_groups
            $videoGroupId = DB::table('video_groups')->insertGetId([
                'name' => null, // Sin nombre por defecto
                'organization_id' => $group->organization_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Created video_group #{$videoGroupId} for old group_id: {$group->video_group_id}");

            // Obtener todos los videos de este grupo
            $videos = DB::table('videos')
                ->where('video_group_id', $group->video_group_id)
                ->get();

            Log::info("Migrating {$videos->count()} videos for group {$group->video_group_id}");

            // Insertar relaciones en tabla pivot
            foreach ($videos as $video) {
                DB::table('video_group_video')->insert([
                    'video_group_id' => $videoGroupId,
                    'video_id' => $video->id,
                    'is_master' => $video->is_master ?? false,
                    'camera_angle' => $video->camera_angle,
                    'sync_offset' => $video->sync_offset,
                    'is_synced' => $video->is_synced ?? false,
                    'sync_reference_event' => $video->sync_reference_event,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("  - Migrated video #{$video->id} (is_master: {$video->is_master}, angle: {$video->camera_angle})");
            }
        }

        Log::info('Video groups data migration completed successfully');
    }

    /**
     * Reverse the migrations.
     *
     * NO intentamos revertir porque los IDs de video_groups son diferentes
     * a los video_group_id originales (que eran strings generados).
     * La migración de limpieza (cleanup) manejará el rollback si es necesario.
     */
    public function down(): void
    {
        Log::warning('Reverting video groups data migration - this may cause data loss');

        // Vaciar tablas en orden inverso
        DB::table('video_group_video')->truncate();
        DB::table('video_groups')->truncate();

        Log::info('Video groups data migration reverted (tables truncated)');
    }
};
