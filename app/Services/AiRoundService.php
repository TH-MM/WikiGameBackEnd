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

        $prompt = "You are an expert designer of the Wikipedia Game. Your goal is to create MEDIUM or HARD level and FUN rounds for casual players.

Language: English

STRICT RULES:
1. Select two VERY FAMOUS but distant Wikipedia articles (start_page and target_page).
2. The connection should NOT be obvious. A player should need 5–10 clicks to connect them.
3. The start_page and target_page must NOT be directly linked.
4. The path should rely on logical but non-trivial relationships.
5. Avoid obscure, technical, or niche topics. Both topics must be widely known.

CHALLENGING EXAMPLES:
- 'The Beatles' → 'Apollo 11'
- 'Harry Potter' → 'Industrial Revolution'
- 'iPhone' → 'Ancient Rome'

OUTPUT FORMAT:
Return ONLY a valid JSON object:
{
  \"start_page\": \"...\",
  \"target_page\": \"...\",
  \"difficulty\": \"medium\"
}

IMPORTANT:
- Always set difficulty to 'medium' or 'hard'
- Do NOT include explanations
- Do NOT include markdown
- Ensure titles match EXACT Wikipedia page titles.
- Variety seed: " . bin2hex(random_bytes(4)) . "
- Random Theme: " . (['History', 'Science', 'Nature', 'Pop Culture', 'Geography', 'Sports', 'Movies', 'Music'][rand(0, 7)]);

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

        // STATIC FALLBACK for testing or when API fails
        // You can change these values to test specific rounds
        return [
            'start_page' => "Covid-19",
            'target_page' => "Egypt",
            'difficulty' => "medium"
        ];
    }
}
