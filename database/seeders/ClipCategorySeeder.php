<?php

namespace Database\Seeders;

use App\Models\ClipCategory;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class ClipCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 4 categorías genéricas de rugby
        $categories = [
            ['name' => 'Try', 'slug' => 'try', 'color' => '#1a6b2d', 'hotkey' => 't', 'lead_seconds' => 5, 'lag_seconds' => 3, 'sort_order' => 1],
            ['name' => 'Penal', 'slug' => 'penal', 'color' => '#922b2b', 'hotkey' => 'p', 'lead_seconds' => 2, 'lag_seconds' => 5, 'sort_order' => 2],
            ['name' => 'Scrum', 'slug' => 'scrum', 'color' => '#0056b3', 'hotkey' => 's', 'lead_seconds' => 3, 'lag_seconds' => 5, 'sort_order' => 3],
            ['name' => 'Tackle', 'slug' => 'tackle', 'color' => '#4a2d82', 'hotkey' => 'k', 'lead_seconds' => 2, 'lag_seconds' => 2, 'sort_order' => 4],
        ];

        // Crear categorías para cada organización existente
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            // Obtener el primer usuario de la org como creador
            $firstUser = $org->users()->first();
            if (! $firstUser) {
                continue;
            }

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
