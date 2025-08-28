<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            ['name' => 'Los Troncos', 'abbreviation' => 'LT', 'is_own_team' => true],
            ['name' => 'DOBS', 'abbreviation' => 'DOBS', 'is_own_team' => false],
            ['name' => 'All Brads A', 'abbreviation' => 'ABA', 'is_own_team' => false],
            ['name' => 'Old Georgians', 'abbreviation' => 'OG', 'is_own_team' => false],
            ['name' => 'Tabancura RC', 'abbreviation' => 'TRC', 'is_own_team' => false],
            ['name' => 'Old Locks', 'abbreviation' => 'OL', 'is_own_team' => false],
            ['name' => 'Lagartos RC', 'abbreviation' => 'LRC', 'is_own_team' => false],
            ['name' => 'Old Gabs', 'abbreviation' => 'OGB', 'is_own_team' => false],
            ['name' => 'Old Anglonians', 'abbreviation' => 'OA', 'is_own_team' => false],
            ['name' => 'Costa del Sol', 'abbreviation' => 'CDS', 'is_own_team' => false],
        ];

        foreach ($teams as $team) {
            \App\Models\Team::firstOrCreate(
                ['name' => $team['name']],
                $team
            );
        }
    }
}
