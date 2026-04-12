<?php

namespace Models;

/**
 * PDO model for the fatsecret_cache table.
 *
 * FatSecret ToS forbids permanent storage of nutritional data or recipe instructions.
 * This table holds full JSON responses for up to 24 hours.
 * The reserved endpoint '__oauth_token__' is also stored here with an explicit
 * expires_at embedded in the JSON, so Bearer tokens are not re-fetched on every call.
 */
class FatSecretCache
{
    private \PDO $pdo;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? $GLOBALS['conn'];
    }

    /**
     * Build a deterministic cache key from an endpoint name and its parameters.
     * OAuth-specific keys (oauth_*, access_token) are excluded so the key
     * represents only the logical request.
     */
    public function makeKey(string $endpoint, array $params): string
    {
        // Strip transient OAuth fields before hashing
        $clean = array_filter($params, static function (string $k): bool {
            return !str_starts_with($k, 'oauth_') && $k !== 'access_token';
        }, ARRAY_FILTER_USE_KEY);
        ksort($clean);
        return hash('sha256', $endpoint . json_encode($clean, JSON_THROW_ON_ERROR));
    }

    /**
     * Retrieve a cached response. Returns the decoded array, or null on miss/expiry.
     * Rows older than 24 hours are treated as expired (prune_cache.php removes them).
     */
    public function get(string $cacheKey): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT response_json, created_at FROM fatsecret_cache
             WHERE cache_key = :key AND created_at >= NOW() - INTERVAL 24 HOUR
             LIMIT 1'
        );
        $stmt->execute([':key' => $cacheKey]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $decoded = json_decode((string)$row['response_json'], true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Store (or refresh) a response. Uses INSERT … ON DUPLICATE KEY UPDATE so
     * re-fetching the same endpoint overwrites the previous entry and resets created_at.
     */
    public function set(string $cacheKey, string $endpoint, array $data): void
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $stmt = $this->pdo->prepare(
            'INSERT INTO fatsecret_cache (cache_key, endpoint, response_json, created_at)
             VALUES (:key, :endpoint, :json, NOW())
             ON DUPLICATE KEY UPDATE
               endpoint      = VALUES(endpoint),
               response_json = VALUES(response_json),
               created_at    = NOW()'
        );
        $stmt->execute([
            ':key'      => $cacheKey,
            ':endpoint' => $endpoint,
            ':json'     => $json,
        ]);
    }

    /**
     * Delete all rows older than 24 hours.
     * Called by prune_cache.php (cron) and can also be called inline.
     *
     * @return int Number of rows deleted.
     */
    public static function prune(\PDO $pdo): int
    {
        $stmt = $pdo->prepare(
            'DELETE FROM fatsecret_cache WHERE created_at < NOW() - INTERVAL 24 HOUR'
        );
        $stmt->execute();
        return (int)$stmt->rowCount();
    }
}
