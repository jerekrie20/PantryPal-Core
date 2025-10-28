<?php

namespace Services\AI;

use GuzzleHttp\Client;

/**
 * Langfuse Client for AI observability and caching
 * Provides request caching, monitoring, and analytics
 */
class LangfuseClient
{
    private Client $httpClient;
    private string $publicKey;
    private string $secretKey;
    private string $host;
    private bool $enabled;
    
    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 10]);
        $this->publicKey = getenv('LANGFUSE_PUBLIC_KEY') ?: '';
        $this->secretKey = getenv('LANGFUSE_SECRET_KEY') ?: '';
        $this->host = getenv('LANGFUSE_HOST') ?: 'https://cloud.langfuse.com';
        $this->enabled = filter_var(getenv('LANGFUSE_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Check if Langfuse is enabled and configured
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->publicKey) && !empty($this->secretKey);
    }
    
    /**
     * Create a trace for tracking an AI conversation
     */
    public function createTrace(string $userId, string $sessionId, array $metadata = []): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }
        
        try {
            $traceId = 'trace_' . uniqid() . '_' . time();
            
            $response = $this->httpClient->post($this->host . '/api/public/traces', [
                'auth' => [$this->publicKey, $this->secretKey],
                'json' => [
                    'id' => $traceId,
                    'name' => 'PantryPal AI Chat',
                    'userId' => (string)$userId,
                    'sessionId' => $sessionId,
                    'metadata' => $metadata,
                    'timestamp' => date('c')
                ]
            ]);
            
            return $traceId;
        } catch (\Exception $e) {
            error_log('Langfuse trace creation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log a generation (AI request/response)
     */
    public function logGeneration(
        string $traceId,
        string $model,
        array $messages,
        string $systemPrompt,
        string $response,
        array $usage,
        float $duration
    ): void {
        if (!$this->isEnabled() || empty($traceId)) {
            return;
        }
        
        try {
            $this->httpClient->post($this->host . '/api/public/generations', [
                'auth' => [$this->publicKey, $this->secretKey],
                'json' => [
                    'traceId' => $traceId,
                    'name' => 'Claude Chat Completion',
                    'model' => $model,
                    'modelParameters' => [
                        'max_tokens' => 1024,
                        'system' => $systemPrompt
                    ],
                    'input' => $messages,
                    'output' => $response,
                    'usage' => [
                        'input' => $usage['input_tokens'] ?? 0,
                        'output' => $usage['output_tokens'] ?? 0,
                        'total' => ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0)
                    ],
                    'metadata' => [
                        'duration_ms' => round($duration * 1000, 2)
                    ],
                    'startTime' => date('c', time() - (int)$duration),
                    'endTime' => date('c')
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Langfuse generation logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Check cache for a similar prompt
     */
    public function getCachedResponse(string $prompt, string $model): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }
        
        // Simple cache key based on prompt hash
        $cacheKey = 'langfuse_cache_' . md5($prompt . $model);
        
        global $redis;
        if ($redis && method_exists($redis, 'isConnected') && $redis->isConnected()) {
            try {
                $cached = $redis->get($cacheKey);
                if ($cached) {
                    $data = json_decode($cached, true);
                    if ($data && isset($data['response'], $data['usage'])) {
                        return $data;
                    }
                }
            } catch (\Exception $e) {
                error_log('Cache retrieval failed: ' . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Store response in cache
     */
    public function cacheResponse(string $prompt, string $model, string $response, array $usage): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        
        $cacheKey = 'langfuse_cache_' . md5($prompt . $model);
        $cacheData = json_encode([
            'response' => $response,
            'usage' => $usage,
            'cached_at' => time()
        ]);
        
        global $redis;
        if ($redis && method_exists($redis, 'isConnected') && $redis->isConnected()) {
            try {
                // Cache for 7 days
                $redis->setex($cacheKey, 604800, $cacheData);
            } catch (\Exception $e) {
                error_log('Cache storage failed: ' . $e->getMessage());
            }
        }
    }
}
