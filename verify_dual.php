<?php

use App\Http\Controllers\GameController;
use App\Models\Round;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Delete latest round to force a new one
Round::latest()->delete();

$request = Request::create('/api/current-round', 'GET', ['lang' => 'en']);
$controller = new GameController();
$response = $controller->currentRound($request);
$data = $response->getData();

echo "Round ID: " . $data->round->id . "\n";
echo "Start Genre: " . $data->round->start_genre . "\n";
echo "Target Genre: " . $data->round->target_genre . "\n";
echo "Start Page: " . $data->round->start_page . "\n";
echo "Target Page: " . $data->round->target_page . "\n";
