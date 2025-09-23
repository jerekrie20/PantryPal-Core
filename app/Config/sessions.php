<?php
// Secure session cookie params and start session
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax', // or 'Strict' for highly sensitive apps
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// CSRF token generation (per-session)
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (\Throwable $e) {
        $_SESSION['csrf_token'] = sha1(uniqid('', true));
    }
}
