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
        // Obtener organizaciones que no tienen un equipo propio
        $organizations = DB::table('organizations')->get();

        foreach ($organizations as $org) {
            // Verificar si ya tiene un equipo propio
            $hasOwnTeam = DB::table('teams')
                ->where('organization_id', $org->id)
                ->where('is_own_team', true)
                ->exists();

            if (! $hasOwnTeam) {
                // Crear equipo propio
                DB::table('teams')->insert([
                    'name' => $org->name,
                    'is_own_team' => true,
                    'organization_id' => $org->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No eliminamos los equipos creados
    }
};
