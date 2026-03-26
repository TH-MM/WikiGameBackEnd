<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRoundService
{
    private LlamaService $llamaService;

    public function __construct(LlamaService $llamaService)
    {
        $this->llamaService = $llamaService;
    }

    public function generateRound(): array
    {
        $themes = ['History', 'Science', 'Nature', 'Pop Culture', 'Geography', 'Sports', 'Movies', 'Music', 'Technology', 'Business', 'Politics', 'Art', 'Philosophy', 'Literature', 'Environment', 'Food & Drink'];
        $startTheme = $themes[random_int(0, count($themes) - 1)];
        $targetTheme = $themes[random_int(0, count($themes) - 1)];
        
        // Ensure themes are different
        while ($startTheme === $targetTheme) {
            $targetTheme = $themes[random_int(0, count($themes) - 1)];
        }

        $prompt = "You are an expert designer of the Wikipedia Game. Your goal is to create MEDIUM or HARD level rounds where the articles are from COMPLETELY UNRELATED domains.

Language: English

STRICT RULES:
1. Select two VERY FAMOUS articles (start_page and target_page) from DIFFERENT categories.
2. The start_page should be related to the theme: '{$startTheme}'.
3. The target_page should be related to the theme: '{$targetTheme}'.
4. The connection should NOT be obvious. A player should need 3–6 clicks to connect them.
5. The start_page and target_page must NOT be directly linked.
6. Avoid obscure, technical, or niche topics. Both topics must be widely known.

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
            $text = $this->llamaService->generate($prompt);

            if ($text) {
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

            Log::error("Llama API failed to return valid JSON: " . ($text ?? 'Null response'));
        } catch (\Exception $e) {
            Log::error("AiRoundService Exception during Llama generation: " . $e->getMessage());
        }

        // DYNAMIC FALLBACK pool for when AI fails
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
