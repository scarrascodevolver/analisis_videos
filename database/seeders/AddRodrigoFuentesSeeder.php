<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class AddRodrigoFuentesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Obtener categoría Juveniles
        $juvenilesCategory = Category::where('name', 'Juveniles')->first();

        if (!$juvenilesCategory) {
            $this->command->error('Categoría Juveniles no encontrada. Ejecutar CategorySeeder primero.');
            return;
        }

        // Buscar o crear usuario Rodrigo Fuentes
        $user = User::firstOrCreate(
            ['email' => 'rodrigo@clublostroncos.cl'],
            [
                'name' => 'Rodrigo Fuentes',
                'email_verified_at' => now(),
                'password' => Hash::make('rodrigo2025'),
                'role' => 'entrenador',
                'phone' => '+56 9 8765 4321',
            ]
        );

        // Crear o actualizar perfil de entrenador
        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'coaching_experience' => 8, // 8 años de experiencia
                'certifications' => 'Level 2 World Rugby Coaching, Certificación UAR Juveniles, Curso de Formación de Backs',
                'specializations' => ['Entrenamiento de Backs', 'Desarrollo Juvenil', 'Técnica Individual', 'Formación de Jugadores'],
                'club_team_organization' => 'Club Los Troncos',
                'division_category' => 'Juveniles',
                'user_category_id' => $juvenilesCategory->id,
                'goals' => 'Desarrollar el potencial individual de cada jugador juvenil, enfocándome en la técnica de backs y la formación integral como persona y deportista.',
                'can_receive_assignments' => true, // Puede recibir asignaciones como staff
            ]
        );

        $this->command->info('✅ Entrenador Rodrigo Fuentes creado exitosamente');
        $this->command->info('📧 Email: rodrigo@clublostroncos.cl');
        $this->command->info('🔑 Password: rodrigo2025');
        $this->command->info('🏈 Categoría: Juveniles');
        $this->command->info('⚡ Especialidad: Entrenamiento de Backs');
    }
}