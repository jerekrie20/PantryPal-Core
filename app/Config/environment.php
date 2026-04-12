<?php
// Load environment variables from .env if present and set error reporting
$__root = dirname(__DIR__, 2);
$__envFile = $__root . '/.env';
if (is_readable($__envFile)) {
    foreach (file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
        $__line = trim($__line);
        if ($__line === '' || str_starts_with($__line, '#')) continue;
        $pos = strpos($__line, '=');
        if ($pos === false) continue;
        $key = trim(substr($__line, 0, $pos));
        $val = trim(substr($__line, $pos + 1));
        if ($val !== '' && (($val[0] === '"' && substr($val, -1) === '"') || ($val[0] === "'" && substr($val, -1) === "'"))) {
            $val = substr($val, 1, -1);
        }
        if ($key !== '') {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

$env = getenv('APP_ENV') ?: 'development';
if ($env === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    // Dev-only: route all error_log() calls to a local file (Herd doesn't expose PHP's default log)
    $__logDir = $__root . '/storage/logs';
    if (!is_dir($__logDir)) { @mkdir($__logDir, 0755, true); }
    ini_set('log_errors', '1');
    ini_set('error_log', $__logDir . '/app.log');
}
