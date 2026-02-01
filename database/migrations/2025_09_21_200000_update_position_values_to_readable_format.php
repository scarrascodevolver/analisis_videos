<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mapeo de valores antiguos a nuevos
        $positionMap = [
            // Forwards
            'pilar_izquierdo' => 'Pilar Izquierdo',
            'hooker' => 'Hooker',
            'pilar_derecho' => 'Pilar Derecho',
            'segunda_linea' => 'Segunda Línea',
            'ala_izquierdo' => 'Ala',
            'ala_derecho' => 'Ala',
            'octavo' => 'Octavo',

            // Backs
            'medio_scrum' => 'Medio Scrum',
            'apertura' => 'Apertura',
            'ala_izquierdo_back' => 'Wing',
            'centro_interno' => 'Centro',
            'centro_externo' => 'Centro',
            'ala_derecho_back' => 'Wing',
            'fullback' => 'Fullback',
        ];

        // Actualizar position
        foreach ($positionMap as $oldValue => $newValue) {
            DB::table('user_profiles')
                ->where('position', $oldValue)
                ->update(['position' => $newValue]);
        }

        // Actualizar secondary_position
        foreach ($positionMap as $oldValue => $newValue) {
            DB::table('user_profiles')
                ->where('secondary_position', $oldValue)
                ->update(['secondary_position' => $newValue]);
        }

        // Log de la operación
        \Log::info('Position values updated to readable format', [
            'mappings_applied' => count($positionMap),
            'timestamp' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mapeo inverso para rollback
        $reverseMap = [
            'Pilar Izquierdo' => 'pilar_izquierdo',
            'Hooker' => 'hooker',
            'Pilar Derecho' => 'pilar_derecho',
            'Segunda Línea' => 'segunda_linea',
            'Ala' => 'ala_izquierdo', // Nota: se pierde diferencia izq/der
            'Octavo' => 'octavo',
            'Medio Scrum' => 'medio_scrum',
            'Apertura' => 'apertura',
            'Wing' => 'ala_izquierdo_back', // Nota: se pierde diferencia izq/der
            'Centro' => 'centro_interno', // Nota: se pierde diferencia int/ext
            'Fullback' => 'fullback',
        ];

        // Revertir position
        foreach ($reverseMap as $newValue => $oldValue) {
            DB::table('user_profiles')
                ->where('position', $newValue)
                ->update(['position' => $oldValue]);
        }

        // Revertir secondary_position
        foreach ($reverseMap as $newValue => $oldValue) {
            DB::table('user_profiles')
                ->where('secondary_position', $newValue)
                ->update(['secondary_position' => $oldValue]);
        }
    }
};
