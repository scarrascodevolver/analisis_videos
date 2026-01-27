<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sistema Multi-Cámara / Multi-Ángulo
     * Permite asociar múltiples videos del mismo partido y sincronizarlos
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Agrupación de videos (mismo partido)
            $table->string('video_group_id', 50)->nullable()->after('organization_id');

            // Rol en el grupo
            $table->boolean('is_master')->default(true)->after('video_group_id')
                ->comment('Indica si es el video principal del grupo (tiene XML, clips, timeline)');

            // Identificación del ángulo
            $table->string('camera_angle', 100)->nullable()->after('is_master')
                ->comment('Nombre del ángulo: Tribuna Central, Lateral Derecha, Try Zone, Drone, etc.');

            // Sincronización
            $table->decimal('sync_offset', 8, 2)->nullable()->after('camera_angle')
                ->comment('Offset de sincronización en segundos. NULL = no sincronizado');

            $table->boolean('is_synced')->default(false)->after('sync_offset')
                ->comment('Indica si el video ya fue sincronizado con el master');

            $table->string('sync_reference_event', 255)->nullable()->after('is_synced')
                ->comment('Evento usado como referencia para la sincronización (ej: Kickoff Inicial)');

            // Índices para mejorar consultas
            $table->index('video_group_id');
            $table->index(['video_group_id', 'is_master']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['video_group_id', 'is_master']);
            $table->dropIndex(['video_group_id']);

            $table->dropColumn([
                'video_group_id',
                'is_master',
                'camera_angle',
                'sync_offset',
                'is_synced',
                'sync_reference_event',
            ]);
        });
    }
};
