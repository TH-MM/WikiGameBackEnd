<?php

function getRandomPageFromCategoryRedirect($category, $lang = 'ar') {
    $url = "https://{$lang}.wikipedia.org/wiki/Special:RandomInCategory/" . urlencode($category);
    
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: ArabicWikiGame/1.0\r\n",
            "follow_location" => 0 // We want to see where it redirects
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ]
    ];
    $context = stream_context_create($opts);
    $headers = get_headers($url, 1, $context);
    
    if (isset($headers['Location'])) {
        $location = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
        // Location is like /wiki/Title
        $title = str_replace(['/wiki/', '_'], ['', ' '], urldecode($location));
        return $title;
    }
    
    return "Failed to get redirect for $category";
}

echo "Testing English History (Redirect):\n";
echo getRandomPageFromCategoryRedirect('History', 'en') . "\n";

echo "\nTesting Arabic History (Redirect):\n";
echo getRandomPageFromCategoryRedirect('تاريخ', 'ar') . "\n";

echo "\nTesting Arabic Music (Redirect):\n";
echo getRandomPageFromCategoryRedirect('موسيقى', 'ar') . "\n";
