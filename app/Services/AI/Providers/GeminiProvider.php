<?php

namespace Services\AI\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Google Gemini provider
 * Free tier: no card required, ~10-15 requests/minute and ~1,500 requests/day
 * on Flash models — plenty for development and small user bases.
 * Default model tracks the latest Flash release; override with GEMINI_MODEL in .env.
 *
 * The free tier occasionally returns 503 "high demand" during Google-side
 * capacity spikes, so chat() retries once and then falls back to a second
 * model (default gemini-flash-lite-latest, a separate capacity pool).
 */
class GeminiProvider implements ChatProviderInterface
{
    private const DEFAULT_MODEL = 'gemini-flash-latest';
    private const DEFAULT_FALLBACK_MODEL = 'gemini-flash-lite-latest';
    private const RETRYABLE_STATUSES = [429, 500, 503];
    private const RETRY_DELAY_MS = 1200;

    private Client $httpClient;
    private string $apiKey;
    private string $model;
    private string $fallbackModel;

    public function __construct()
    {
        $this->apiKey = getenv('GEMINI_API_KEY') ?: '';
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY not configured in .env file');
        }

        $this->httpClient = new Client(['timeout' => 30]);
        $this->model = getenv('GEMINI_MODEL') ?: self::DEFAULT_MODEL;
        $this->fallbackModel = getenv('GEMINI_FALLBACK_MODEL') ?: self::DEFAULT_FALLBACK_MODEL;
    }

    public function chat(string $systemPrompt, array $messages): array
    {
        // Gemini uses 'model' instead of 'assistant' and wraps text in parts
        $contents = [];
        foreach ($messages as $message) {
            $contents[] = [
                'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message['content']]],
            ];
        }

        // Primary, primary again after a pause, then the fallback model
        $attempts = [
            ['model' => $this->model, 'delayMs' => 0],
            ['model' => $this->model, 'delayMs' => self::RETRY_DELAY_MS],
        ];
        if ($this->fallbackModel !== '' && $this->fallbackModel !== $this->model) {
            $attempts[] = ['model' => $this->fallbackModel, 'delayMs' => 0];
        }

        $lastError = null;
        foreach ($attempts as $attempt) {
            if ($attempt['delayMs'] > 0) {
                usleep($attempt['delayMs'] * 1000);
            }
            try {
                return $this->generateContent($attempt['model'], $systemPrompt, $contents);
            } catch (BadResponseException $e) {
                $status = $e->getResponse()->getStatusCode();
                if (!in_array($status, self::RETRYABLE_STATUSES, true)) {
                    throw $e;
                }
                error_log("GeminiProvider: {$attempt['model']} returned {$status}, trying next option");
                $lastError = $e;
            }
        }

        throw $lastError;
    }

    private function generateContent(string $model, string $systemPrompt, array $contents): array
    {
        $response = $this->httpClient->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
            [
                'headers' => [
                    'x-goog-api-key' => $this->apiKey,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents' => $contents,
                    'generationConfig' => ['maxOutputTokens' => 1024],
                ],
            ]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($text === null) {
            // Safety block or empty candidate — surface the reason for the error log
            $reason = $data['candidates'][0]['finishReason']
                ?? $data['promptFeedback']['blockReason']
                ?? 'unknown';
            throw new \RuntimeException("Gemini returned no text (reason: {$reason})");
        }

        return [
            'content' => $text,
            'usage' => [
                'input_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                'output_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
            ],
        ];
    }

    public function name(): string
    {
        return 'gemini';
    }
}
