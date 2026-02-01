<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoAssignment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VideoAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        // Get users by role
        $analysts = User::where('role', 'analista')->get();
        $players = User::where('role', 'jugador')->get();
        $coaches = User::where('role', 'entrenador')->get();
        $videos = Video::all();

        if ($analysts->isEmpty() || $players->isEmpty() || $videos->isEmpty()) {
            $this->command->info('No hay suficientes usuarios o videos para crear asignaciones');

            return;
        }

        $assignments = [];

        // Create assignments from analysts to players
        foreach ($players as $player) {
            $videosToAssign = $videos->count() > 1 ? $videos->random(min(2, $videos->count())) : $videos;
            if (! is_iterable($videosToAssign)) {
                $videosToAssign = [$videosToAssign];
            }

            foreach ($videosToAssign as $video) {
                $analyst = $analysts->random();

                $assignments[] = [
                    'video_id' => $video->id,
                    'assigned_to' => $player->id,
                    'assigned_by' => $analyst->id,
                    'status' => collect(['assigned', 'completed', 'in_progress'])->random(),
                    'due_date' => rand(0, 1) ? Carbon::now()->addDays(rand(1, 14)) : null,
                    'notes' => collect([
                        'Analiza los aspectos defensivos del equipo rival',
                        'Enfócate en las jugadas de lineout',
                        'Revisa las formaciones de scrum',
                        'Observa las jugadas de ataque en los últimos 15 minutos',
                        'Analiza la estrategia de patadas del equipo rival',
                    ])->random(),
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ];
            }
        }

        // Create assignments from coaches to players
        if (! $coaches->isEmpty()) {
            foreach ($players->take(1) as $player) {
                $video = $videos->first();
                $coach = $coaches->random();

                $assignments[] = [
                    'video_id' => $video->id,
                    'assigned_to' => $player->id,
                    'assigned_by' => $coach->id,
                    'status' => collect(['assigned', 'in_progress'])->random(),
                    'due_date' => Carbon::now()->addDays(rand(3, 10)),
                    'notes' => collect([
                        'Preparación para el próximo partido',
                        'Estudia las jugadas especiales del rival',
                        'Enfócate en tu posición específica',
                        'Analiza las oportunidades de mejora',
                    ])->random(),
                    'created_at' => Carbon::now()->subDays(rand(1, 7)),
                    'updated_at' => now(),
                ];
            }
        }

        // Add some overdue assignments
        foreach ($players->take(1) as $player) {
            $video = $videos->first();
            $analyst = $analysts->random();

            $assignments[] = [
                'video_id' => $video->id,
                'assigned_to' => $player->id,
                'assigned_by' => $analyst->id,
                'status' => 'assigned',
                'due_date' => Carbon::now()->subDays(rand(1, 5)), // Overdue
                'notes' => 'Video urgente para análisis pre-partido',
                'created_at' => Carbon::now()->subDays(rand(7, 14)),
                'updated_at' => now(),
            ];
        }

        VideoAssignment::insert($assignments);

        $this->command->info('Se crearon '.count($assignments).' asignaciones de video');
    }
}
