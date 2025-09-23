<?php

namespace Middleware;

class CsrfMiddleware
{
    /**
     * For unsafe HTTP methods (POST only in this router), validate CSRF token.
     * Accept token via form field 'csrf_token' or header 'X-CSRF-Token'.
     */
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'POST') {
            return; // only enforce on POST (router supports GET/POST)
        }
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($token) || $token === '' || !hash_equals((string)$sessionToken, (string)$token)) {
            http_response_code(419);
            echo 'Invalid CSRF token';
            exit;
        }
    }
}
