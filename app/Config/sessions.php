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
