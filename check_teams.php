<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$teams = DB::table('videos')
    ->whereNotNull('analyzed_team_name')
    ->where('analyzed_team_name', '!=', '')
    ->select('analyzed_team_name', DB::raw('COUNT(*) as total'))
    ->groupBy('analyzed_team_name')
    ->orderByDesc('total')
    ->get();

foreach ($teams as $t) {
    echo $t->analyzed_team_name . ' (' . $t->total . ')' . PHP_EOL;
}
