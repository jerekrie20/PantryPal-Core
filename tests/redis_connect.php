<?php
require __DIR__ . '/../vendor/autoload.php';

// Load env if your environment loader is not automatically applied here
$envPath = realpath(__DIR__ . '/../.env');
if ($envPath && is_readable($envPath)) {
    // very naive .env loader for local testing (key=value lines only)
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) { $_ENV[trim($parts[0])] = trim($parts[1]); }
    }
}

$host = getenv('REDIS_HOST') ?: ($_ENV['REDIS_HOST'] ?? '127.0.0.1');
$port = (int)(getenv('REDIS_PORT') ?: ($_ENV['REDIS_PORT'] ?? 6379));
$pass = getenv('REDIS_PASSWORD') ?: ($_ENV['REDIS_PASSWORD'] ?? '');
$db   = (int)(getenv('REDIS_DB') ?: ($_ENV['REDIS_DB'] ?? 0));
$tls  = getenv('REDIS_TLS') ?: ($_ENV['REDIS_TLS'] ?? 'false');
$useTls = strtolower((string)$tls) === 'true';

$params = [
    'scheme'   => $useTls ? 'tls' : 'tcp',
    'host'     => $host,
    'port'     => $port,
    'database' => $db,
];
if ($pass) { $params['password'] = $pass; }
$options = [];
if ($useTls) {
    $options['ssl'] = [ 'verify_peer' => false, 'verify_peer_name' => false ];
}

$client = new Predis\Client($params, $options);
$client->connect();
$client->set('pp:redis_connect_test', 'ok', 'EX', 30);
$result = $client->get('pp:redis_connect_test');
echo "Redis connected. set/get => {$result}\n";