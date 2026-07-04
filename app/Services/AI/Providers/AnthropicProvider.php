<?php

namespace Services\AI\Providers;

use GuzzleHttp\Client;

/**
 * Anthropic Claude provider
 * Default model is Haiku 4.5 — the cheapest Claude model ($1/$5 per MTok),
 * more than capable for cooking Q&A. Override with ANTHROPIC_MODEL in .env.
 */
class AnthropicProvider implements ChatProviderInterface
{
    private const DEFAULT_MODEL = 'claude-haiku-4-5';

    private Client $httpClient;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
        if (empty($this->apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY not configured in .env file');
        }

        $this->httpClient = new Client(['timeout' => 30]);
        $this->model = getenv('ANTHROPIC_MODEL') ?: self::DEFAULT_MODEL;
    }

    public function chat(string $systemPrompt, array $messages): array
    {
        $response = $this->httpClient->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'messages' => $messages,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'content' => $data['content'][0]['text'] ?? 'No response',
            'usage' => [
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            ],
        ];
    }

    public function name(): string
    {
        return 'anthropic';
    }
}
