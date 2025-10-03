<?php

namespace Middleware;

class AdminMiddleware
{
    public function handle()
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) { @session_start(); }
        $isAdmin = !empty($_SESSION['is_admin']);
        if (!$isAdmin) {
            http_response_code(403);
            require VIEW_PATH . '/Pages/403.php';
            exit; // stop further handling when forbidden
        }
        // Router does not use next() chaining; simply allow request to proceed
        return;
    }
}
