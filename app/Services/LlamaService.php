<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlamaService
{
    private ?string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.sambanova.key');
        $this->baseUrl = config('services.sambanova.url', 'https://api.sambanova.ai/v1/chat/completions');
        $this->model = config('services.sambanova.model', 'Meta-Llama-3.1-70B-Instruct');
    }

    /**
     * Generate content using the Llama model via SambaNova.
     * 
     * @param string $prompt
     * @return string|null
     */
    public function generate(string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning("SambaNova API key missing. Llama generation disabled.");
            return null;
        }

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 1.5,
                    'top_p' => 0.9,
                ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }

            Log::error("SambaNova API Error (Model: {$this->model}): " . $response->body());
        } catch (\Exception $e) {
            Log::error("SambaNova API Exception: " . $e->getMessage());
        }

        return null;
    }
}
