<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

try {
    $response = Http::withoutVerifying()
        ->withHeaders(['User-Agent' => 'WikiGame/1.0'])
        ->get('https://en.wikipedia.org/w/api.php', [
            'action' => 'query',
            'list' => 'random',
            'rnnamespace' => 0,
            'rnlimit' => 1,
            'format' => 'json'
    ]);
    print_r($response->json());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
