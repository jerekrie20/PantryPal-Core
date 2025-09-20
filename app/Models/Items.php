<?php

namespace Models;

use PDO;

class Items
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

    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /**
     * Finds all items, with support for pagination.
     *
     * @param int $userId The ID of the user whose items to fetch. If null, fetches all items.
     * @param int $page The current page number.
     * @param int $itemsPerPage The number of items to display per page.
     * @return array An associative array containing the items for the current page and pagination metadata.
     */
    public function findAll(int $userId, int $page = 1, int $itemsPerPage = 10): array
    {
        // --- Step 1: Count the total number of records ---
        $countSql = "SELECT COUNT(id) FROM items";
        $params = [];
        if (!empty($userId)) {
            $countSql .= " WHERE user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();

        // Calculate total pages
        $totalPages = ceil($totalItems / $itemsPerPage);

        // --- Step 2: Fetch the records for the current page ---
        // Calculate the offset for the SQL query
        $offset = ($page - 1) * $itemsPerPage;

        $sql = "SELECT * FROM items";
        $params[':limit'] = $itemsPerPage;
        $params[':offset'] = $offset;

        if (!empty($userId)) {
            $sql .= " WHERE user_id = :user_id";
        }

        // Add ordering to ensure consistent results across pages
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // Bind parameters, making sure to bind them as integers
        if (!empty($userId)) {
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
        }
        $stmt->bindParam(':limit', $params[':limit'], \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $params[':offset'], \PDO::PARAM_INT);

        $stmt->execute();

        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // --- Step 3: Return the data and pagination info ---
        return [
            'items' => $items,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'itemsPerPage' => $itemsPerPage
            ]
        ];
    }

    /**
     * Fetch recent items for the dashboard with joined global item data (name, category, image).
     */
    public function findRecentWithGlobal(int $userId, int $limit = 6): array
    {
        $sql = "SELECT i.id, i.expiration_date, gi.name, gi.category, gi.image_url
                FROM items i
                JOIN global_items gi ON gi.id = i.global_item_id
                WHERE i.user_id = :user_id
                ORDER BY i.created_at DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Count items expiring within the next N days (including today).
     */
    public function countExpiringSoon(int $userId, int $days = 3): int
    {
        $sql = "SELECT COUNT(*) FROM items
                WHERE user_id = :user_id
                  AND expiration_date IS NOT NULL
                  AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindParam(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function insert($data, int $globalId)
    {
        $user_id = $_SESSION['user_id'];
        $quantity = $data['quantity'];
        $unit = $data['unit'];
        $purchaseDate = $data['purchase_date'];
        $expirationDate = $data['expiration_date'];

        $sql = "INSERT INTO items ( user_id, global_item_id ,quantity, unit, purchase_date ,expiration_date) VALUES (:user_id, :global_item_id ,:quantity, :unit, :purchase_date ,:expiration_date)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->bindParam(':global_item_id', $globalId, \PDO::PARAM_INT);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':purchase_date', $purchaseDate);
        $stmt->bindParam(':expiration_date', $expirationDate);
        // quantity may be decimal; bind as string to avoid truncation
        $stmt->bindParam(':quantity', $quantity, \PDO::PARAM_STR);

        $stmt->execute();
        //close
        $stmt = null;

    }

    public function findWithGlobalById(int $id, int $userId): array|false
    {
        $sql = "SELECT i.*, gi.name, gi.category, gi.image_url, gi.nutrition_info
                FROM items i
                JOIN global_items gi ON gi.id = i.global_item_id
                WHERE i.id = :id AND i.user_id = :user_id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function update()
    {
    }

    public function delete()
    {
    }
}