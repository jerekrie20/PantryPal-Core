<?php

namespace Models;

use PDO;

class ShoppingList
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

    /** Fetch all items for a user, ordered by recipe group then insertion order. */
    public function findAllForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM shopping_list_items
             WHERE user_id = :uid
             ORDER BY COALESCE(recipe_title, '') ASC, created_at ASC"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Find a single item belonging to a user. */
    public function find(int $id, int $userId): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM shopping_list_items WHERE id = :id AND user_id = :uid LIMIT 1"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Insert a new shopping list item. Returns the new row ID. */
    public function create(int $userId, string $name, ?string $quantity = null, ?int $recipeId = null, ?string $recipeTitle = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shopping_list_items (user_id, name, quantity, recipe_id, recipe_title)
             VALUES (:uid, :name, :qty, :rid, :rtitle)"
        );
        $stmt->bindValue(':uid',    $userId, PDO::PARAM_INT);
        $stmt->bindValue(':name',   $name);
        $stmt->bindValue(':qty',    $quantity);
        if ($recipeId === null) {
            $stmt->bindValue(':rid', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':rid', $recipeId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':rtitle', $recipeTitle);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /** Update name and optional quantity of an item (ownership-scoped). */
    public function update(int $id, int $userId, string $name, ?string $quantity = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE shopping_list_items SET name = :name, quantity = :qty
             WHERE id = :id AND user_id = :uid"
        );
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':qty',  $quantity);
        $stmt->bindValue(':id',   $id,     PDO::PARAM_INT);
        $stmt->bindValue(':uid',  $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** Delete a single item (ownership-scoped). */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM shopping_list_items WHERE id = :id AND user_id = :uid"
        );
        $stmt->bindValue(':id',  $id,     PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** Total item count for a user (used in nav badge). */
    public function countForUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM shopping_list_items WHERE user_id = :uid"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
