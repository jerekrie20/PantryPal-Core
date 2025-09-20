<?php

/**
 * Handles user-related functionality within the application.
 */

namespace Controllers;

use Helpers\View;
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
            $userArray = $this->user->login($_POST);

            if (!$userArray) {
                return View::render('Pages/login', ['title' => 'Login', 'error' => 'Please try again']);
            }

            $_SESSION['user_id'] = $userArray['id'];
            $_SESSION['username'] = $userArray['username'];

            header('location: /dashboard');
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

        $_SESSION['user_id'] = $createdUser['id'] ?? null;
        $_SESSION['username'] = $createdUser['username'] ?? null;

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