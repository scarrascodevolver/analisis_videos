<?php

namespace Database\Seeders;

use App\Models\ClipCategory;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class ClipCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Colores oscuros adaptados al tema dark del video player
        $categories = [
            ['name' => 'Try', 'slug' => 'try', 'color' => '#1a6b2d', 'hotkey' => 't', 'lead_seconds' => 5, 'lag_seconds' => 3, 'sort_order' => 1],
            ['name' => 'Scrum', 'slug' => 'scrum', 'color' => '#0056b3', 'hotkey' => 's', 'lead_seconds' => 3, 'lag_seconds' => 5, 'sort_order' => 2],
            ['name' => 'Lineout', 'slug' => 'lineout', 'color' => '#b35900', 'hotkey' => 'l', 'lead_seconds' => 3, 'lag_seconds' => 4, 'sort_order' => 3],
            ['name' => 'Penal', 'slug' => 'penal', 'color' => '#922b2b', 'hotkey' => 'p', 'lead_seconds' => 2, 'lag_seconds' => 5, 'sort_order' => 4],
            ['name' => 'Tackle', 'slug' => 'tackle', 'color' => '#4a2d82', 'hotkey' => 'k', 'lead_seconds' => 2, 'lag_seconds' => 2, 'sort_order' => 5],
            ['name' => 'Ruck', 'slug' => 'ruck', 'color' => '#117a8b', 'hotkey' => 'r', 'lead_seconds' => 2, 'lag_seconds' => 3, 'sort_order' => 6],
            ['name' => 'Maul', 'slug' => 'maul', 'color' => '#a02d62', 'hotkey' => 'm', 'lead_seconds' => 2, 'lag_seconds' => 4, 'sort_order' => 7],
            ['name' => 'Knock-on', 'slug' => 'knock-on', 'color' => '#495057', 'hotkey' => 'n', 'lead_seconds' => 2, 'lag_seconds' => 2, 'sort_order' => 8],
            ['name' => 'Break', 'slug' => 'break', 'color' => '#158a67', 'hotkey' => 'b', 'lead_seconds' => 3, 'lag_seconds' => 4, 'sort_order' => 9],
            ['name' => 'Drop Goal', 'slug' => 'drop-goal', 'color' => '#b38600', 'hotkey' => 'd', 'lead_seconds' => 3, 'lag_seconds' => 3, 'sort_order' => 10],
        ];

        // Crear categorías para cada organización existente
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            // Obtener el primer usuario de la org como creador
            $firstUser = $org->users()->first();
            if (!$firstUser) continue;

            foreach ($categories as $cat) {
                ClipCategory::firstOrCreate(
                    [
                        'organization_id' => $org->id,
                        'slug' => $cat['slug'],
                    ],
                    [
                        'name' => $cat['name'],
                        'color' => $cat['color'],
                        'hotkey' => $cat['hotkey'],
                        'lead_seconds' => $cat['lead_seconds'],
                        'lag_seconds' => $cat['lag_seconds'],
                        'sort_order' => $cat['sort_order'],
                        'is_active' => true,
                        'created_by' => $firstUser->id,
                    ]
                );
            }
        }
    }
}
