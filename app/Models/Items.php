<?php

namespace Models;

use PDO;

class Items
{
    protected ?PDO $db = null;

    public function __construct()
    {
        global $conn;
        $this->db = $conn instanceof PDO ? $conn : null;
        if ($this->db === null) {
            throw new \RuntimeException('Database connection not initialized.');
        }
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM items WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Pagination (kept your signature & return shape). Now joins ingredient/product fields.
     */
    public function findAll(int $userId, int $page = 1, int $itemsPerPage = 10): array
    {
        $countSql = "SELECT COUNT(i.id) FROM items i" . (!empty($userId) ? " WHERE i.user_id = :user_id" : "");
        $params = [];
        if (!empty($userId)) $params[':user_id'] = $userId;

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = (int)ceil($totalItems / max(1, $itemsPerPage));
        $offset = ($page - 1) * $itemsPerPage;

        // Eager-load a few display fields from related tables to avoid N+1 queries in views
        $sql = "SELECT i.*, 
                       ing.name        AS ingredient_name,
                       ing.category    AS ingredient_category,
                       ing.image_url   AS ingredient_image_url,
                       p.title         AS product_title,
                       p.category      AS product_category,
                       p.image_url     AS product_image_url
                FROM items i
                LEFT JOIN ingredients ing ON ing.id = i.ingredient_id
                LEFT JOIN products p      ON p.id = i.product_id"
            . (!empty($userId) ? " WHERE i.user_id = :user_id" : "") .
            " ORDER BY i.created_at DESC, i.id DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        if (!empty($userId)) $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'items' => $items,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => max(1, $totalPages),
                'totalItems' => $totalItems,
                'itemsPerPage' => $itemsPerPage,
            ],
        ];
    }

    /** Admin: paged/filterable items list across all users */
    public function findAllAdminPaged(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['user_id'])) {
            $where[] = 'i.user_id = :uid';
            $params[':uid'] = (int)$filters['user_id'];
        }
        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($q !== '') {
            $where[] = '(i.entered_name LIKE :q OR ing.name LIKE :q OR p.title LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count
        $countSql = "SELECT COUNT(i.id)
                     FROM items i
                     LEFT JOIN ingredients ing ON ing.id = i.ingredient_id
                     LEFT JOIN products p      ON p.id = i.product_id
                     $whereSql";
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

        $sql = "SELECT i.*, 
                       ing.name        AS ingredient_name,
                       ing.category    AS ingredient_category,
                       ing.image_url   AS ingredient_image_url,
                       p.title         AS product_title,
                       p.category      AS product_category,
                       p.image_url     AS product_image_url
                FROM items i
                LEFT JOIN ingredients ing ON ing.id = i.ingredient_id
                LEFT JOIN products p      ON p.id = i.product_id
                $whereSql
                ORDER BY i.created_at DESC, i.id DESC
                LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        $items = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalPages = (int)max(1, ceil($total / max(1, $perPage)));
        return [
            'items' => $items,
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'totalItems' => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }

    /** Dashboard: recent items (items-only). */
    public function findRecent(int $userId, int $limit = 6): array
    {
        $sql = "SELECT i.id, i.ingredient_id, i.product_id, i.expiration_date, i.created_at
                FROM items i
                WHERE i.user_id = :user_id
                ORDER BY i.created_at DESC, i.id DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $limit,   PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Backward-compat wrapper: previously joined global tables; now items-only. */
    public function findRecentWithGlobal(int $userId, int $limit = 6): array
    {
        $sql = "SELECT i.id, i.ingredient_id, i.product_id, i.expiration_date, i.created_at,
                       ing.name      AS ingredient_name,
                       ing.category  AS ingredient_category,
                       ing.image_url AS ingredient_image_url,
                       p.title       AS product_title,
                       p.category    AS product_category,
                       p.image_url   AS product_image_url
                FROM items i
                LEFT JOIN ingredients ing ON ing.id = i.ingredient_id
                LEFT JOIN products p      ON p.id = i.product_id
                WHERE i.user_id = :user_id
                ORDER BY i.created_at DESC, i.id DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $limit,   PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    public function countExpiringSoon(int $userId, int $days = 3): int
    {
        $sql = "SELECT COUNT(*) FROM items
                WHERE user_id = :user_id
                  AND expiration_date IS NOT NULL
                  AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }


    /** Explicit creator that returns ID */
    public function create(array $data): int
    {
        $st = $this->db->prepare("INSERT INTO items
        (user_id, ingredient_id, product_id, quantity, unit, purchase_date, expiration_date, entered_name, entered_brand)
        VALUES
        (:user_id, :ingredient_id, :product_id, :quantity, :unit, :purchase_date, :expiration_date, :entered_name, :entered_brand)");
        $st->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);

        $ingId = $data['ingredient_id'] ?? null;
        $prodId = $data['product_id'] ?? null;

        if ($ingId === null) $st->bindValue(':ingredient_id', null, PDO::PARAM_NULL);
        else                  $st->bindValue(':ingredient_id', $ingId, PDO::PARAM_INT);

        if ($prodId === null) $st->bindValue(':product_id', null, PDO::PARAM_NULL);
        else                  $st->bindValue(':product_id', $prodId, PDO::PARAM_INT);

        $st->bindValue(':quantity', (string)$data['quantity']);
        $st->bindValue(':unit', $data['unit'] ?? null);
        $st->bindValue(':purchase_date', $data['purchase_date'] ?? null);
        $st->bindValue(':expiration_date', $data['expiration_date'] ?? null);
        $st->bindValue(':entered_name', $data['entered_name'] ?? null);
        $st->bindValue(':entered_brand', $data['entered_brand'] ?? null);
        $st->execute();
        return (int)$this->db->lastInsertId();
    }

    public function findWithGlobalById(int $id, int $userId): array|false
    {
        // Backward-compat: now returns items-only row with user check
        $sql = "SELECT * FROM items WHERE id = :id AND user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }


    public function update(int $id, array $data, ?int $userId = null): bool
    {
        $fields = [];
        $params = [':id' => $id];
        $map = [
            'ingredient_id' => PDO::PARAM_INT,
            'product_id' => PDO::PARAM_INT,
            'quantity' => PDO::PARAM_STR,
            'unit' => PDO::PARAM_STR,
            'purchase_date' => PDO::PARAM_STR,
            'expiration_date' => PDO::PARAM_STR,
            'entered_name' => PDO::PARAM_STR,
            'entered_brand' => PDO::PARAM_STR,
        ];
        foreach ($map as $key => $type) {
            if (array_key_exists($key, $data)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $data[$key];
            }
        }
        if (empty($fields)) return false;

        $sql = "UPDATE items SET " . implode(', ', $fields) . " WHERE id = :id";
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $type = $map[ltrim($k, ':')] ?? PDO::PARAM_STR;
            if ($k === ':id' || $k === ':user_id') $type = PDO::PARAM_INT;
            $stmt->bindValue($k, $v, $type);
        }
        return $stmt->execute();
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        $sql = "DELETE FROM items WHERE id = :id";
        $stmt = $this->db->prepare($sql . ($userId !== null ? " AND user_id = :user_id" : ""));
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($userId !== null) $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
