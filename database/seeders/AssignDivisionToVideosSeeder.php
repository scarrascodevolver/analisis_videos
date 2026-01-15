<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Video;

class AssignDivisionToVideosSeeder extends Seeder
{
    public function run()
    {
        echo "📹 Asignando divisiones a videos existentes...\n";

        // Para videos de la categoría "Adultas" (ID: 2), necesitamos determinar
        // cuáles eran "Primera" y cuáles "Intermedia" antes de la reestructuración

        // Basándome en los nombres de los equipos rivales en los videos existentes:
        $videos = Video::where('category_id', 2)->get(); // Categoría Adultas

        foreach ($videos as $video) {
            $division = $this->determineDivisionFromVideoData($video);

            $video->update(['division' => $division]);
            echo "✅ '{$video->title}' → División: {$division}\n";
        }

        // Videos de otras categorías (Juveniles, Femenino) son "unica"
        $otherVideos = Video::whereIn('category_id', [1, 3])->get(); // Juveniles y Femenino

        foreach ($otherVideos as $video) {
            $video->update(['division' => 'unica']);
            echo "✅ '{$video->title}' → División: unica\n";
        }

        echo "\n🎯 Divisiones asignadas correctamente a todos los videos\n";
    }

    private function determineDivisionFromVideoData($video)
    {
        // Lógica para determinar división basándose en datos del video
        // Puedes ajustar esta lógica según patrones específicos de tu club

        $title = $video->title;
        $rivalTeam = $video->rival_team_name ?? '';

        // Algunos equipos típicos de Primera División
        $primeraTeams = ['Old Locks', 'Los Troncos', 'Stade Français'];

        // Algunos equipos típicos de Intermedia
        $intermediaTeams = ['All Brads A', 'Lagartos RC'];

        // Verificar por equipo rival
        foreach ($primeraTeams as $team) {
            if (stripos($rivalTeam, $team) !== false || stripos($title, $team) !== false) {
                return 'primera';
            }
        }

        foreach ($intermediaTeams as $team) {
            if (stripos($rivalTeam, $team) !== false || stripos($title, $team) !== false) {
                return 'intermedia';
            }
        }

        // Por defecto, si no podemos determinar, asignar "primera"
        // (puedes cambiar esto según tu preferencia)
        return 'primera';
    }
}