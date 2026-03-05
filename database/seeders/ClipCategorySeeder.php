<?php

namespace Database\Seeders;

use App\Models\ClipCategory;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class ClipCategorySeeder extends Seeder
{
    /**
     * Las 4 categorías de análisis estándar para rugby.
     * Siempre relativas al equipo analizado (equipo local/propio).
     */
    public static array $defaults = [
        [
            'name'         => 'Ataque',
            'slug'         => 'ataque',
            'color'        => '#1a6b2d',  // verde
            'hotkey'       => 'a',
            'lead_seconds' => 1,
            'lag_seconds'  => 1,
            'sort_order'   => 1,
        ],
        [
            'name'         => 'Defensa',
            'slug'         => 'defensa',
            'color'        => '#922b2b',  // rojo
            'hotkey'       => 'd',
            'lead_seconds' => 1,
            'lag_seconds'  => 1,
            'sort_order'   => 2,
        ],
        [
            'name'         => 'Highlights 1',
            'slug'         => 'highlights-1',
            'color'        => '#005461',
            'hotkey'       => 's',
            'lead_seconds' => 1,
            'lag_seconds'  => 1,
            'sort_order'   => 3,
        ],
        [
            'name'         => 'Highlights 2',
            'slug'         => 'highlights-2',
            'color'        => '#7d4e00',
            'hotkey'       => 'r',
            'lead_seconds' => 1,
            'lag_seconds'  => 1,
            'sort_order'   => 4,
        ],
    ];

    public function run(): void
    {
        foreach (Organization::all() as $org) {
            static::seedForOrganization($org);
        }
    }

    /**
     * Crea las categorías por defecto para una organización.
     * Usa firstOrCreate → no duplica si ya existen.
     */
    public static function seedForOrganization(Organization $org): void
    {
        $firstUser = $org->users()->first();
        $createdBy = $firstUser?->id ?? auth()->id();

        foreach (static::$defaults as $cat) {
            ClipCategory::firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'slug'            => $cat['slug'],
                ],
                [
                    'name'         => $cat['name'],
                    'color'        => $cat['color'],
                    'hotkey'       => $cat['hotkey'],
                    'lead_seconds' => $cat['lead_seconds'],
                    'lag_seconds'  => $cat['lag_seconds'],
                    'sort_order'   => $cat['sort_order'],
                    'scope'        => ClipCategory::SCOPE_ORGANIZATION,
                    'is_active'    => true,
                    'created_by'   => $createdBy,
                ]
            );
        }
    }
}
