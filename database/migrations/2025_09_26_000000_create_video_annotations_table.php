<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('video_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('timestamp', 8, 2); // Timestamp en segundos (ej: 125.50)
            $table->json('annotation_data'); // Datos del dibujo en JSON
            $table->enum('annotation_type', ['arrow', 'circle', 'line', 'text', 'rectangle', 'free_draw', 'canvas']);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            // Índices para optimizar búsquedas
            $table->index(['video_id', 'timestamp']);
            $table->index(['video_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_annotations');
    }
};
