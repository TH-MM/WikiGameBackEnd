<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRoundService
{
    private ?string $apiKey;
    private string $baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent";

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    public function generateRound(): array
    {
        if (empty($this->apiKey)) {
            Log::warning("Gemini API key missing. AI selection disabled.");
            return [];
        }

        $themes = ['History', 'Science', 'Nature', 'Pop Culture', 'Geography', 'Sports', 'Movies', 'Music', 'Technology', 'Business', 'Politics', 'Art'];
        $startTheme = $themes[random_int(0, count($themes) - 1)];
        $targetTheme = $themes[random_int(0, count($themes) - 1)];

        $prompt = "You are an expert designer of the Wikipedia Game. Your goal is to create MEDIUM or HARD level rounds where the articles are from COMPLETELY UNRELATED domains.

Language: English

STRICT RULES:
1. Select two VERY FAMOUS articles (start_page and target_page) from DIFFERENT categories.
2. The connection should NOT be obvious. A player should need 3–6 clicks to connect them.
3. The start_page and target_page must NOT be directly linked.
4. The path should rely on logical but non-trivial relationships across different fields.
5. Avoid obscure, technical, or niche topics. Both topics must be widely known.

CHALLENGING CROSS-GENRE EXAMPLES:
- 'Electric power' → 'BBC News'
- 'Blog' → 'Transylvania'
- 'Cannabis (drug)' → 'Epidemic'
- 'iPhone' → 'Ancient Rome'

OUTPUT FORMAT:
Return ONLY a valid JSON object:
{
  \"start_page\": \"...\",
  \"target_page\": \"...\",
  \"difficulty\": \"easy\"
}

IMPORTANT:
- Always set difficulty to 'medium' or 'easy'
- Do NOT include explanations
- Do NOT include markdown
- Ensure titles match EXACT Wikipedia page titles.";

        try {
            $response = Http::withoutVerifying()
                ->post("{$this->baseUrl}?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 1.5,
                        'topP' => 0.95,
                    ]
                ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $cleanJson = preg_replace('/^```json\s*|```\s*$/', '', trim($text));
                $result = json_decode($cleanJson, true);

                if (isset($result['start_page']) && isset($result['target_page'])) {
                    return [
                        'start_page' => $result['start_page'],
                        'target_page' => $result['target_page'],
                        'difficulty' => $result['difficulty'] ?? "medium"
                    ];
                }
            }

            Log::error("Gemini API Error or Invalid JSON: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Gemini API Exception: " . $e->getMessage());
        }

        // DYNAMIC FALLBACK pool for when Gemini API fails
        $fallbacks = [
            ['start' => "Electric power", 'target' => "BBC News", 'diff' => "medium"],
            ['start' => "Blog", 'target' => "Transylvania", 'diff' => "medium"],
            ['start' => "Cannabis (drug)", 'target' => "Epidemic", 'diff' => "hard"],
            ['start' => "The Dark Knight", 'target' => "The Great Depression", 'diff' => "hard"],
            ['start' => "IPhone", 'target' => "Ancient Rome", 'diff' => "medium"],
            ['start' => "Taylor Swift", 'target' => "New York City", 'diff' => "medium"],
            ['start' => "World War II", 'target' => "United States", 'diff' => "medium"],
            ['start' => "Pizza", 'target' => "Italy", 'diff' => "easy"],
            ['start' => "Global warming", 'target' => "Renewable energy", 'diff' => "medium"],
        ];

        $choice = $fallbacks[random_int(0, count($fallbacks) - 1)];

        return [
            'start_page' => $choice['start'],
            'target_page' => $choice['target'],
            'difficulty' => $choice['diff']
        ];
    }
}
