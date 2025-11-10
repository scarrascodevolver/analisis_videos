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
        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "Evaluación Enero 2025"
            $table->text('description')->nullable(); // Descripción opcional
            $table->timestamp('started_at'); // Fecha de inicio
            $table->timestamp('ended_at')->nullable(); // Fecha de fin (null = activo)
            $table->boolean('is_active')->default(false); // Solo un período puede estar activo
            $table->timestamps();
        });

        // Crear período inicial para evaluaciones existentes
        DB::table('evaluation_periods')->insert([
            'name' => 'Período Inicial - Noviembre 2025',
            'description' => 'Período creado automáticamente para evaluaciones existentes',
            'started_at' => now(),
            'ended_at' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_periods');
    }
};
