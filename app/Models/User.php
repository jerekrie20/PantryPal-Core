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


    protected function getUser()
    {
    }

    protected function getUserId()
    {

    }

    protected function logout()
    {
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