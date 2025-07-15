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
            ['name' => 'LOS TRONCOS', 'abbreviation' => 'LT', 'is_own_team' => true],
            ['name' => 'DOBS', 'abbreviation' => 'DOBS', 'is_own_team' => false],
            ['name' => 'ALL BRADS', 'abbreviation' => 'AB', 'is_own_team' => false],
            ['name' => 'OLD GEORGIANS', 'abbreviation' => 'OG', 'is_own_team' => false],
            ['name' => 'TABANCURA RC', 'abbreviation' => 'TRC', 'is_own_team' => false],
            ['name' => 'OLD GABS', 'abbreviation' => 'OGB', 'is_own_team' => false],
            ['name' => 'LAGARTOS RC', 'abbreviation' => 'LRC', 'is_own_team' => false],
            ['name' => 'OLD ANGLONIANS', 'abbreviation' => 'OA', 'is_own_team' => false],
            ['name' => 'OLD LOCKS', 'abbreviation' => 'OL', 'is_own_team' => false],
            ['name' => 'COSTA DEL SOL', 'abbreviation' => 'CDS', 'is_own_team' => false],
        ];

        foreach ($teams as $team) {
            \App\Models\Team::create($team);
        }
    }
}
