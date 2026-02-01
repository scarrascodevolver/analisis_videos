<?php

/**
 * Debug script para verificar el estado de las anotaciones
 * Ejecutar: php debug-annotations.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEBUG: Video Annotations ===\n\n";

// 1. Verificar estructura de tabla
echo "1. Estructura de la tabla video_annotations:\n";
$columns = DB::select('DESCRIBE video_annotations');
foreach ($columns as $column) {
    echo "   - {$column->Field}: {$column->Type} (Default: {$column->Default}, Null: {$column->Null})\n";
}
echo "\n";

// 2. Ver anotaciones existentes
echo "2. Anotaciones en la base de datos:\n";
$annotations = DB::table('video_annotations')
    ->select('id', 'video_id', 'timestamp', 'duration_seconds', 'is_permanent', 'created_at')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($annotations->isEmpty()) {
    echo "   No hay anotaciones en la base de datos.\n\n";
} else {
    foreach ($annotations as $ann) {
        $isPerm = $ann->is_permanent ?? 'NULL';
        $duration = $ann->duration_seconds ?? 'NULL';
        echo "   ID: {$ann->id} | Video: {$ann->video_id} | Time: {$ann->timestamp}s | Duration: {$duration}s | Permanent: {$isPerm}\n";
    }
    echo "\n";
}

// 3. Verificar tipos de datos de campos
echo "3. Verificando valores de is_permanent:\n";
$checks = DB::table('video_annotations')
    ->select(DB::raw('
        COUNT(*) as total,
        SUM(CASE WHEN is_permanent IS NULL THEN 1 ELSE 0 END) as null_count,
        SUM(CASE WHEN is_permanent = 0 THEN 1 ELSE 0 END) as false_count,
        SUM(CASE WHEN is_permanent = 1 THEN 1 ELSE 0 END) as true_count
    '))
    ->first();

echo "   Total: {$checks->total}\n";
echo "   NULL values: {$checks->null_count}\n";
echo "   FALSE (0): {$checks->false_count}\n";
echo "   TRUE (1): {$checks->true_count}\n";
echo "\n";

// 4. Test JSON serialization del modelo
echo "4. Testing modelo VideoAnnotation (JSON serialization):\n";
$testAnnotation = \App\Models\VideoAnnotation::orderBy('created_at', 'desc')->first();
if ($testAnnotation) {
    $json = $testAnnotation->toArray();
    echo "   Annotation ID: {$testAnnotation->id}\n";
    echo '   is_permanent (raw from DB): '.var_export($testAnnotation->getAttributes()['is_permanent'] ?? 'NOT SET', true)."\n";
    echo '   is_permanent (after cast): '.var_export($testAnnotation->is_permanent, true)."\n";
    echo '   is_permanent (in JSON): '.var_export($json['is_permanent'], true).' (type: '.gettype($json['is_permanent']).")\n";
    echo "   duration_seconds: {$json['duration_seconds']}\n";
} else {
    echo "   No annotations found to test.\n";
}
echo "\n";

echo "=== FIN DEBUG ===\n";
