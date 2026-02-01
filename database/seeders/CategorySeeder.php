<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Estructura realista para rugby: 3 categorías principales
        $categories = [
            ['name' => 'Juveniles', 'description' => 'Categoría juvenil (Sub-18, Sub-16, etc.)'],
            ['name' => 'Adultas', 'description' => 'Categorías adultas (Primera + Intermedia)'],
            ['name' => 'Femenino', 'description' => 'Categoría femenina'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
