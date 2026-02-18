<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$updated = DB::table('videos')
    ->where('analyzed_team_name', 'Los Troncos')
    ->update(['analyzed_team_name' => 'Club Los Troncos']);

echo "Videos actualizados: {$updated}" . PHP_EOL;
