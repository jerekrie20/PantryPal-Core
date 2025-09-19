<?php

namespace Models;

class Items
{
    protected $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
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
     * @param int|null $userId The ID of the user whose items to fetch. If null, fetches all items.
     * @param int $page The current page number.
     * @param int $itemsPerPage The number of items to display per page.
     * @return array An associative array containing the items for the current page and pagination metadata.
     */
    public function findAll($userId = null, int $page = 1, int $itemsPerPage = 10): array
    {
        // --- Step 1: Count the total number of records ---
        $countSql = "SELECT COUNT(id) FROM items";
        $params = [];
        if ($userId !== null) {
            $countSql .= " WHERE user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int) $countStmt->fetchColumn();

        // Calculate total pages
        $totalPages = ceil($totalItems / $itemsPerPage);

        // --- Step 2: Fetch the records for the current page ---
        // Calculate the offset for the SQL query
        $offset = ($page - 1) * $itemsPerPage;

        $sql = "SELECT * FROM items";
        $params[':limit'] = $itemsPerPage;
        $params[':offset'] = $offset;

        if ($userId !== null) {
            $sql .= " WHERE user_id = :user_id";
        }

        // Add ordering to ensure consistent results across pages
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // Bind parameters, making sure to bind them as integers
        if ($userId !== null) {
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

    public function insert(){}

    public function update(){}

    public function delete(){}

}