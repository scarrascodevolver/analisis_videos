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
        // Paso 1: Agregar campo evaluation_period_id sin constraint
        Schema::table('player_evaluations', function (Blueprint $table) {
            $table->foreignId('evaluation_period_id')
                  ->nullable()
                  ->after('evaluated_player_id')
                  ->constrained('evaluation_periods')
                  ->onDelete('cascade');
        });

        // Paso 2: Asignar todas las evaluaciones existentes al período inicial (ID 1)
        DB::table('player_evaluations')
            ->whereNull('evaluation_period_id')
            ->update(['evaluation_period_id' => 1]);

        // Paso 3: Hacer el campo NOT NULL
        DB::statement('ALTER TABLE player_evaluations MODIFY COLUMN evaluation_period_id BIGINT UNSIGNED NOT NULL');

        // Paso 4: Eliminar constraint UNIQUE antiguo usando SQL directo
        DB::statement('ALTER TABLE player_evaluations DROP INDEX player_evaluations_evaluator_id_evaluated_player_id_unique');

        // Paso 5: Agregar nuevo constraint UNIQUE con period_id
        Schema::table('player_evaluations', function (Blueprint $table) {
            $table->unique(['evaluator_id', 'evaluated_player_id', 'evaluation_period_id'], 'unique_evaluation_per_period');
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
