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
        // Paso 1: Eliminar foreign keys temporalmente
        DB::statement('ALTER TABLE player_evaluations DROP FOREIGN KEY player_evaluations_evaluator_id_foreign');
        DB::statement('ALTER TABLE player_evaluations DROP FOREIGN KEY player_evaluations_evaluated_player_id_foreign');

        // Paso 2: Eliminar constraint UNIQUE antiguo
        DB::statement('ALTER TABLE player_evaluations DROP INDEX player_evaluations_evaluator_id_evaluated_player_id_unique');

        // Paso 3: Re-crear foreign keys
        DB::statement('ALTER TABLE player_evaluations ADD CONSTRAINT player_evaluations_evaluator_id_foreign FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE player_evaluations ADD CONSTRAINT player_evaluations_evaluated_player_id_foreign FOREIGN KEY (evaluated_player_id) REFERENCES users(id) ON DELETE CASCADE');

        // Paso 4: Agregar campo evaluation_period_id
        Schema::table('player_evaluations', function (Blueprint $table) {
            $table->foreignId('evaluation_period_id')
                ->nullable()
                ->after('evaluated_player_id')
                ->constrained('evaluation_periods')
                ->onDelete('cascade');
        });

        // Paso 5: Asignar todas las evaluaciones existentes al período inicial (ID 1)
        DB::table('player_evaluations')
            ->whereNull('evaluation_period_id')
            ->update(['evaluation_period_id' => 1]);

        // Paso 6: Hacer el campo NOT NULL
        DB::statement('ALTER TABLE player_evaluations MODIFY COLUMN evaluation_period_id BIGINT UNSIGNED NOT NULL');

        // Paso 7: Agregar nuevo constraint UNIQUE con period_id
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
