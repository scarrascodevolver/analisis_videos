<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla pivot para relación many-to-many entre videos y grupos.
     * Un video puede estar en múltiples grupos simultáneamente.
     */
    public function up(): void
    {
        Schema::create('video_group_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_group_id')->constrained('video_groups')->onDelete('cascade');
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');

            // Campos de sincronización multi-cámara (específicos por grupo)
            $table->boolean('is_master')->default(false)
                ->comment('Indica si es el video principal del grupo (tiene XML, clips, timeline)');
            $table->string('camera_angle', 100)->nullable()
                ->comment('Nombre del ángulo: Tribuna Central, Lateral Derecha, Try Zone, Drone, etc.');
            $table->decimal('sync_offset', 8, 2)->nullable()
                ->comment('Offset de sincronización en segundos. NULL = no sincronizado');
            $table->boolean('is_synced')->default(false)
                ->comment('Indica si el video ya fue sincronizado con el master');
            $table->string('sync_reference_event', 255)->nullable()
                ->comment('Evento usado como referencia para la sincronización (ej: Kickoff Inicial)');

            $table->timestamps();

            // Índices
            $table->unique(['video_group_id', 'video_id'], 'vgv_unique');
            $table->index('video_group_id');
            $table->index('video_id');
            $table->index(['video_group_id', 'is_master'], 'vgv_group_master');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_group_video');
    }
};
