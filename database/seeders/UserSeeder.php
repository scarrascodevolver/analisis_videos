<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Crear usuario Analista
        $analista = User::create([
            'name' => 'Jeremias Rodriguez',
            'email' => 'analista@rugby.com',
            'password' => Hash::make('password123'),
            'phone' => '+56912345678',
            'role' => 'analista',
        ]);

        UserProfile::create([
            'user_id' => $analista->id,
            'position' => 'Analista Senior',
            'club_team_organization' => 'Los Troncos Rugby Club',
            'division_category' => 'Primera División',
        ]);

        // Crear usuario Jugador
        $jugador = User::create([
            'name' => 'Carlos Rodríguez',
            'email' => 'jugador@rugby.com',
            'password' => Hash::make('password123'),
            'phone' => '+56987654321',
            'role' => 'jugador',
        ]);

        UserProfile::create([
            'user_id' => $jugador->id,
            'position' => 'Primera Línea',
            'club_team_organization' => 'Los Troncos Rugby Club',
            'division_category' => 'Primera División',
        ]);

        // Crear usuario Entrenador
        $entrenador = User::create([
            'name' => 'Thor',
            'email' => 'entrenador@rugby.com',
            'password' => Hash::make('password123'),
            'phone' => '+56911223344',
            'role' => 'entrenador',
        ]);

        UserProfile::create([
            'user_id' => $entrenador->id,
            'position' => 'Entrenador Principal',
            'club_team_organization' => 'Los Troncos Rugby Club',
            'division_category' => 'Primera División',
        ]);

        // Crear usuario Director Técnico
        $dt = User::create([
            'name' => 'Valentin Dapena',
            'email' => 'dt@rugby.com',
            'password' => Hash::make('password123'),
            'phone' => '+56955667788',
            'role' => 'director_tecnico',
        ]);

        UserProfile::create([
            'user_id' => $dt->id,
            'position' => 'Director Técnico',
            'club_team_organization' => 'Los Troncos Rugby Club',
            'division_category' => 'Primera División',
        ]);

        // Crear usuario Scout
        $scout = User::create([
            'name' => 'Fernando Martínez',
            'email' => 'scout@rugby.com',
            'password' => Hash::make('password123'),
            'phone' => '+56999887766',
            'role' => 'scout',
        ]);

        UserProfile::create([
            'user_id' => $scout->id,
            'position' => 'Scout',
            'club_team_organization' => 'Los Troncos Rugby Club',
            'division_category' => 'Primera División',
        ]);

        // Crear usuario Aficionado
        $aficionado = User::create([
            'name' => 'María López',
            'email' => 'aficionado@rugby.com',
            'password' => Hash::make('password123'),
            'phone' => '+56933445566',
            'role' => 'aficionado',
        ]);

        UserProfile::create([
            'user_id' => $aficionado->id,
            'position' => null,
            'club_team_organization' => 'Los Troncos Rugby Club',
            'division_category' => null,
        ]);
    }
}