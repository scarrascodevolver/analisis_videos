<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TeamSeeder::class,
            CategorySeeder::class,
            RugbySituationSeeder::class,
            PlayersSeeder::class,
        ]);

        // Create sample users with different roles
        $analyst = User::create([
            'name' => 'Analista Principal',
            'email' => 'analista@lostroncos.cl',
            'password' => bcrypt('password'),
            'phone' => '+56912345678',
            'role' => 'analista',
        ]);

        $coach = User::create([
            'name' => 'Entrenador Principal',
            'email' => 'entrenador@lostroncos.cl',
            'password' => bcrypt('password'),
            'phone' => '+56987654321',
            'role' => 'entrenador',
        ]);

        $player = User::create([
            'name' => 'Jugador Ejemplo',
            'email' => 'jugador@lostroncos.cl',
            'password' => bcrypt('password'),
            'phone' => '+56911111111',
            'role' => 'jugador',
        ]);

        $analyst2 = User::create([
            'name' => 'Analista Segundo',
            'email' => 'analista2@lostroncos.cl',
            'password' => bcrypt('password'),
            'phone' => '+56922222222',
            'role' => 'analista',
        ]);

        $player2 = User::create([
            'name' => 'Jugador Segundo',
            'email' => 'jugador2@lostroncos.cl',
            'password' => bcrypt('password'),
            'phone' => '+56933333333',
            'role' => 'jugador',
        ]);

        // Create user profiles
        \App\Models\UserProfile::create([
            'user_id' => $analyst->id,
            'position' => null,
            'club_team_organization' => 'Los Troncos RC',
            'division_category' => 'Primera División',
        ]);

        \App\Models\UserProfile::create([
            'user_id' => $coach->id,
            'position' => null,
            'club_team_organization' => 'Los Troncos RC',
            'division_category' => 'Primera División',
        ]);

        \App\Models\UserProfile::create([
            'user_id' => $player->id,
            'position' => 'Centro',
            'club_team_organization' => 'Los Troncos RC',
            'division_category' => 'Primera División',
        ]);

        \App\Models\UserProfile::create([
            'user_id' => $analyst2->id,
            'position' => null,
            'club_team_organization' => 'Los Troncos RC',
            'division_category' => 'Primera División',
        ]);

        \App\Models\UserProfile::create([
            'user_id' => $player2->id,
            'position' => 'Ala',
            'club_team_organization' => 'Los Troncos RC',
            'division_category' => 'Primera División',
        ]);
    }
}
