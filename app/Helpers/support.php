<?php
use Helpers\Vite;

if (!function_exists('vite_tags')) {
    function vite_tags(): string {
        return Vite::tags();
    }
}

// Global HTML escape helper
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        // Figure out the base path of the app (handles /, /public, or deeper subfolders)
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $base = ($scriptDir === '/' || $scriptDir === '') ? '' : $scriptDir;

        // Build the URL path
        $url = $base . '/' . ltrim($path, '/');

        // Optional: cache-busting if the file exists under public/
        $file = APP_ROOT . '/public/' . ltrim($path, '/');
        if (is_file($file)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($file);
        }
        return $url;
    }
}

/** CSRF Token helpers */
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

