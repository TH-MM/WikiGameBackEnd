<?php

use App\Http\Controllers\GameController;
use App\Models\Round;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Delete latest round to force a new one for Arabic Music
Round::latest()->delete();

$request = Request::create('/api/current-round', 'GET', ['lang' => 'ar']);
$controller = new GameController();

// We can't easily "force" a genre because it's random, but we can call it multiple times 
// or just verify what we get.
$response = $controller->currentRound($request);
$data = $response->getData();

echo "Language: ar\n";
echo "Genre: " . ($data->round->genre ?? 'NULL') . "\n";
echo "Start: " . $data->round->start_page . "\n";
echo "Target: " . $data->round->target_page . "\n";
