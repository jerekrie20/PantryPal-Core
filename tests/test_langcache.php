<?php
/**
 * LangCache Verification Script
 * Run this to verify semantic caching is working end-to-end.
 * Access via: http://pantrypal.local/tests/test_langcache.php
 * Or CLI:     php tests/test_langcache.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT', dirname(__DIR__));
const APP_PATH = APP_ROOT . '/app';

require_once APP_PATH . '/Config/environment.php';

// Web access only when internal tools are explicitly enabled (CLI always allowed)
if (PHP_SAPI !== 'cli' && !filter_var(getenv('INTERNAL_TOOLS_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain');

use Services\AI\LangCacheClient;

echo "=== LangCache Verification ===\n\n";

// 1. Config
echo "1. Configuration...\n";
$enabled = filter_var(getenv('LANGCACHE_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN);
echo "   LANGCACHE_ENABLED: " . ($enabled ? 'true' : 'false') . "\n";
echo "   LANGCACHE_HOST:    " . (getenv('LANGCACHE_HOST') ?: '(missing)') . "\n";
echo "   LANGCACHE_API_KEY: " . (getenv('LANGCACHE_API_KEY') ? 'set' : '(missing)') . "\n";
echo "   LANGCACHE_CACHE_ID:" . (getenv('LANGCACHE_CACHE_ID') ? ' set' : ' (missing)') . "\n\n";

$lc = new LangCacheClient();
if (!$lc->isEnabled()) {
    echo "❌ LangCache is disabled or missing config — fix .env first.\n";
    exit;
}

// 2. Round-trip with the same attributes the app uses
echo "2. Store → search round-trip (with attributes, as the app sends them)...\n";
$attrs = ['userId' => 'langcache-verify'];
$prompt = 'LANGCACHE_VERIFY: how should I store fresh basil?';

$stored = $lc->storeResponse($prompt, 'Keep basil stems in water at room temperature.', $attrs);
echo "   store:  " . ($stored ? "✅ OK" : "❌ FAILED — check error log (likely: attributes not configured on the cache)") . "\n";

$hit = $lc->searchCache($prompt, $attrs);
echo "   exact:  " . ($hit ? "✅ HIT" : "❌ MISS") . "\n";

$semantic = $lc->searchCache('LANGCACHE_VERIFY: best way to keep basil fresh?', $attrs);
echo "   semantic: " . ($semantic
    ? "✅ HIT (similarity " . round($semantic['similarity'] ?? 0, 3) . ")"
    : "❌ MISS") . "\n";

$lc->deleteByAttributes($attrs);
echo "   cleanup: done\n\n";

if ($stored && $hit) {
    echo "✅ LangCache is working. In the app, repeated/similar questions now skip the AI API.\n";
    echo "   Ongoing signal: the /api/ai/chat JSON response includes \"cached\": true on cache hits.\n";
} else {
    echo "❌ LangCache is NOT working. Most common cause: the cache was created without\n";
    echo "   attributes. In the Redis Cloud LangCache console, the cache must have these\n";
    echo "   attributes defined: userId, pageType, pageId. If attributes can't be added to\n";
    echo "   an existing cache, create a new cache with them and update LANGCACHE_CACHE_ID.\n";
    echo "   The exact API error is now logged to the PHP error log.\n";
}
