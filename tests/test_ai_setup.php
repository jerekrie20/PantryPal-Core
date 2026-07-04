<?php
/**
 * AI Setup Test Script
 * Run this to verify your AI assistant configuration
 * Access via: http://pantrypal.local/tests/test_ai_setup.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT', dirname(__DIR__));
const APP_PATH = APP_ROOT . '/app';

// Load environment
require_once APP_PATH . '/Config/environment.php';

// Web access only when internal tools are explicitly enabled (CLI always allowed)
if (PHP_SAPI !== 'cli' && !filter_var(getenv('INTERNAL_TOOLS_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain');

echo "=== AI Assistant Configuration Test ===\n\n";

// Test 1: Check AI provider configuration
echo "1. Checking AI provider configuration...\n";
$aiProvider = getenv('AI_PROVIDER') ?: '(auto-detect)';
$geminiKey = getenv('GEMINI_API_KEY') ?: '';
$anthropicKey = getenv('ANTHROPIC_API_KEY') ?: '';
$apiKey = $geminiKey ?: $anthropicKey;

echo "   AI_PROVIDER: {$aiProvider}\n";
if (!empty($geminiKey)) {
    $masked = substr($geminiKey, 0, 7) . '...' . substr($geminiKey, -4);
    echo "   ✅ GEMINI_API_KEY found ($masked)\n";
}
if (!empty($anthropicKey)) {
    $masked = substr($anthropicKey, 0, 7) . '...' . substr($anthropicKey, -4);
    echo "   ✅ ANTHROPIC_API_KEY found ($masked)\n";
}
if (empty($apiKey)) {
    echo "   ❌ FAIL: No AI API key found in environment\n";
    echo "   Fix: Add GEMINI_API_KEY (free) or ANTHROPIC_API_KEY to your .env file\n";
}
echo "\n";

// Test 2: Check Guzzle HTTP client
echo "2. Checking GuzzleHttp\\Client...\n";
if (class_exists('GuzzleHttp\\Client')) {
    echo "   ✅ PASS: Guzzle HTTP client available\n\n";
} else {
    echo "   ❌ FAIL: Guzzle HTTP client not found\n";
    echo "   Fix: Run 'composer install'\n\n";
}

// Test 3: Check Redis connection (for rate limiting)
echo "3. Checking Redis connection...\n";
try {
    if (class_exists('Predis\\Client')) {
        $redis = new \Predis\Client([
            'scheme' => getenv('REDIS_TLS') === 'true' ? 'tls' : 'tcp',
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => (int)(getenv('REDIS_PORT') ?: 6379),
        ]);
        $redis->ping();
        echo "   ✅ PASS: Redis connected\n\n";
    } else {
        echo "   ⚠️  WARN: Redis not available (rate limiting will not work)\n";
        echo "   Note: Rate limiting requires Redis\n\n";
    }
} catch (Exception $e) {
    echo "   ⚠️  WARN: Redis connection failed: " . $e->getMessage() . "\n";
    echo "   Note: Rate limiting will not work without Redis\n\n";
}

// Test 4: Check database connection
echo "4. Checking database connection...\n";
try {
    require_once APP_PATH . '/Database/connection.php';
    if (isset($conn) && $conn instanceof PDO) {
        echo "   ✅ PASS: Database connected\n\n";
    } else {
        echo "   ❌ FAIL: Database connection not available\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ FAIL: " . $e->getMessage() . "\n\n";
}

// Test 5: Test AI service instantiation
echo "5. Testing CookingAssistant service...\n";
try {
    require_once APP_PATH . '/Services/AI/CookingAssistant.php';
    $assistant = new \Services\AI\CookingAssistant(1); // Test with user ID 1
    echo "   ✅ PASS: CookingAssistant instantiated successfully\n\n";
} catch (Exception $e) {
    echo "   ❌ FAIL: " . $e->getMessage() . "\n";
    echo "   Error: " . $e->getTraceAsString() . "\n\n";
}

// Test 6: Check if routes are defined
echo "6. Checking route configuration...\n";
$routesFile = APP_PATH . '/routes/web.php';
$routesContent = file_get_contents($routesFile);
if (strpos($routesContent, 'AIController') !== false && strpos($routesContent, '/api/ai/chat') !== false) {
    echo "   ✅ PASS: AI routes configured\n\n";
} else {
    echo "   ❌ FAIL: AI routes not found in web.php\n\n";
}

// Test 7: Check if files exist
echo "7. Checking required files...\n";
$files = [
    APP_PATH . '/Services/AI/CookingAssistant.php',
    APP_PATH . '/Controllers/AIController.php',
    APP_PATH . '/Views/Components/ai_chat.php',
];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✅ " . basename(dirname($file)) . "/" . basename($file) . "\n";
    } else {
        echo "   ❌ " . basename(dirname($file)) . "/" . basename($file) . " (missing)\n";
    }
}

echo "\n=== Test Complete ===\n\n";

if (empty($apiKey)) {
    echo "⚠️  ACTION REQUIRED:\n";
    echo "1. Copy .env.example to .env\n";
    echo "2. Get a free Gemini key from: https://aistudio.google.com/apikey\n";
    echo "   (or a paid Anthropic key from: https://console.anthropic.com/)\n";
    echo "3. Add GEMINI_API_KEY=your_key_here (or ANTHROPIC_API_KEY=...) to .env\n";
    echo "4. Restart your web server\n";
} else {
    echo "✅ Configuration looks good!\n";
    echo "\nNext steps:\n";
    echo "1. Log in to your dashboard\n";
    echo "2. Click the 'AI Chef' button (bottom-right)\n";
    echo "3. Try asking: 'How many cups is 250g of flour?'\n";
}
