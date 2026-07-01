<?php
/**
 * Internal Tools Routes
 * Development and admin diagnostic endpoints
 * These routes are only accessible in non-production environments
 */

global $router;

// Learning/Theme Pages (Admin Only)
$router->get('/__internal/learning', function () {
    $enabled = getenv('INTERNAL_TOOLS_ENABLED') ?: '';
    if (strtolower((string)$enabled) !== 'true') { 
        http_response_code(404); 
        require VIEW_PATH . '/Pages/404.php'; 
        return; 
    }
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isAdmin = !empty($_SESSION['is_admin']);
    if (!$isAdmin) { 
        http_response_code(403); 
        require VIEW_PATH . '/Pages/403.php'; 
        return; 
    }
    require VIEW_PATH . '/Learning/overview.html';
});

$router->get('/__internal/styleguide', function () {
    $enabled = getenv('INTERNAL_TOOLS_ENABLED') ?: '';
    if (strtolower((string)$enabled) !== 'true') {
        http_response_code(404);
        require VIEW_PATH . '/Pages/404.php';
        return;
    }
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['is_admin'])) {
        http_response_code(403);
        require VIEW_PATH . '/Pages/403.php';
        return;
    }
    $title = 'Style Guide';
    require VIEW_PATH . '/Pages/styleguide.php';
});

$router->get('/__internal/theme', function () {
    $enabled = getenv('INTERNAL_TOOLS_ENABLED') ?: '';
    if (strtolower((string)$enabled) !== 'true') { 
        http_response_code(404); 
        require VIEW_PATH . '/Pages/404.php'; 
        return; 
    }
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isAdmin = !empty($_SESSION['is_admin']);
    if (!$isAdmin) { 
        http_response_code(403); 
        require VIEW_PATH . '/Pages/403.php'; 
        return; 
    }
    require VIEW_PATH . '/Learning/theme.php';
});

// Redis Connection Test (Admin Only, Non-Production)
$router->get('/__internal/redis-test', function () {
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') { 
        http_response_code(404); 
        echo 'Not Found'; 
        return; 
    }
    $enabled = getenv('INTERNAL_TOOLS_ENABLED') ?: '';
    if (strtolower((string)$enabled) !== 'true') { 
        http_response_code(404); 
        echo 'Not Found'; 
        return; 
    }
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isAdmin = !empty($_SESSION['is_admin']);
    if (!$isAdmin) { 
        http_response_code(403); 
        echo 'Forbidden'; 
        return; 
    }
    
    header('Content-Type: text/plain');
    
    try {
        $ready = \Helpers\Cache::ready();
        if (!$ready) {
            echo "redis: not connected\n";
            echo 'Predis\\Client: ' . (class_exists('Predis\\Client') ? 'yes' : 'no') . "\n";
            echo 'ext-redis: ' . (class_exists('Redis') ? 'yes' : 'no') . "\n";
            return;
        }
        $k = 'pp:test:' . bin2hex(random_bytes(4));
        \Helpers\Cache::set($k, 'bar', 30);
        $v = \Helpers\Cache::get($k);
        echo "set/get => $v\n";
    } catch (\Throwable $e) {
        echo 'error: ' . $e->getMessage();
    }
});

// AI Assistant Configuration Test (Non-Production)
$router->get('/__internal/ai-test', function () {
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') { 
        http_response_code(404); 
        echo 'Not Found'; 
        return; 
    }
    
    header('Content-Type: text/plain; charset=utf-8');

    echo "=== AI Assistant Configuration Test ===\n\n";

    // Test 1: API key
    echo "1. ANTHROPIC_API_KEY...\n";
    $apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
    if (empty($apiKey)) {
        echo "   ❌ FAIL: Not found\n";
        echo "   Fix: Add to .env file\n\n";
    } else {
        $masked = substr($apiKey, 0, 10) . '...' . substr($apiKey, -4);
        echo "   ✅ PASS: Found ($masked)\n\n";
    }

    // Test 2: Guzzle
    echo "2. GuzzleHttp Client...\n";
    echo "   " . (class_exists('GuzzleHttp\\Client') ? '✅ PASS' : '❌ FAIL: Run composer install') . "\n\n";

    // Test 3: Redis
    echo "3. Redis...\n";
    try {
        global $redis;
        if ($redis && method_exists($redis, 'ping')) {
            $redis->ping();
            echo "   ✅ PASS: Connected\n\n";
        } else {
            echo "   ⚠️  WARN: Not available (rate limiting won't persist)\n\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️  WARN: " . $e->getMessage() . "\n\n";
    }

    // Test 4: Database
    echo "4. Database...\n";
    global $conn;
    echo "   " . (isset($conn) && $conn instanceof PDO ? '✅ PASS' : '❌ FAIL') . "\n\n";

    // Test 5: AI Service
    echo "5. CookingAssistant...\n";
    try {
        $test = new \Services\AI\CookingAssistant(1);
        echo "   ✅ PASS: Can instantiate\n\n";
    } catch (Exception $e) {
        echo "   ❌ FAIL: " . $e->getMessage() . "\n\n";
    }

    // Test 6: Files
    echo "6. Required files...\n";
    $files = [
        'AIController.php' => APP_PATH . '/Controllers/AIController.php',
        'CookingAssistant.php' => APP_PATH . '/Services/AI/CookingAssistant.php',
        'ai_chat.php' => APP_PATH . '/Views/Components/ai_chat.php',
    ];
    foreach ($files as $name => $path) {
        echo "   " . (file_exists($path) ? '✅' : '❌') . " $name\n";
    }

    // Test 7: Routes
    echo "\n7. API Routes...\n";
    echo "   Check POST /api/ai/chat is accessible\n";
    echo "   Check GET /api/ai/usage is accessible\n";

    echo "\n=== Summary ===\n";
    if (empty($apiKey)) {
        echo "❌ CRITICAL: Add ANTHROPIC_API_KEY to .env\n";
        echo "   Get key from: https://console.anthropic.com/\n";
    } else {
        echo "✅ Configuration complete!\n\n";
        echo "Next steps:\n";
        echo "1. Go to http://pantrypal.local/dashboard\n";
        echo "2. Click 'AI Chef' button (bottom-right corner)\n";
        echo "3. Try: 'How many cups is 250g of flour?'\n";
    }
});

// List Available Claude Models
$router->get('/__internal/ai-models', function () {
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') {
        http_response_code(404);
        echo 'Not Found';
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');

    echo "=== Available Claude Models ===\n\n";

    $apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
    if (empty($apiKey)) {
        echo "❌ ANTHROPIC_API_KEY not set\n";
        return;
    }

    try {
        $client = new \GuzzleHttp\Client(['timeout' => 10]);
        $response = $client->get('https://api.anthropic.com/v1/models', [
            'headers' => [
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['data']) && is_array($data['data'])) {
            echo "Found " . count($data['data']) . " models:\n\n";

            foreach ($data['data'] as $model) {
                $id = $model['id'] ?? 'unknown';
                $name = $model['display_name'] ?? 'Unknown';
                $created = $model['created_at'] ?? 'unknown';

                echo "ID: $id\n";
                echo "Name: $name\n";
                echo "Created: $created\n";
                echo str_repeat('-', 60) . "\n";
            }

            // Show recommendation
            if (!empty($data['data'])) {
                $recommended = $data['data'][0]['id'] ?? null;
                echo "\n✅ Recommended model (latest): $recommended\n";
                echo "\nUpdate CookingAssistant.php line 192 to:\n";
                echo "'model' => '$recommended',\n";
            }
        } else {
            echo "No models found in response\n";
            echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
});

// Environment Info (Non-Production)
$router->get('/__internal/env-info', function () {
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') { 
        http_response_code(404); 
        echo 'Not Found'; 
        return; 
    }
    
    header('Content-Type: text/plain; charset=utf-8');
    
    echo "=== Environment Information ===\n\n";
    
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "APP_ENV: " . ($env) . "\n";
    echo "APP_DEBUG: " . (getenv('APP_DEBUG') ?: 'not set') . "\n\n";
    
    echo "Loaded Extensions:\n";
    $exts = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'json', 'openssl'];
    foreach ($exts as $ext) {
        echo "  " . ($ext) . ": " . (extension_loaded($ext) ? '✅' : '❌') . "\n";
    }
    
    echo "\nComposer Packages:\n";
    $packages = ['GuzzleHttp\\Client', 'Predis\\Client'];
    foreach ($packages as $class) {
        echo "  " . $class . ": " . (class_exists($class) ? '✅' : '❌') . "\n";
    }
    
    echo "\nPaths:\n";
    echo "  APP_ROOT: " . APP_ROOT . "\n";
    echo "  APP_PATH: " . APP_PATH . "\n";
    echo "  VIEW_PATH: " . VIEW_PATH . "\n";
});

// phpinfo() (Non-Production, requires confirmation)
$router->get('/__internal/phpinfo', function () {
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') { 
        http_response_code(404); 
        echo 'Not Found'; 
        return; 
    }
    
    $confirm = $_GET['confirm'] ?? '';
    if ($confirm !== 'yes') {
        echo '<h1>PHP Info</h1>';
        echo '<p>This will display sensitive server information.</p>';
        echo '<p><a href="/__internal/phpinfo?confirm=yes">Click here to confirm</a></p>';
        return;
    }
    
    phpinfo();
});
