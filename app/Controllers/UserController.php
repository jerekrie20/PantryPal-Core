<?php

/**
 * Handles user-related functionality within the application.
 */

namespace Controllers;

use Helpers\View;
use Helpers\Cache;
use JetBrains\PhpStorm\NoReturn;
use Models\User;

/**
 * Handles operations related to user management, including CRUD operations.
 */
class UserController
{
    private User $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function index()
    {
        try {
            // Simple Redis-backed login throttling
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $key = 'pp:auth:fail:' . sha1($email . '|' . $ip);
            $maxAttempts = 5;
            $window = 900; // 15 minutes
            $fails = (int)(Cache::get($key) ?? 0);
            if ($fails >= $maxAttempts) {
                http_response_code(429);
                return View::render('Pages/login', [
                    'title' => 'Login',
                    'error' => 'Too many login attempts. Please try again in a few minutes.',
                    'input' => ['email' => $email]
                ]);
            }
            if ($fails >= 3) { usleep(250000); }

            $userArray = $this->user->login($_POST);

            if (!$userArray) {
                // increment failure counter with TTL
                try { Cache::set($key, $fails + 1, $window); } catch (\Throwable $e) { /* ignore */ }
                return View::render('Pages/login', ['title' => 'Login', 'error' => 'Please try again', 'input' => ['email' => $email]]);
            }

            // Successful login: reset throttle counter
            try { Cache::del($key); } catch (\Throwable $e) { /* ignore */ }

            // Regenerate session ID to prevent fixation
            if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userArray['id'];
            $_SESSION['username'] = $userArray['username'];
            $_SESSION['is_admin'] = !empty($userArray['is_admin']) ? (int)$userArray['is_admin'] : 0;
            // Rotate CSRF token on login
            try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } catch (\Throwable $e) { $_SESSION['csrf_token'] = sha1(uniqid('', true)); }

            header('location: /dashboard');
            exit();
        }catch (\Throwable $e){
            // Log the detailed database error for debugging purposes.
            error_log("Database Error in UserController::index(): " . $e->getMessage());
            header('location: /login');
            exit();
        }
    }

    public function create(): string // Create a new user
    {
        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        $renderRegister = function (array $data = []): string {
            $defaults = ['title' => 'Register', 'input' => $_POST ?? []];
            return View::render('Pages/register', $defaults + $data);
        };

        if (!$isPost) {
            return $renderRegister();
        }

        $validationResult = $this->user->register();

        if ($validationResult !== true) {
            return $renderRegister(['errors' => $validationResult]);
        }
        $userId = $this->user->createUser($_POST);

        $createdUser =  $this->user->find($userId);

        if (!$createdUser) {
            return $renderRegister(['error' => 'An unexpected error has occurred, please try again.']);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $createdUser['id'] ?? null;
        $_SESSION['username'] = $createdUser['username'] ?? null;
        try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } catch (\Throwable $e) { $_SESSION['csrf_token'] = sha1(uniqid('', true)); }

        header('Location: /dashboard');
        exit();
    }

    public function store()
    {
    }

    public function show()
    {
    }

    public function edit()
    {
    }


    public function update()
    {
    }


    public function destroy()
    {
    }

    #[NoReturn]
    public function logout(): void
    {
        $this->user->logout();
        header('location: /');
        exit();
    }


}