<?php
namespace Models;

use PDO;

class Products
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

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function normalizeTitle(string $title): string
    {
        $t = mb_strtolower(trim($title));
        return preg_replace('/\s+/', ' ', $t);
    }

    public static function normalizeBrand(?string $brand): ?string
    {
        if ($brand === null) return null;
        $b = trim($brand);
        return $b === '' ? null : $b;
    }

    public function findBySourceAndApiId(string $source, int|string $apiId): ?array
    {
        $st = $this->db->prepare("SELECT * FROM products WHERE api_source = :s AND api_id = :i LIMIT 1");
        $st->execute([':s' => $source, ':i' => (string)$apiId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByApiId(int $apiId): ?array
    {
        // backward-compat
        $st = $this->db->prepare("SELECT * FROM products WHERE api_id = :api LIMIT 1");
        $st->execute([':api' => $apiId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findExact(string $title, ?string $brand): ?array
    {
        $sql = "SELECT * FROM products WHERE title = :t";
        $params = [':t' => $title];
        if ($brand !== null) { $sql .= " AND brand = :b"; $params[':b'] = $brand; }
        else { $sql .= " AND brand IS NULL"; }
        $sql .= " LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $d): ?int
    {
        $st = $this->db->prepare("INSERT INTO products
            ( api_source, api_id, title, brand, upc, size_text, image_url, category, nutrition_info, raw_payload)
            VALUES
            ( :api_source, :api_id, :title, :brand, :upc, :size_text, :image_url, :category, :nutrition_info, :raw_payload)");
        $ok = $st->execute([
            ':api_source'     => $d['api_source'] ?? null,
            ':api_id'         => isset($d['api_id']) ? (string)$d['api_id'] : null,
            ':title'          => $d['title'],
            ':brand'          => $d['brand'] ?? null,
            ':upc'            => $d['upc'] ?? null,
            ':size_text'      => $d['size_text'] ?? null,
            ':image_url'      => $d['image_url'] ?? null,
            ':category'       => $d['category'] ?? null,
            ':nutrition_info' => isset($d['nutrition_info'])
                ? (is_array($d['nutrition_info']) ? json_encode($d['nutrition_info']) : $d['nutrition_info'])
                : null,
            ':raw_payload'    => isset($d['raw_payload'])
                ? (is_array($d['raw_payload']) ? json_encode($d['raw_payload']) : $d['raw_payload'])
                : null,
        ]);
        return $ok ? (int)$this->db->lastInsertId() : null;
    }

    public function searchFuzzy(string $title, ?string $brand, int $limit = 8): array
    {
        $norm = self::normalizeTitle($title);
        $tokens = preg_split('/\s+/', $norm);
        $clauses = [];
        $params = [];

        foreach ($tokens as $i => $t) {
            $clauses[] = "LOWER(title) LIKE :t$i";
            $params[":t$i"] = "%$t%";
        }
        $where = $clauses ? '(' . implode(' AND ', $clauses) . ')' : '1=1';
        $sql = "SELECT * FROM products WHERE $where";

        // Prefer brand matches first if provided
        $sql .= " ORDER BY (CASE WHEN " . ($brand ? "brand = :b" : "brand IS NULL") . " THEN 0 ELSE 1 END), created_at DESC LIMIT :lim";

        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        if ($brand) $st->bindValue(':b', $brand);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

}
