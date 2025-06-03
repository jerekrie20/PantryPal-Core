<?php
/**
 * PantryPal Application Entry Point
 * 
 * This file serves as the entry point for the PantryPal application.
 * It bootstraps the application by loading configuration, setting up
 * error handling, and routing requests to the appropriate controllers.
 */

// Define the application root directory
define('APP_ROOT', dirname(__DIR__));

// Load the autoloader (assuming Composer is used)
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require APP_ROOT . '/vendor/autoload.php';
}

// Determine if we're in development mode
$isDev = file_exists(APP_ROOT . '/node_modules');

// Function to include Vite assets
function viteAssets() {
    global $isDev;

    if ($isDev) {
        // In development, include the Vite client and use the dev server
        // Use a timestamp to prevent caching issues
        $timestamp = time();
        echo '<script type="module" src="https://localhost:5173/pantrypal_core/@vite/client?' . $timestamp . '"></script>';
        echo '<script type="module" src="https://localhost:5173/pantrypal_core/src/js/main.js?' . $timestamp . '"></script>';
    } else {
        // In production, include the built assets
        $manifestPath = APP_ROOT . '/dist/manifest.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);

            if (isset($manifest['src/js/main.js']) && isset($manifest['src/js/main.js']['file'])) {
                // CSS is imported in JS, so Vite automatically injects the CSS
                echo '<script type="module" src="/pantrypal_core/dist/' . $manifest['src/js/main.js']['file'] . '"></script>';
            }
        }
    }
}

// Simple router (to be replaced with a more robust solution)
$request = $_SERVER['REQUEST_URI'];
$basePath = '/pantrypal_core/';

// Remove the base path from the request
if (strpos($request, $basePath) === 0) {
    $request = substr($request, strlen($basePath));
}

// Remove query string
if (($pos = strpos($request, '?')) !== false) {
    $request = substr($request, 0, $pos);
}

// Default to index if no path is specified
if (empty($request) || $request === '/') {
    $request = 'home';
}

// Basic routing
switch ($request) {
    case 'home':
        require APP_ROOT . '/app/Views/home.php';
        break;
    default:
        // If no route matches, show a 404 page
        header("HTTP/1.0 404 Not Found");
        require APP_ROOT . '/app/Views/404.php';
        break;
}