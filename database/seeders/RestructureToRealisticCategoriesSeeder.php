<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestructureToRealisticCategoriesSeeder extends Seeder
{
    public function run()
    {
        echo "🏉 REESTRUCTURACIÓN COMPLETA A CATEGORÍAS REALISTAS\n";
        echo "====================================================\n\n";

        // Desactivar foreign key checks temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Paso 1: Migrar videos a nueva estructura
            echo "📺 PASO 1: Migrando videos existentes...\n";
            $this->call(MigrateVideosToNewCategoriesSeeder::class);

            // Paso 2: Reestructurar categorías y usuarios
            echo "👥 PASO 2: Reestructurando categorías y usuarios...\n";
            $this->call(UpdateCategoriesRealisticSeeder::class);

            echo "\n✅ REESTRUCTURACIÓN COMPLETADA EXITOSAMENTE\n";
            echo "===========================================\n";
            echo "📊 Nueva estructura final:\n";
            echo "   🔹 ID 1: Juveniles (Juan Cruz Fleitas)\n";
            echo "   🔹 ID 2: Adultas (Valentín Dapena + Víctor Escobar)\n";
            echo "   🔹 ID 3: Femenino\n\n";
            echo "👨‍🏫 Entrenadores:\n";
            echo "   - Juan Cruz → Solo ve videos de Juveniles\n";
            echo "   - Valentín + Víctor → Ven todos los videos de Adultas\n";
            echo "     (incluyendo lo que antes era Primera + Intermedia)\n\n";

        } catch (\Exception $e) {
            echo "❌ ERROR durante la reestructuración: " . $e->getMessage() . "\n";
            throw $e;
        } finally {
            // Reactivar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        echo "🎯 Sistema listo para usar con estructura realista\n";
    }
}