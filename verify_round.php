<?php

use App\Http\Controllers\GameController;
use App\Models\Round;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Request::create('/api/current-round', 'GET', ['lang' => 'en']);
$controller = new GameController();
$response = $controller->currentRound($request);

echo "Response Body:\n";
echo json_encode($response->getData(), JSON_PRETTY_PRINT);
echo "\n\nLatest Round in DB:\n";
print_r(Round::latest()->first()->toArray());
