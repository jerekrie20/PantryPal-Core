<?php

namespace Middleware;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Checks if a user is authenticated by verifying that 'user_id' is set in the session.
     * If the user is not authenticated, they are redirected to the login page.
     */
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            // User is not logged in, redirect to the login page.
            header('Location: /login');
            exit();
        }
    }
}