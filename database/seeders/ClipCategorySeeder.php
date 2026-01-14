<?php

namespace Database\Seeders;

use App\Models\ClipCategory;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class ClipCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Try', 'slug' => 'try', 'color' => '#28a745', 'hotkey' => 't', 'lead_seconds' => 5, 'lag_seconds' => 3, 'sort_order' => 1],
            ['name' => 'Scrum', 'slug' => 'scrum', 'color' => '#007bff', 'hotkey' => 's', 'lead_seconds' => 3, 'lag_seconds' => 5, 'sort_order' => 2],
            ['name' => 'Lineout', 'slug' => 'lineout', 'color' => '#fd7e14', 'hotkey' => 'l', 'lead_seconds' => 3, 'lag_seconds' => 4, 'sort_order' => 3],
            ['name' => 'Penal', 'slug' => 'penal', 'color' => '#dc3545', 'hotkey' => 'p', 'lead_seconds' => 2, 'lag_seconds' => 5, 'sort_order' => 4],
            ['name' => 'Tackle', 'slug' => 'tackle', 'color' => '#6f42c1', 'hotkey' => 'k', 'lead_seconds' => 2, 'lag_seconds' => 2, 'sort_order' => 5],
            ['name' => 'Ruck', 'slug' => 'ruck', 'color' => '#17a2b8', 'hotkey' => 'r', 'lead_seconds' => 2, 'lag_seconds' => 3, 'sort_order' => 6],
            ['name' => 'Maul', 'slug' => 'maul', 'color' => '#e83e8c', 'hotkey' => 'm', 'lead_seconds' => 2, 'lag_seconds' => 4, 'sort_order' => 7],
            ['name' => 'Knock-on', 'slug' => 'knock-on', 'color' => '#6c757d', 'hotkey' => 'n', 'lead_seconds' => 2, 'lag_seconds' => 2, 'sort_order' => 8],
            ['name' => 'Break', 'slug' => 'break', 'color' => '#20c997', 'hotkey' => 'b', 'lead_seconds' => 3, 'lag_seconds' => 4, 'sort_order' => 9],
            ['name' => 'Drop Goal', 'slug' => 'drop-goal', 'color' => '#ffc107', 'hotkey' => 'd', 'lead_seconds' => 3, 'lag_seconds' => 3, 'sort_order' => 10],
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
