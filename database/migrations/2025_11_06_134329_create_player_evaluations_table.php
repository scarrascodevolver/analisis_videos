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
        Schema::create('player_evaluations', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('evaluated_player_id')->constrained('users')->onDelete('cascade');

            // Acondicionamiento Físico (0-10)
            $table->tinyInteger('resistencia')->unsigned()->default(0);
            $table->tinyInteger('velocidad')->unsigned()->default(0);
            $table->tinyInteger('musculatura')->unsigned()->default(0);

            // Destrezas Básicas (0-10)
            $table->tinyInteger('recepcion_pelota')->unsigned()->default(0);
            $table->tinyInteger('pase_dos_lados')->unsigned()->default(0);
            $table->tinyInteger('juego_aereo')->unsigned()->default(0);
            $table->tinyInteger('tackle')->unsigned()->default(0);
            $table->tinyInteger('ruck')->unsigned()->default(0);
            $table->tinyInteger('duelos')->unsigned()->default(0);
            $table->tinyInteger('carreras')->unsigned()->default(0);
            $table->tinyInteger('conocimiento_plan')->unsigned()->default(0);
            $table->tinyInteger('entendimiento_juego')->unsigned()->default(0);
            $table->tinyInteger('reglamento')->unsigned()->default(0);

            // Destrezas Mentales (0-10)
            $table->tinyInteger('autocontrol')->unsigned()->default(0);
            $table->tinyInteger('concentracion')->unsigned()->default(0);
            $table->tinyInteger('toma_decisiones')->unsigned()->default(0);
            $table->tinyInteger('liderazgo')->unsigned()->default(0);

            // Otros Aspectos (0-10)
            $table->tinyInteger('disciplina')->unsigned()->default(0);
            $table->tinyInteger('compromiso')->unsigned()->default(0);
            $table->tinyInteger('puntualidad')->unsigned()->default(0);
            $table->tinyInteger('actitud_positiva')->unsigned()->default(0);
            $table->tinyInteger('actitud_negativa')->unsigned()->default(0);
            $table->tinyInteger('comunicacion')->unsigned()->default(0);

            // Habilidades específicas Forwards (0-10, nullable)
            $table->tinyInteger('scrum_tecnica')->unsigned()->nullable();
            $table->tinyInteger('scrum_empuje')->unsigned()->nullable();
            $table->tinyInteger('line_levantar')->unsigned()->nullable();
            $table->tinyInteger('line_saltar')->unsigned()->nullable();
            $table->tinyInteger('line_lanzamiento')->unsigned()->nullable();

            // Habilidades específicas Backs (0-10, nullable)
            $table->tinyInteger('kick_salidas')->unsigned()->nullable();
            $table->tinyInteger('kick_aire')->unsigned()->nullable();
            $table->tinyInteger('kick_rastron')->unsigned()->nullable();
            $table->tinyInteger('kick_palos')->unsigned()->nullable();
            $table->tinyInteger('kick_drop')->unsigned()->nullable();

            // Puntaje calculado
            $table->decimal('total_score', 5, 2)->default(0);

            $table->timestamps();

            // Constraint: Un jugador solo puede evaluar a otro una vez
            $table->unique(['evaluator_id', 'evaluated_player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_evaluations');
    }
};
