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
        // 1. Crear la organización default "Club Los Troncos"
        $orgId = DB::table('organizations')->insertGetId([
            'name' => 'Club Los Troncos',
            'slug' => 'los-troncos',
            'logo_path' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Organización 'Club Los Troncos' creada con ID: {$orgId}\n";

        // 2. Asignar organization_id a todos los registros existentes

        // Videos
        $videosUpdated = DB::table('videos')
            ->whereNull('organization_id')
            ->update(['organization_id' => $orgId]);
        echo "📹 Videos actualizados: {$videosUpdated}\n";

        // Teams
        $teamsUpdated = DB::table('teams')
            ->whereNull('organization_id')
            ->update(['organization_id' => $orgId]);
        echo "🏉 Teams actualizados: {$teamsUpdated}\n";

        // Categories
        $categoriesUpdated = DB::table('categories')
            ->whereNull('organization_id')
            ->update(['organization_id' => $orgId]);
        echo "📂 Categories actualizadas: {$categoriesUpdated}\n";

        // Player Evaluations
        $evaluationsUpdated = DB::table('player_evaluations')
            ->whereNull('organization_id')
            ->update(['organization_id' => $orgId]);
        echo "📊 Player Evaluations actualizadas: {$evaluationsUpdated}\n";

        // Evaluation Periods
        $periodsUpdated = DB::table('evaluation_periods')
            ->whereNull('organization_id')
            ->update(['organization_id' => $orgId]);
        echo "📅 Evaluation Periods actualizados: {$periodsUpdated}\n";

        // Settings
        $settingsUpdated = DB::table('settings')
            ->whereNull('organization_id')
            ->update(['organization_id' => $orgId]);
        echo "⚙️ Settings actualizados: {$settingsUpdated}\n";

        // 3. Crear registros en organization_user para cada usuario existente
        $users = DB::table('users')->get();
        $usersCount = 0;

        foreach ($users as $user) {
            DB::table('organization_user')->insert([
                'organization_id' => $orgId,
                'user_id' => $user->id,
                'role' => $user->role, // Copiar el rol actual del usuario
                'is_current' => true,  // Esta es su organización activa
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $usersCount++;
        }

        echo "👥 Usuarios asignados a la organización: {$usersCount}\n";
        echo "\n🎉 Migración completada exitosamente!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Buscar la organización "Club Los Troncos"
        $org = DB::table('organizations')->where('slug', 'los-troncos')->first();

        if ($org) {
            // Eliminar registros de organization_user
            DB::table('organization_user')
                ->where('organization_id', $org->id)
                ->delete();

            // Poner organization_id en null en todas las tablas
            DB::table('videos')
                ->where('organization_id', $org->id)
                ->update(['organization_id' => null]);

            DB::table('teams')
                ->where('organization_id', $org->id)
                ->update(['organization_id' => null]);

            DB::table('categories')
                ->where('organization_id', $org->id)
                ->update(['organization_id' => null]);

            DB::table('player_evaluations')
                ->where('organization_id', $org->id)
                ->update(['organization_id' => null]);

            DB::table('evaluation_periods')
                ->where('organization_id', $org->id)
                ->update(['organization_id' => null]);

            DB::table('settings')
                ->where('organization_id', $org->id)
                ->update(['organization_id' => null]);

            // Eliminar la organización
            DB::table('organizations')->where('id', $org->id)->delete();

            echo "🔄 Rollback completado: organización 'Club Los Troncos' eliminada\n";
        }
    }
};
