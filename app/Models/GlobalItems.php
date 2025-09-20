<?php

namespace Models;

use PDO;
use PDOException;

class GlobalItems
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
        try {
            $stmt = $this->db->prepare("SELECT * FROM global_items WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function findByNormalizedName(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM global_items WHERE normalized_name = ?");
            $stmt->execute([$name]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function findByApiId(int $apiId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM global_items WHERE api_id = ?");
            $stmt->execute([$apiId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function create(array $data): ?int
    {
        try {
            $sql = "INSERT INTO global_items (name, normalized_name, api_id, image_url, category, nutrition_info) 
                    VALUES (:name, :normalized_name, :api_id, :image_url, :category, :nutrition_info)";

            $stmt = $this->db->prepare($sql);

            $success = $stmt->execute([
                ':name' => $data['name'],
                ':normalized_name' => $data['normalized_name'],
                ':api_id' => $data['api_id'],
                ':image_url' => $data['image_url'],
                ':category' => $data['category'],
                ':nutrition_info' => $data['nutrition_info'],
            ]);

            return $success ? (int)$this->db->lastInsertId() : null;

        } catch (PDOException $e) {
            error_log("Error in GlobalItems::create(): " . $e->getMessage());
            return null;
        }
    }
}
