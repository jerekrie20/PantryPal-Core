<?php
// Security headers and CSP
$env = getenv('APP_ENV') ?: 'development';
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

if ($env === 'production') {
    // Enforce HTTPS long-term (enable only when HTTPS is fully configured site-wide)
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' https: data:; connect-src 'self' https://production.suggestic.com; font-src 'self' data:; frame-ancestors 'none'");
} else {
    $vite = getenv('VITE_DEV_SERVER') ?: 'https://localhost:5173';

    // Derive the proper WS/WSS URL for HMR
    $p = parse_url($vite);
    $host = $p['host'] ?? 'localhost';
    $port = isset($p['port']) ? ':' . $p['port'] : ( ($p['scheme'] ?? 'https') === 'https' ? ':5173' : ':5173');
    $ws  = (($p['scheme'] ?? 'https') === 'https' ? 'wss://' : 'ws://') . $host . $port;

    // In dev, Vite may require 'unsafe-eval' for source maps
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$vite}; " .
        "style-src 'self' 'unsafe-inline'; " .
        "img-src 'self' https: data:; " .
        "connect-src 'self' {$vite} {$ws} https://production.suggestic.com; " .
        "font-src 'self' data:; " .
        "frame-ancestors 'none'"
    );
}