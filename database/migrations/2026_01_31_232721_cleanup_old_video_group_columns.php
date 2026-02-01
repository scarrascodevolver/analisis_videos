<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IMPORTANTE: Esta migración NO debe ejecutarse hasta confirmar
     * que el nuevo sistema de múltiples grupos funciona correctamente.
     *
     * Elimina las columnas viejas de multi-cámara de la tabla videos:
     * - video_group_id
     * - is_master
     * - camera_angle
     * - sync_offset
     * - is_synced
     * - sync_reference_event
     *
     * Para ejecutar manualmente después de testing:
     * php artisan migrate --path=database/migrations/2026_01_31_232721_cleanup_old_video_group_columns.php
     */
    public function up(): void
    {
        Log::warning('⚠️ CLEANUP MIGRATION - This will drop old multi-camera columns from videos table');
        Log::warning('⚠️ Make sure the new system is working before running this!');

        Schema::table('videos', function (Blueprint $table) {
            // Drop índices primero
            $table->dropIndex(['video_group_id', 'is_master']);
            $table->dropIndex(['video_group_id']);

            // Drop columnas
            $table->dropColumn([
                'video_group_id',
                'is_master',
                'camera_angle',
                'sync_offset',
                'is_synced',
                'sync_reference_event',
            ]);
        });

        Log::info('Old multi-camera columns dropped from videos table');
    }

    /**
     * Reverse the migrations.
     *
     * Restaura las columnas (sin datos, solo estructura)
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('video_group_id', 50)->nullable()->after('organization_id');
            $table->boolean('is_master')->default(true)->after('video_group_id');
            $table->string('camera_angle', 100)->nullable()->after('is_master');
            $table->decimal('sync_offset', 8, 2)->nullable()->after('camera_angle');
            $table->boolean('is_synced')->default(false)->after('sync_offset');
            $table->string('sync_reference_event', 255)->nullable()->after('is_synced');

            // Recrear índices
            $table->index('video_group_id');
            $table->index(['video_group_id', 'is_master']);
        });

        Log::info('Old multi-camera columns restored to videos table (structure only, no data)');
    }
};
