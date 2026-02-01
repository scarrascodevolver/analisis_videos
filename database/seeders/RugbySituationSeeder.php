<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RugbySituationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $situations = [
            // 1. Jugadas Fijas
            ['name' => 'Scrum (Melé)', 'category' => 'Jugadas Fijas', 'color' => '#dc3545', 'sort_order' => 10],
            ['name' => 'Line Out (Saque lateral)', 'category' => 'Jugadas Fijas', 'color' => '#dc3545', 'sort_order' => 20],
            ['name' => 'Reinicio (Saque de 22, drop out)', 'category' => 'Jugadas Fijas', 'color' => '#dc3545', 'sort_order' => 30],

            // 2. Juego Dinámico
            ['name' => 'Ruck (Agrupamiento)', 'category' => 'Juego Dinámico', 'color' => '#fd7e14', 'sort_order' => 40],
            ['name' => 'Maul (Maul)', 'category' => 'Juego Dinámico', 'color' => '#fd7e14', 'sort_order' => 50],
            ['name' => 'Tackle (Placaje)', 'category' => 'Juego Dinámico', 'color' => '#fd7e14', 'sort_order' => 60],
            ['name' => 'Breakdown (Disputa de pelota)', 'category' => 'Juego Dinámico', 'color' => '#fd7e14', 'sort_order' => 70],

            // 3. Fase de Ataque
            ['name' => 'Ataque estructurado (1-3 fases)', 'category' => 'Fase de Ataque', 'color' => '#28a745', 'sort_order' => 80],
            ['name' => 'Ataque prolongado (4+ fases)', 'category' => 'Fase de Ataque', 'color' => '#28a745', 'sort_order' => 90],
            ['name' => 'Penetración individual', 'category' => 'Fase de Ataque', 'color' => '#28a745', 'sort_order' => 100],
            ['name' => 'Juego de backs', 'category' => 'Fase de Ataque', 'color' => '#28a745', 'sort_order' => 110],
            ['name' => 'Contraataque', 'category' => 'Fase de Ataque', 'color' => '#28a745', 'sort_order' => 120],

            // 4. Fase Defensiva
            ['name' => 'Línea defensiva', 'category' => 'Fase Defensiva', 'color' => '#007bff', 'sort_order' => 130],
            ['name' => 'Defensa de canal', 'category' => 'Fase Defensiva', 'color' => '#007bff', 'sort_order' => 140],
            ['name' => 'Defensa de patadas', 'category' => 'Fase Defensiva', 'color' => '#007bff', 'sort_order' => 150],
            ['name' => 'Presión defensiva', 'category' => 'Fase Defensiva', 'color' => '#007bff', 'sort_order' => 160],

            // 5. Juego Aéreo
            ['name' => 'Patadas tácticas', 'category' => 'Juego Aéreo', 'color' => '#6f42c1', 'sort_order' => 170],
            ['name' => 'Box kick', 'category' => 'Juego Aéreo', 'color' => '#6f42c1', 'sort_order' => 180],
            ['name' => 'Patada de despeje', 'category' => 'Juego Aéreo', 'color' => '#6f42c1', 'sort_order' => 190],
            ['name' => 'Drop goal', 'category' => 'Juego Aéreo', 'color' => '#6f42c1', 'sort_order' => 200],

            // 6. Situaciones Especiales
            ['name' => 'Zona roja (cerca del in-goal)', 'category' => 'Situaciones Especiales', 'color' => '#e83e8c', 'sort_order' => 210],
            ['name' => 'Penales', 'category' => 'Situaciones Especiales', 'color' => '#e83e8c', 'sort_order' => 220],
            ['name' => 'Ventaja', 'category' => 'Situaciones Especiales', 'color' => '#e83e8c', 'sort_order' => 230],
            ['name' => 'Superioridad numérica', 'category' => 'Situaciones Especiales', 'color' => '#e83e8c', 'sort_order' => 240],

            // 7. Habilidades Individuales
            ['name' => 'Manejo de pelota', 'category' => 'Habilidades Individuales', 'color' => '#20c997', 'sort_order' => 250],
            ['name' => 'Pase', 'category' => 'Habilidades Individuales', 'color' => '#20c997', 'sort_order' => 260],
            ['name' => 'Decisiones', 'category' => 'Habilidades Individuales', 'color' => '#20c997', 'sort_order' => 270],
            ['name' => 'Comunicación', 'category' => 'Habilidades Individuales', 'color' => '#20c997', 'sort_order' => 280],
        ];

        foreach ($situations as $situation) {
            \App\Models\RugbySituation::create($situation);
        }
    }
}
