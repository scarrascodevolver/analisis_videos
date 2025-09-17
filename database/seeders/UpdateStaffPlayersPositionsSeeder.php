<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateStaffPlayersPositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n🏉 Actualizando posiciones de staff-jugadores...\n\n";

        // Actualizar Víctor Escobar → Segunda línea y Pilar
        $victor = User::where('email', 'victor@clublostroncos.cl')->first();
        if ($victor && $victor->profile) {
            $victor->profile->update([
                'position' => 'Segunda Línea',
                'secondary_position' => 'Pilar'
            ]);
            echo "✅ Víctor Escobar → Segunda Línea (principal) / Pilar (secundaria)\n";
        } else {
            echo "❌ Víctor no encontrado o sin perfil\n";
        }

        // Actualizar Valentín Dapena → Apertura (10) y Wing
        $valentin = User::where('email', 'valentin@clublostroncos.cl')->first();
        if ($valentin && $valentin->profile) {
            $valentin->profile->update([
                'position' => 'Apertura',
                'secondary_position' => 'Wing'
            ]);
            echo "✅ Valentín Dapena → Apertura (principal) / Wing (secundaria)\n";
        } else {
            echo "❌ Valentín no encontrado o sin perfil\n";
        }

        echo "\n🎉 Posiciones de staff-jugadores actualizadas\n";
        echo "Ahora aparecerán como jugadores con posiciones reales de rugby\n\n";
    }
}