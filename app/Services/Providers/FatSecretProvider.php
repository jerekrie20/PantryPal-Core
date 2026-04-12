<?php

namespace Services\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Helpers\Cache;

/**
 * FatSecret Platform API client — food search and retrieval.
 *
 * ToS compliance:
 *   Full JSON responses are cached in Redis (24-hour TTL only).
 *   Nutritional data is NEVER written to the permanent ingredients/products tables.
 *
 * Auth: OAuth 2.0 client_credentials flow.
 *   Token endpoint : https://oauth.fatsecret.com/connect/token
 *   API base       : https://platform.fatsecret.com/rest/
 *   Env vars       : FATSECRET_CLIENT_ID, FATSECRET_CLIENT_SECRET
 */
class FatSecretProvider
{
    private const TOKEN_URL       = 'https://oauth.fatsecret.com/connect/token';
    private const API_BASE        = 'https://platform.fatsecret.com/rest/';
    private const TOKEN_CACHE_KEY = 'fatsecret:__oauth_token__';
    private const CACHE_TTL       = 86400; // 24 hours

    private string $clientId;
    private string $clientSecret;
    private Client $http;

    public function __construct(
        ?string $clientId     = null,
        ?string $clientSecret = null,
        ?Client $http         = null
    ) {
        $this->clientId     = $clientId     ?? ($_ENV['FATSECRET_CLIENT_ID']     ?? '');
        $this->clientSecret = $clientSecret ?? ($_ENV['FATSECRET_CLIENT_SECRET'] ?? '');
        $this->http = $http ?? new Client(['timeout' => 8.0]);
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    // -------------------------------------------------------------------------
    // Public API methods
    // -------------------------------------------------------------------------

    /**
     * Search foods via foods/search/v1.
     * Returns normalized results; full response cached in Redis for 24 hours.
     *
     * @return array<int, array{api_id:string,name:string,brand:string|null,type:string,url:string|null,source:string}>
     */
    public function searchFoods(string $query, int $maxResults = 10): array
    {
        if (!$this->isConfigured() || trim($query) === '') {
            return [];
        }
        $params = [
            'search_expression' => $query,
            'max_results'       => max(1, min(50, $maxResults)),
            'format'            => 'json',
        ];
        $data = $this->cachedCall('foods.search.v1', self::API_BASE . 'foods/search/v1', $params);
        if (!$data) {
            return [];
        }
        $foods = $data['foods']['food'] ?? [];
        // API returns a single object when there is exactly one result
        if (isset($foods['food_id'])) {
            $foods = [$foods];
        }
        $out = [];
        foreach ($foods as $f) {
            $out[] = [
                'api_id' => (string)($f['food_id'] ?? ''),
                'name'   => (string)($f['food_name'] ?? ''),
                'brand'  => isset($f['brand_name']) && $f['brand_name'] !== '' ? (string)$f['brand_name'] : null,
                'type'   => (string)($f['food_type'] ?? 'Generic'), // 'Generic' or 'Brand'
                'url'    => isset($f['food_url']) && $f['food_url'] !== '' ? (string)$f['food_url'] : null,
                'source' => 'fatsecret',
            ];
        }
        return $out;
    }

    /**
     * Fetch full food details via food/v5.
     * Returns the raw decoded response array (cached in Redis); caller must not store permanently.
     */
    public function getFood(int|string $foodId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }
        $params = [
            'food_id' => (string)$foodId,
            'format'  => 'json',
        ];
        return $this->cachedCall('food.get.v5', self::API_BASE . 'food/v5', $params);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function cacheKey(string $endpoint, array $params): string
    {
        ksort($params);
        return 'fatsecret:' . hash('sha256', $endpoint . json_encode($params));
    }

    /**
     * Check Redis → on miss call the API → cache result → return decoded data.
     */
    private function cachedCall(string $endpoint, string $url, array $params): ?array
    {
        $key = $this->cacheKey($endpoint, $params);
        $hit = Cache::get($key);
        if (is_array($hit)) {
            return $hit;
        }
        $data = $this->callApi($url, $params);
        if ($data !== null) {
            Cache::set($key, $data, self::CACHE_TTL);
        }
        return $data;
    }

    /**
     * Execute a GET request to a versioned FatSecret REST endpoint.
     */
    private function callApi(string $url, array $params): ?array
    {
        $token = $this->getBearerToken();
        if ($token === null) {
            return null;
        }
        try {
            $resp = $this->http->get($url, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query'   => $params,
            ]);
            $body    = (string)$resp->getBody();
            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                error_log('FatSecretProvider: non-JSON response url=' . $url . ' body=' . substr($body, 0, 500));
                return null;
            }
            if (isset($decoded['error'])) {
                error_log('FatSecretProvider API error: ' . json_encode($decoded['error']));
                return null;
            }
            return $decoded;
        } catch (GuzzleException $e) {
            error_log('FatSecretProvider HTTP error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Return a valid Bearer token, fetching a new one via client_credentials if needed.
     * Stored in Redis with a TTL matching expires_in.
     */
    private function getBearerToken(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_array($cached) && isset($cached['access_token'], $cached['expires_at'])) {
            if ($cached['expires_at'] > time() + 60) {
                return (string)$cached['access_token'];
            }
        }
        try {
            $resp = $this->http->post(self::TOKEN_URL, [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope'         => 'basic',
                ],
            ]);
            $data = json_decode((string)$resp->getBody(), true);
            if (!isset($data['access_token'])) {
                error_log('FatSecretProvider: token response missing access_token');
                return null;
            }
            $expiresIn = (int)($data['expires_in'] ?? 86400);
            $payload   = [
                'access_token' => $data['access_token'],
                'expires_at'   => time() + $expiresIn,
            ];
            Cache::set(self::TOKEN_CACHE_KEY, $payload, max(60, $expiresIn - 60));
            return (string)$data['access_token'];
        } catch (GuzzleException $e) {
            error_log('FatSecretProvider token fetch error: ' . $e->getMessage());
            return null;
        }
    }
}
