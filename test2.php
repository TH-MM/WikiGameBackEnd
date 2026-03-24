<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Round;

$round = Round::latest()->first();
echo "End time: " . $round->end_time . "\n";
echo "Now: " . now() . "\n";

echo "Diff 1: " . $round->end_time->diffInSeconds(now()) . "\n";
echo "Diff 2: " . $round->end_time->diffInSeconds(now(), false) . "\n";
echo "Diff 3 (now->diff): " . now()->diffInSeconds($round->end_time, false) . "\n";
