<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Adulta Primera', 'description' => 'Categoría primera división adulta'],
            ['name' => 'Adulta Intermedia', 'description' => 'Categoría intermedia división adulta'],
            ['name' => 'Juveniles', 'description' => 'Categoría juvenil'],
            ['name' => 'Femenino', 'description' => 'Categoría femenina'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
