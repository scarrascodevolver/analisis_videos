<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Video;
use Illuminate\Support\Facades\DB;

class MigrateVideosToNewCategoriesSeeder extends Seeder
{
    public function run()
    {
        echo "🎬 Iniciando migración de videos a nueva estructura de categorías...\n";

        // Mapeo de categorías antiguas a nuevas
        $categoryMapping = [
            1 => 2,  // Adulta Primera (ID: 1) → Adultas (ID: 2)
            2 => 2,  // Adulta Intermedia (ID: 2) → Adultas (ID: 2)
            3 => 1,  // Juveniles (ID: 3) → Juveniles (ID: 1)
            4 => 3,  // Femenino (ID: 4) → Femenino (ID: 3)
        ];

        foreach ($categoryMapping as $oldId => $newId) {
            $videos = Video::where('category_id', $oldId)->get();

            if ($videos->count() > 0) {
                $oldName = $this->getOldCategoryName($oldId);
                $newName = $this->getNewCategoryName($newId);

                echo "📋 Migrando videos de '{$oldName}' (ID: {$oldId}) → '{$newName}' (ID: {$newId})\n";

                foreach ($videos as $video) {
                    $video->update(['category_id' => $newId]);
                    echo "   ✅ '{$video->title}' migrado\n";
                }

                echo "   📊 Total migrados: {$videos->count()}\n\n";
            }
        }

        echo "🎯 Migración de videos completada exitosamente\n";
    }

    private function getOldCategoryName($id)
    {
        $names = [
            1 => 'Adulta Primera',
            2 => 'Adulta Intermedia',
            3 => 'Juveniles',
            4 => 'Femenino'
        ];

        return $names[$id] ?? "ID: {$id}";
    }

    private function getNewCategoryName($id)
    {
        $names = [
            1 => 'Juveniles',
            2 => 'Adultas',
            3 => 'Femenino'
        ];

        return $names[$id] ?? "ID: {$id}";
    }
}