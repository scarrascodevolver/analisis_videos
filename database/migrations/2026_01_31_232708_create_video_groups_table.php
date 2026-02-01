<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla para agrupar videos multi-cámara.
     * Permite que un video pertenezca a múltiples grupos.
     */
    public function up(): void
    {
        Schema::create('video_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('Nombre opcional del grupo (ej: "Partido vs Rival - Multi-cam")');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Índices
            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_groups');
    }
};
