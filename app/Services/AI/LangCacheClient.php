<?php

namespace Services\AI;

use GuzzleHttp\Client;

/**
 * LangCache Client for semantic caching of AI responses
 * Uses Redis-based semantic caching to reduce API costs
 */
class LangCacheClient
{
    private Client $httpClient;
    private string $apiKey;
    private string $host;
    private string $cacheId;
    private bool $enabled;

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 10]);
        $this->apiKey = getenv('LANGCACHE_API_KEY') ?: '';
        $this->host = getenv('LANGCACHE_HOST') ?: '';
        $this->cacheId = getenv('LANGCACHE_CACHE_ID') ?: '';
        $this->enabled = filter_var(getenv('LANGCACHE_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if LangCache is enabled and configured
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey) && !empty($this->host) && !empty($this->cacheId);
    }

    /**
     * Search cache for similar prompts
     *
     * @param string $prompt The user prompt to search for
     * @param array $attributes Optional attributes to scope the search
     * @return array|null Cached response if found, null otherwise
     */
    public function searchCache(string $prompt, array $attributes = []): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $payload = ['prompt' => $prompt];
            if (!empty($attributes)) {
                $payload['attributes'] = $attributes;
            }

            $response = $this->httpClient->post(
                "{$this->host}/v1/caches/{$this->cacheId}/entries/search",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $payload,
                    'http_errors' => false
                ]
            );

            $statusCode = $response->getStatusCode();

            // 200 = cache hit, 404 = cache miss
            if ($statusCode === 200) {
                $body = json_decode($response->getBody()->getContents(), true);

                if (isset($body['response'])) {
                    return [
                        'response' => $body['response'],
                        'entryId' => $body['id'] ?? null,
                        'cached' => true,
                        'similarity' => $body['similarity'] ?? null
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            error_log('LangCache search failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Store a new response in the cache
     *
     * @param string $prompt The user prompt
     * @param string $response The LLM response
     * @param array $attributes Optional attributes for filtering
     * @return bool Success status
     */
    public function storeResponse(string $prompt, string $response, array $attributes = []): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $payload = [
                'prompt' => $prompt,
                'response' => $response
            ];

            if (!empty($attributes)) {
                $payload['attributes'] = $attributes;
            }

            $response = $this->httpClient->post(
                "{$this->host}/v1/caches/{$this->cacheId}/entries",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $payload,
                    'http_errors' => false
                ]
            );

            return $response->getStatusCode() === 201;

        } catch (\Exception $e) {
            error_log('LangCache store failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a specific cached entry
     *
     * @param string $entryId The entry ID to delete
     * @return bool Success status
     */
    public function deleteEntry(string $entryId): bool
    {
        if (!$this->isEnabled() || empty($entryId)) {
            return false;
        }

        try {
            $response = $this->httpClient->delete(
                "{$this->host}/v1/caches/{$this->cacheId}/entries/{$entryId}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json'
                    ],
                    'http_errors' => false
                ]
            );

            return $response->getStatusCode() === 204;

        } catch (\Exception $e) {
            error_log('LangCache delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete cached entries by attributes
     *
     * @param array $attributes Attributes to match for deletion
     * @return bool Success status
     */
    public function deleteByAttributes(array $attributes): bool
    {
        if (!$this->isEnabled() || empty($attributes)) {
            return false;
        }

        try {
            $response = $this->httpClient->delete(
                "{$this->host}/v1/caches/{$this->cacheId}/entries",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                    'json' => ['attributes' => $attributes],
                    'http_errors' => false
                ]
            );

            return $response->getStatusCode() === 204;

        } catch (\Exception $e) {
            error_log('LangCache bulk delete failed: ' . $e->getMessage());
            return false;
        }
    }
}
