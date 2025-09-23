<?php

namespace Helpers;

use Predis\Client;
use Redis;

/**
 * Minimal Redis-backed cache with graceful fallback if Redis is unavailable.
 * Supports either ext-redis or predis/predis.
 */
class Cache
{
    /** @var Redis|Client|null */
    private static Redis|Client|null $client = null;
    private static bool $initialized = false;
    private static bool $isPredis = false;

    private static function init(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;
        $url = getenv('REDIS_URL') ?: '';
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int)(getenv('REDIS_PORT') ?: 6379);
        $pass = getenv('REDIS_PASSWORD') ?: '';
        $db = (int)(getenv('REDIS_DB') ?: 0);
        $tlsEnv = getenv('REDIS_TLS');
        $username = getenv('REDIS_USERNAME') ?: '';
        // Respect explicit REDIS_TLS if provided; otherwise auto-detect for Redis Cloud hosts
        if ($tlsEnv !== false && $tlsEnv !== '') {
            $useTls = strtolower((string)$tlsEnv) === 'true';
        } else {
            $useTls = str_contains((string)$host, 'redis-cloud.com') || str_contains((string)$host, 'redns.redis-cloud.com');
        }

        try {
            if ($url) {
                // Parse redis[s]://[user[:pass]@]host:port/db
                $parts = parse_url($url);
                if (!empty($parts['host'])) $host = $parts['host'];
                if (!empty($parts['port'])) $port = (int)$parts['port'];
                if (!empty($parts['user'])) $username = $parts['user'];
                if (!empty($parts['pass'])) $pass = $parts['pass'];
                if (!empty($parts['path'])) {
                    $dbPath = ltrim($parts['path'], '/');
                    if ($dbPath !== '') $db = (int)$dbPath;
                }
                $scheme = $parts['scheme'] ?? '';
                if ($scheme === 'rediss') $useTls = true;
            }

            if (class_exists('Redis') && !$useTls) {
                // Prefer ext-redis when TLS is not required (simpler setup)
                $redis = new Redis();
                $redis->connect($host, $port, 1.0);
                if ($pass) {
                    // ext-redis supports array auth for ACL usernames in newer versions
                    if ($username !== '') { $redis->auth([$username, $pass]); }
                    else { $redis->auth($pass); }
                }
                if ($db) { $redis->select($db); }
                self::$client = $redis;
                self::$isPredis = false;
                return;
            }

            // Fallback to Predis (supports TLS easily)
            if (class_exists('Predis\\Client')) {
                if ($url) {
                    // Allow full URL (redis:// or rediss://)
                    $params = $url;
                } else {
                    $params = [
                        'scheme'   => $useTls ? 'tls' : 'tcp',
                        'host'     => $host,
                        'port'     => $port,
                        'database' => $db,
                    ];
                    if ($pass) { $params['password'] = $pass; }
                    if ($username !== '') { $params['username'] = $username; }
                }
                // Heuristic: Redis Cloud typically requires ACL username 'default'
                if ($useTls && $pass && empty($username) && (str_contains((string)$host, 'redis-cloud.com') || str_contains((string)$host, 'redns.redis-cloud.com'))) {
                    if (is_array($params)) { $params['username'] = 'default'; }
                }
                $options = [];
                if ($useTls) {
                    // Determine verification settings from env; default to verify in production
                    $appEnv = getenv('APP_ENV') ?: 'development';
                    $verifyPeer = strtolower((string)(getenv('REDIS_VERIFY_PEER') ?: ($appEnv === 'production' ? 'true' : 'false'))) === 'true';
                    $verifyPeerName = strtolower((string)(getenv('REDIS_VERIFY_PEER_NAME') ?: ($verifyPeer ? 'true' : 'false'))) === 'true';
                    $cafile = getenv('REDIS_CAFILE') ?: '';
                    $capath = getenv('REDIS_CAPATH') ?: '';
                    $ssl = [
                        'verify_peer' => $verifyPeer,
                        'verify_peer_name' => $verifyPeerName,
                    ];
                    if ($verifyPeer) {
                        if ($cafile !== '') { $ssl['cafile'] = $cafile; }
                        if ($capath !== '') { $ssl['capath'] = $capath; }
                    }
                    $options['ssl'] = $ssl;
                }
                $predis = new Client($params, $options);
                // Smoke test connection
                $predis->connect();
                self::$client = $predis;
                self::$isPredis = true;
                return;
            }
        } catch (\Throwable $e) {
            // fall through to no-cache
        }
        self::$client = null; // fail open (no cache)
    }

    public static function ready(): bool
    {
        self::init();
        if (!self::$client) return false;
        if (self::$isPredis) return self::$client instanceof Client;
        return self::$client instanceof Redis;
    }

    public static function get(string $key): mixed
    {
        self::init();
        if (!self::ready()) return null;
        try {
            $raw = self::$client->get($key);
        } catch (\Throwable $e) {
            return null;
        }
        if ($raw === false || $raw === null) return null;
        $val = @json_decode($raw, true);
        return $val === null && $raw !== 'null' ? $raw : $val;
    }

    public static function set(string $key, mixed $value, int $ttlSeconds = 300): void
    {
        self::init();
        if (!self::ready()) return;
        $payload = is_string($value) ? $value : json_encode($value);
        try {
            if ($ttlSeconds > 0) {
                self::$client->setex($key, $ttlSeconds, $payload);
            } else {
                self::$client->set($key, $payload);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function del(string $key): void
    {
        self::init();
        if (!self::ready()) return;
        try { self::$client->del($key); } catch (\Throwable $e) { /* ignore */ }
    }
}
