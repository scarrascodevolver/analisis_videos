<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class UpdateCategoriesRealisticSeeder extends Seeder
{
    public function run()
    {
        echo "🏉 Iniciando reestructuración de categorías realista...\n";

        // 1. Recrear categorías con nueva estructura
        echo "📋 Limpiando categorías existentes...\n";
        DB::table('categories')->truncate();

        echo "📋 Creando nueva estructura de categorías...\n";
        $categories = [
            ['id' => 1, 'name' => 'Juveniles', 'description' => 'Categoría juvenil (Sub-18, Sub-16, etc.)'],
            ['id' => 2, 'name' => 'Adultas', 'description' => 'Categorías adultas (Primera + Intermedia)'],
            ['id' => 3, 'name' => 'Femenino', 'description' => 'Categoría femenina'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
            echo "✅ Categoría creada: {$category['name']}\n";
        }

        // 2. Actualizar asignaciones de entrenadores según nueva estructura
        echo "\n👨‍🏫 Actualizando asignaciones de entrenadores...\n";

        // Juan Cruz → Juveniles (ID: 1)
        $juanCruz = User::where('email', 'juancruz@clublostroncos.cl')->first();
        if ($juanCruz && $juanCruz->profile) {
            $juanCruz->profile->update(['user_category_id' => 1]);
            echo "✅ Juan Cruz Fleitas → Juveniles (ID: 1)\n";
        }

        // Valentín → Adultas (ID: 2)
        $valentin = User::where('email', 'valentin@clublostroncos.cl')->first();
        if ($valentin && $valentin->profile) {
            $valentin->profile->update(['user_category_id' => 2]);
            echo "✅ Valentín Dapena → Adultas (ID: 2)\n";
        }

        // Víctor → Adultas (ID: 2)
        $victor = User::where('email', 'victor@clublostroncos.cl')->first();
        if ($victor && $victor->profile) {
            $victor->profile->update(['user_category_id' => 2]);
            echo "✅ Víctor Escobar → Adultas (ID: 2)\n";
        }

        // 3. Actualizar jugadores existentes a nueva estructura
        echo "\n🏃 Actualizando jugadores a nueva estructura...\n";

        // Buscar jugadores con categorías adultas (1 y 2) y cambiarlos a "Adultas" (2)
        $adultPlayers = User::where('role', 'jugador')
            ->whereHas('profile', function($q) {
                $q->whereIn('user_category_id', [1, 2]); // Antigua Adulta Primera/Intermedia
            })->get();

        foreach ($adultPlayers as $player) {
            if ($player->profile) {
                $oldCategory = $player->profile->user_category_id == 1 ? 'Adulta Primera' : 'Adulta Intermedia';
                $player->profile->update(['user_category_id' => 2]); // Nueva "Adultas"
                echo "✅ {$player->name} ({$oldCategory}) → Adultas (ID: 2)\n";
            }
        }

        // Jugadores juveniles mantienen su categoría pero cambia el ID (de 3 a 1)
        $juvenilePlayers = User::where('role', 'jugador')
            ->whereHas('profile', function($q) {
                $q->where('user_category_id', 3); // Antigua Juveniles
            })->get();

        foreach ($juvenilePlayers as $player) {
            if ($player->profile) {
                $player->profile->update(['user_category_id' => 1]); // Nueva "Juveniles"
                echo "✅ {$player->name} (Juveniles) → Juveniles (ID: 1)\n";
            }
        }

        echo "\n🎯 Reestructuración completada exitosamente\n";
        echo "📊 Nueva estructura:\n";
        echo "   ID 1: Juveniles (Juan Cruz)\n";
        echo "   ID 2: Adultas (Valentín + Víctor)\n";
        echo "   ID 3: Femenino\n";
    }
}