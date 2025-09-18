<?php

/**
 * Handles user-related functionality within the application.
 */

namespace Controllers;

use Helpers\View;
use Models\User;

/**
 * Handles operations related to user management, including CRUD operations.
 */
class UserController
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function index()
    {
        var_dump($_POST);
        die;
    }

    public function create() //Create A new users
    {
        $validation = '';
        $data = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->user->register();
        }

        if ($validation === true) {
            $data = $this->user->createUser($_POST);
        } else {
            return View::render('Pages/register', ['title' => 'Register', 'errors' => $validation, 'input' => $_POST]);
        }

        if($data != false){
            $_SESSION['user_id'] = $data;
            return View::render('Users/dashboard', ['title' => 'Dashboard']);
        }else{
            return View::render('Pages/register', ['title' => 'Register', 'errors' => 'An unexpected error has occurred, please try again.']);
        }


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


}