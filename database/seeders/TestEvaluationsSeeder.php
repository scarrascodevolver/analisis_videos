<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PlayerEvaluation;

class TestEvaluationsSeeder extends Seeder
{
    /**
     * Generar evaluaciones de prueba con datos realistas
     * Solo para jugadores de categoría "Adulta Primera"
     */
    public function run(): void
    {
        // Buscar ID de categoría "Adulta Primera"
        $categoriaAdultaPrimera = \App\Models\Category::where('name', 'Adulta Primera')->first();

        if (!$categoriaAdultaPrimera) {
            $this->command->error('No se encontró la categoría "Adulta Primera".');
            return;
        }

        // Obtener solo jugadores de categoría Adulta Primera
        $jugadores = User::where('role', 'jugador')
            ->whereHas('profile', function($q) use ($categoriaAdultaPrimera) {
                $q->where('user_category_id', $categoriaAdultaPrimera->id);
            })
            ->with('profile')
            ->get();

        if ($jugadores->count() < 2) {
            $this->command->error('Se necesitan al menos 2 jugadores en categoría Adulta Primera para generar evaluaciones.');
            return;
        }

        $this->command->info("Generando evaluaciones de prueba entre {$jugadores->count()} jugadores de Adulta Primera...");

        $evaluacionesCreadas = 0;

        // Cada jugador evalúa a los demás
        foreach ($jugadores as $evaluador) {
            foreach ($jugadores as $evaluado) {
                // No se puede evaluar a sí mismo
                if ($evaluador->id === $evaluado->id) {
                    continue;
                }

                // Determinar si es Forward o Back
                $posicionEvaluado = $evaluado->profile->position ?? '';
                $esForward = in_array($posicionEvaluado, [
                    'Pilar Izquierdo',
                    'Hooker',
                    'Pilar Derecho',
                    'Segunda Línea',
                    'Ala',
                    'Número 8'
                ]);

                // Generar valores base con algo de variación
                // Algunas evaluaciones serán mejores que otras
                $nivelBase = rand(1, 3); // 1=bajo (5-6), 2=medio (6-8), 3=alto (7-10)

                $data = [
                    'evaluator_id' => $evaluador->id,
                    'evaluated_player_id' => $evaluado->id,

                    // Acondicionamiento Físico
                    'resistencia' => $this->getScore($nivelBase),
                    'velocidad' => $this->getScore($nivelBase),
                    'musculatura' => $this->getScore($nivelBase),

                    // Destrezas Básicas
                    'recepcion_pelota' => $this->getScore($nivelBase),
                    'pase_dos_lados' => $this->getScore($nivelBase),
                    'juego_aereo' => $this->getScore($nivelBase),
                    'tackle' => $this->getScore($nivelBase),
                    'ruck' => $this->getScore($nivelBase),
                    'duelos' => $this->getScore($nivelBase),
                    'carreras' => $this->getScore($nivelBase),
                    'conocimiento_plan' => $this->getScore($nivelBase),
                    'entendimiento_juego' => $this->getScore($nivelBase),
                    'reglamento' => $this->getScore($nivelBase),

                    // Destrezas Mentales
                    'autocontrol' => $this->getScore($nivelBase),
                    'concentracion' => $this->getScore($nivelBase),
                    'toma_decisiones' => $this->getScore($nivelBase),
                    'liderazgo' => $this->getScore($nivelBase),

                    // Otros Aspectos
                    'disciplina' => $this->getScore($nivelBase),
                    'compromiso' => $this->getScore($nivelBase),
                    'puntualidad' => $this->getScore($nivelBase),
                    'actitud_positiva' => $this->getScore($nivelBase),
                    'actitud_negativa' => rand(0, 3), // Valores bajos (negativos)
                    'comunicacion' => $this->getScore($nivelBase),
                ];

                // Agregar habilidades específicas según posición
                if ($esForward) {
                    $data['scrum_tecnica'] = $this->getScore($nivelBase);
                    $data['scrum_empuje'] = $this->getScore($nivelBase);
                    $data['line_levantar'] = $this->getScore($nivelBase);
                    $data['line_saltar'] = $this->getScore($nivelBase);
                    $data['line_lanzamiento'] = $this->getScore($nivelBase);

                    // Backs en null para Forwards
                    $data['kick_salidas'] = null;
                    $data['kick_aire'] = null;
                    $data['kick_rastron'] = null;
                    $data['kick_palos'] = null;
                    $data['kick_drop'] = null;
                } else {
                    // Es Back
                    $data['kick_salidas'] = $this->getScore($nivelBase);
                    $data['kick_aire'] = $this->getScore($nivelBase);
                    $data['kick_rastron'] = $this->getScore($nivelBase);
                    $data['kick_palos'] = $this->getScore($nivelBase);
                    $data['kick_drop'] = $this->getScore($nivelBase);

                    // Forwards en null para Backs
                    $data['scrum_tecnica'] = null;
                    $data['scrum_empuje'] = null;
                    $data['line_levantar'] = null;
                    $data['line_saltar'] = null;
                    $data['line_lanzamiento'] = null;
                }

                PlayerEvaluation::create($data);
                $evaluacionesCreadas++;

                $this->command->info("✓ {$evaluador->name} evaluó a {$evaluado->name} (Nivel: {$this->getNivelTexto($nivelBase)})");
            }
        }

        $this->command->info("\n🎉 Se crearon {$evaluacionesCreadas} evaluaciones de prueba exitosamente.");
    }

    /**
     * Obtener puntaje según nivel base
     */
    private function getScore($nivel): int
    {
        return match($nivel) {
            1 => rand(4, 6),  // Bajo
            2 => rand(6, 8),  // Medio
            3 => rand(7, 10), // Alto
            default => rand(6, 8),
        };
    }

    /**
     * Obtener texto descriptivo del nivel
     */
    private function getNivelTexto($nivel): string
    {
        return match($nivel) {
            1 => 'Bajo',
            2 => 'Medio',
            3 => 'Alto',
            default => 'Medio',
        };
    }
}
