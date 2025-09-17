<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class EnableAssignmentsForStaffPlayersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n🎯 Activando asignaciones para staff-jugadores...\n\n";

        // Activar para Valentín Dapena
        $valentin = User::where('email', 'valentin@clublostroncos.cl')->first();
        if ($valentin && $valentin->profile) {
            $valentin->profile->update(['can_receive_assignments' => true]);
            echo "✅ Valentín Dapena → Puede recibir asignaciones\n";
        } else {
            echo "❌ Valentín no encontrado o sin perfil\n";
        }

        // Activar para Víctor Escobar
        $victor = User::where('email', 'victor@clublostroncos.cl')->first();
        if ($victor && $victor->profile) {
            $victor->profile->update(['can_receive_assignments' => true]);
            echo "✅ Víctor Escobar → Puede recibir asignaciones\n";
        } else {
            echo "❌ Víctor no encontrado o sin perfil\n";
        }

        echo "\n🎉 Staff-jugadores configurados correctamente\n";
        echo "Ahora Víctor y Valentín aparecerán en las opciones de asignación\n\n";
    }
}