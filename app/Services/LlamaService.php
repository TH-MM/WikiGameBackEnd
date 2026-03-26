<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlamaService
{
    private ?string $apiKey;
    private string $baseUrl;
    private string $model;
    private string $provider;

    public function __construct()
    {
        $this->provider = env('AI_PROVIDER', 'groq'); // Default to groq now
        
        $config = config("services.{$this->provider}");
        
        $this->apiKey = $config['key'] ?? null;
        $this->baseUrl = $config['url'] ?? '';
        $this->model = $config['model'] ?? '';
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

            if ($response->status() === 429) {
                Log::error("{$this->provider} API Rate Limit (429) - Fallback triggered.");
            } else {
                Log::error("{$this->provider} API Error (Model: {$this->model}): " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("{$this->provider} API Exception: " . $e->getMessage());
        }

        return null;
    }
}
