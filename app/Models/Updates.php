<?php

namespace Models;

use PDO;

class Updates
{
    protected ?PDO $db = null;

    public function __construct()
    {
        global $conn;
        $this->db = $conn instanceof PDO ? $conn : null;
        if ($this->db === null) {
            throw new \RuntimeException('Database connection not initialized.');
        }
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO updates (target_user_id, title, message, is_active, created_by) VALUES (:target_user_id, :title, :message, :is_active, :created_by)";
        $stmt = $this->db->prepare($sql);
        $target = !empty($data['target_user_id']) ? (int)$data['target_user_id'] : null;
        $stmt->bindValue(':target_user_id', $target, $target === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':title', (string)($data['title'] ?? ''));
        $stmt->bindValue(':message', (string)($data['message'] ?? ''));
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':created_by', (int)($data['created_by'] ?? 0), PDO::PARAM_INT);
        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function listAll(int $limit = 100): array
    {
        $stmt = $this->db->prepare("SELECT u.*, au.username as author_username FROM updates u JOIN users au ON au.id = u.created_by ORDER BY u.created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Admin: paged/filterable updates list */
    public function listPaged(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = [];
        $params = [];
        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($q !== '') {
            $where[] = '(u.title LIKE :q OR u.message LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = 'u.is_active = :active';
            $params[':active'] = (int)$filters['is_active'] ? 1 : 0;
        }
        if (!empty($filters['target_user_id'])) {
            $where[] = 'u.target_user_id = :tuid';
            $params[':tuid'] = (int)$filters['target_user_id'];
        }
        if (!empty($filters['created_by'])) {
            $where[] = 'u.created_by = :cb';
            $params[':cb'] = (int)$filters['created_by'];
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count
        $countSql = "SELECT COUNT(*) FROM updates u $whereSql";
        $stc = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $stc->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stc->execute();
        $total = (int)$stc->fetchColumn();

        // Page
        $perPage = max(1, min(100, (int)$perPage));
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT u.*, au.username as author_username
                FROM updates u
                JOIN users au ON au.id = u.created_by
                $whereSql
                ORDER BY u.created_at DESC, u.id DESC
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

    public function listActiveForUser(int $userId, int $limit = 10): array
    {
        $sql = "SELECT u.*, au.username as author_username
                FROM updates u
                JOIN users au ON au.id = u.created_by
                WHERE u.is_active = 1 AND (u.target_user_id IS NULL OR u.target_user_id = :uid)
                ORDER BY u.created_at DESC
                LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT u.*, au.username as author_username FROM updates u JOIN users au ON au.id = u.created_by WHERE u.id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE updates SET target_user_id = :target_user_id, title = :title, message = :message, is_active = :is_active WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $target = isset($data['target_user_id']) && $data['target_user_id'] !== '' ? (int)$data['target_user_id'] : null;
        $stmt->bindValue(':target_user_id', $target, $target === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':title', (string)($data['title'] ?? ''));
        $stmt->bindValue(':message', (string)($data['message'] ?? ''));
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM updates WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
