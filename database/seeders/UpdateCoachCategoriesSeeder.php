<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UpdateCoachCategoriesSeeder extends Seeder
{
    public function run()
    {
        // Actualizar Juan Cruz Fleitas → Juveniles (ID: 3)
        $juanCruz = User::where('email', 'juancruz@clublostroncos.cl')->first();
        if ($juanCruz && $juanCruz->profile) {
            $juanCruz->profile->update(['user_category_id' => 3]);
            echo "✅ Juan Cruz actualizado → Juveniles (ID: 3)\n";
        } else {
            echo "❌ Juan Cruz no encontrado o sin perfil\n";
        }

        // Actualizar Valentín Dapena → Adulta Primera (ID: 1)
        $valentin = User::where('email', 'valentin@clublostroncos.cl')->first();
        if ($valentin && $valentin->profile) {
            $valentin->profile->update(['user_category_id' => 1]);
            echo "✅ Valentín actualizado → Adulta Primera (ID: 1)\n";
        } else {
            echo "❌ Valentín no encontrado o sin perfil\n";
        }

        // Actualizar Víctor Escobar → Adulta Intermedia (ID: 2)
        $victor = User::where('email', 'victor@clublostroncos.cl')->first();
        if ($victor && $victor->profile) {
            $victor->profile->update(['user_category_id' => 2]);
            echo "✅ Víctor actualizado → Adulta Intermedia (ID: 2)\n";
        } else {
            echo "❌ Víctor no encontrado o sin perfil\n";
        }

        echo "\n🎯 Categorías de entrenadores actualizadas correctamente\n";
    }
}
