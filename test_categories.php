<?php
require __DIR__ . '/vendor/autoload.php';
use Illuminate\Support\Facades\Http;

// Mocking some Laravel stuff for a standalone script is hard, 
// I'll just use raw curl/Guzzle or direct Http if possible.
// Actually, I'll just use a simple PHP script with file_get_contents for testing.

function fetchRandomWikiPageFromCategory($category, $lang = 'ar') {
    $url = "https://{$lang}.wikipedia.org/w/api.php?action=query&list=categorymembers&cmtitle=" . urlencode($category) . "&cmtype=page&cmlimit=500&format=json";
    
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: ArabicWikiGame/1.0\r\n"
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    $members = $data['query']['categorymembers'] ?? [];
    if (empty($members)) {
        return "No pages found in $category ($lang)";
    }
    $randomMember = $members[array_rand($members)];
    return $randomMember['title'];
}

$categories = [
    'ar' => [
        'History' => 'تصنيف:تاريخ',
        'Geography' => 'تصنيف:جغرافيا',
        'Movies' => 'تصنيف:أفلام',
        'Music' => 'تصنيف:موسيقى',
        'Sports' => 'تصنيف:رياضة',
        'Science' => 'تصنيف:علوم',
        'People' => 'تصنيف:أعلام'
    ],
    'en' => [
        'History' => 'Category:History',
        'Geography' => 'Category:Geography',
        'Movies' => 'Category:Films',
        'Music' => 'Category:Music',
        'Sports' => 'Category:Sports',
        'Science' => 'Category:Science',
        'People' => 'Category:People'
    ]
];

echo "Testing English History:\n";
echo fetchRandomWikiPageFromCategory($categories['en']['History'], 'en') . "\n";

echo "\nTesting Arabic History:\n";
echo fetchRandomWikiPageFromCategory($categories['ar']['History'], 'ar') . "\n";

echo "\nTesting Arabic Music:\n";
echo fetchRandomWikiPageFromCategory($categories['ar']['Music'], 'ar') . "\n";
