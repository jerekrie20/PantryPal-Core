<?php

namespace Models;

use Helpers\Validator;
use PDO;
use PDOException;

class User
{
    protected $db;

    function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    public function register(): true|array
    {
        $validator = new Validator($_POST, $this->db);

        $rules = [
            'username' => [
                'required' => true,
                'min' => 3,
                'max' => 50,
                'unique' => 'users' // This will check the 'username' column in the 'users' table.
            ],
            'email' => [
                'required' => true,
                'email' => true,
                'unique' => 'users' // This will check the 'email' column in the 'users' table.
            ],
            'password' => [
                'required' => true,
                'min' => 8
            ],
            'password_confirm' => [
                'required' => true,
                'matches' => 'password' // This will check if it matches the 'password' field.
            ]
        ];

        $validator->check($rules);

        if ($validator->passed()) {
            return true;
        } else {
            $errors = $validator->errors();
            return $errors;
        }

    }

    public function login($data) {
        $email = $data['email'];
        $password = $data['password'];

        try {
            // It's good practice to select the user's ID as well
            $stmt = $this->db->prepare("SELECT id,username,email,password_hash FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);

            // Fetch the user record from the database
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // 1. Check if a user was found
            // 2. If found, verify the submitted password against the stored hash
            if ($user && password_verify($password, $user['password_hash'])) {
                // Passwords match! Return the user's ID.
                return $user;
            } else {
                // Either the user was not found or the password was incorrect.
                // In either case, return false for security (don't specify which was wrong).
                return false;
            }

        } catch (\PDOException $e) {
            // Log the detailed error for debugging, but don't show it to the user.
            error_log($e->getMessage());
            return false;
        }
    }


    protected function getUser()
    {
    }

    protected function getUserId()
    {

    }

    public function logout()
    {
        session_unset();
        session_destroy();
    }

    public function createUser($data): false|string
    {
        $username = $data['username'];
        $email = $data['email'];
        $password = $data['password'];
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hashedPassword)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':hashedPassword', $hashedPassword);
            $stmt->execute();
            $last_id = $this->db->lastInsertId();
            return $last_id;
        } catch (PDOException $e) {
            //Save error message in the error log
            error_log($e->getMessage());
            return false;
        }
    }

    protected function updateUser()
    {
    }

    function deleteUser()
    {
    }


}