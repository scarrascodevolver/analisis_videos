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
        Schema::table('player_evaluations', function (Blueprint $table) {
            // Agregar campo evaluation_period_id
            $table->foreignId('evaluation_period_id')
                  ->nullable()
                  ->after('evaluated_player_id')
                  ->constrained('evaluation_periods')
                  ->onDelete('cascade');

            // Eliminar constraint UNIQUE antiguo
            $table->dropUnique(['evaluator_id', 'evaluated_player_id']);

            // Agregar nuevo constraint UNIQUE con period_id
            $table->unique(['evaluator_id', 'evaluated_player_id', 'evaluation_period_id'], 'unique_evaluation_per_period');
        });

        // Asignar todas las evaluaciones existentes al período inicial (ID 1)
        DB::table('player_evaluations')
            ->whereNull('evaluation_period_id')
            ->update(['evaluation_period_id' => 1]);

        // Hacer el campo NOT NULL después de migrar datos
        Schema::table('player_evaluations', function (Blueprint $table) {
            $table->foreignId('evaluation_period_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_evaluations', function (Blueprint $table) {
            // Eliminar constraint compuesto
            $table->dropUnique('unique_evaluation_per_period');

            // Restaurar constraint original
            $table->unique(['evaluator_id', 'evaluated_player_id']);

            // Eliminar columna
            $table->dropForeign(['evaluation_period_id']);
            $table->dropColumn('evaluation_period_id');
        });
    }
};
