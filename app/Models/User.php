<?php

namespace Models;

use Helpers\Validator;
use PDO;
use PDOException;

class User
{
    protected ?PDO $db = null;

    function __construct()
    {
        global $conn;
        $this->db = $conn instanceof PDO ? $conn : null;
        if ($this->db === null) {
            throw new \RuntimeException('Database connection not initialized.');
        }
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
            return $validator->errors();
        }

    }

    public function login($data)
    {
        $email = $data['email'];
        $password = $data['password'];

        // It's good practice to select the user's ID as well
        $stmt = $this->db->prepare("SELECT id,username,email,password_hash,is_admin FROM users WHERE email = :email");

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


    }


    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    protected function getUserId()
    {

    }

    public function logout()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
        // Regenerate session ID to invalidate any fixation remnants
        @session_regenerate_id(true);
        // Clear all session data
        $_SESSION = [];
        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        @session_destroy();
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
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            //Save error message in the error log
            error_log($e->getMessage());
            return false;
        }
    }

    public function listAll(int $limit = 100): array
    {
        $st = $this->db->prepare("SELECT id, username, email, IFNULL(is_admin, 0) AS is_admin, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT :lim");
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Admin: paged/filterable list of users */
    public function listPaged(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = [];
        $params = [];
        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($q !== '') {
            $where[] = '(username LIKE :q OR email LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        if (isset($filters['is_admin']) && $filters['is_admin'] !== '') {
            $where[] = 'IFNULL(is_admin,0) = :adm';
            $params[':adm'] = (int)$filters['is_admin'] ? 1 : 0;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count
        $stc = $this->db->prepare("SELECT COUNT(*) FROM users $whereSql");
        foreach ($params as $k => $v) {
            $stc->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stc->execute();
        $total = (int)$stc->fetchColumn();

        // Page
        $perPage = max(1, min(100, (int)$perPage));
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT id, username, email, IFNULL(is_admin, 0) AS is_admin, created_at
                FROM users $whereSql
                ORDER BY created_at DESC, id DESC
                LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalPages = (int)max(1, ceil($total / max(1, $perPage)));
        return [
            'rows' => $rows,
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'totalItems' => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }

    protected function updateUser()
    {
    }

    public function toggleAdmin(int $id): bool
    {
        // Flip is_admin (NULL treated as 0)
        $sql = "UPDATE users SET is_admin = IFNULL(1 - is_admin, 1) WHERE id = :id";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        return $st->execute();
    }

    public function deleteById(int $id): bool
    {
        $st = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        return $st->execute();
    }

}