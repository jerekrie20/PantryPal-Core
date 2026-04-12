#!/usr/bin/env php
<?php
/**
 * prune_cache.php — FatSecret cache pruning script.
 *
 * Deletes rows from fatsecret_cache that are older than 24 hours.
 * Attach to a cron job to run periodically, e.g.:
 *
 *   0 * * * * /usr/bin/php /path/to/pantrypal/prune_cache.php >> /var/log/pantrypal_cache_prune.log 2>&1
 *
 * The script is intentionally self-contained: it bootstraps its own PDO
 * connection from .env rather than relying on the web app's bootstrap.
 *
 * Usage:
 *   php prune_cache.php
 *   php prune_cache.php --dry-run   (counts rows that would be deleted, without deleting)
 */

define('APP_ROOT', __DIR__);

// ---------------------------------------------------------------------------
// Load .env
// ---------------------------------------------------------------------------
$envFile = APP_ROOT . '/.env';
if (!file_exists($envFile)) {
    fwrite(STDERR, "Error: .env file not found at {$envFile}\n");
    exit(1);
}

$env = parse_ini_file($envFile);
if ($env === false) {
    fwrite(STDERR, "Error: Could not parse .env file.\n");
    exit(1);
}
foreach ($env as $k => $v) {
    $_ENV[$k] = $v;
}

// ---------------------------------------------------------------------------
// Parse flags
// ---------------------------------------------------------------------------
$dryRun = in_array('--dry-run', $argv ?? [], true);

// ---------------------------------------------------------------------------
// Connect
// ---------------------------------------------------------------------------
$host    = $_ENV['DB_HOST']    ?? '127.0.0.1';
$port    = $_ENV['DB_PORT']    ?? '3306';
$dbName  = $_ENV['DB_NAME']    ?? '';
$dbUser  = $_ENV['DB_USER']    ?? '';
$dbPass  = $_ENV['DB_PASS']    ?? '';

if ($dbName === '' || $dbUser === '') {
    fwrite(STDERR, "Error: DB_NAME and DB_USER must be set in .env\n");
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Error: DB connection failed — " . $e->getMessage() . "\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Prune
// ---------------------------------------------------------------------------
$timestamp = date('Y-m-d H:i:s');

if ($dryRun) {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS cnt FROM fatsecret_cache WHERE created_at < NOW() - INTERVAL 24 HOUR'
    );
    $stmt->execute();
    $cnt = (int)($stmt->fetch()['cnt'] ?? 0);
    echo "{$timestamp} [dry-run] Would delete {$cnt} expired FatSecret cache row(s).\n";
} else {
    $stmt = $pdo->prepare(
        'DELETE FROM fatsecret_cache WHERE created_at < NOW() - INTERVAL 24 HOUR'
    );
    $stmt->execute();
    $deleted = (int)$stmt->rowCount();
    echo "{$timestamp} Pruned {$deleted} expired FatSecret cache row(s).\n";
}

exit(0);
