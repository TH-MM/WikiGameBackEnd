<?php
$env = file_get_contents('.env');
preg_match('/GEMINI_API_KEY=(.*)/', $env, $m);
$apiKey = trim($m[1] ?? '');
if (!$apiKey) exit('NO KEY');
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$data = json_decode($response, true);
if (!isset($data['models'])) {
    print_r($response);
    exit;
}
foreach ($data['models'] as $m) {
    if (strpos($m['name'], 'gemini') !== false) {
        echo $m['name'] . " - " . ($m['displayName'] ?? '') . "\n";
    }
}
