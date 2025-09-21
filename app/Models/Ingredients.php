<?php
namespace Models;

use PDO;

class Ingredients
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

    public static function normalizeName(string $name): string
    {
        $n = mb_strtolower(trim($name));
        return preg_replace('/\s+/', ' ', $n);
    }

    public static function normalizeBrand(?string $brand): ?string
    {
        if ($brand === null) return null;
        $b = trim($brand);
        return $b === '' ? null : $b;
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM ingredients WHERE id = :id LIMIT 1");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findBySourceAndApiId(string $source, int|string $apiId): ?array
    {
        $st = $this->db->prepare("SELECT * FROM ingredients WHERE api_source = :s AND api_id = :i LIMIT 1");
        $st->execute([':s' => $source, ':i' => (string)$apiId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByApiId(int $apiId): ?array
    {
        // kept for backward-compat (spoonacular code paths); source-agnostic
        $st = $this->db->prepare("SELECT * FROM ingredients WHERE api_id = :api LIMIT 1");
        $st->execute([':api' => $apiId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findExact(string $normalizedName, ?string $brand, ?string $apiKind): ?array
    {
        $sql = "SELECT * FROM ingredients WHERE normalized_name = :n";
        $params = [':n' => $normalizedName];

        if ($brand !== null) { $sql .= " AND brand = :b"; $params[':b'] = $brand; }
        else { $sql .= " AND brand IS NULL"; }

        if ($apiKind !== null) { $sql .= " AND api_kind = :k"; $params[':k'] = $apiKind; }

        $sql .= " LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function searchFuzzy(string $normalizedName, ?string $brand, ?string $apiKind, int $limit = 8): array
    {
        $tokens = preg_split('/\s+/', $normalizedName);
        $clauses = [];
        $params = [];
        foreach ($tokens as $i => $t) {
            $clauses[] = "(normalized_name LIKE :t$i OR name LIKE :tn$i)";
            $params[":t$i"]  = "%$t%";
            $params[":tn$i"] = "%$t%";
        }
        $where = $clauses ? '(' . implode(' AND ', $clauses) . ')' : '1=1';

        $sql = "SELECT * FROM ingredients WHERE $where";
        if ($apiKind !== null) { $sql .= " AND api_kind = :k"; $params[':k'] = $apiKind; }

        $sql .= " ORDER BY (CASE WHEN " . ($brand !== null ? "brand = :b" : "brand IS NULL")
            . " THEN 0 ELSE 1 END), created_at DESC LIMIT :lim";

        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        if ($brand !== null) $st->bindValue(':b', $brand);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $d): ?int
    {
        $st = $this->db->prepare("INSERT INTO ingredients
            (name, normalized_name, brand, api_source, api_id, api_kind, image_url, category, nutrition_info, search_terms)
            VALUES (:name,:normalized_name,:brand,:api_source,:api_id,:api_kind,:image_url,:category,:nutrition_info,:search_terms)");
        $ok = $st->execute([
            ':name'            => $d['name'],
            ':normalized_name' => $d['normalized_name'],
            ':brand'           => $d['brand'] ?? null,
            ':api_source'      => $d['api_source'] ?? null,
            ':api_id'          => isset($d['api_id']) ? (string)$d['api_id'] : null,
            ':api_kind'        => $d['api_kind'] ?? null,
            ':image_url'       => $d['image_url'] ?? null,
            ':category'        => $d['category'] ?? null,
            ':nutrition_info'  => isset($d['nutrition_info'])
                ? (is_array($d['nutrition_info']) ? json_encode($d['nutrition_info']) : $d['nutrition_info'])
                : null,
            ':search_terms'    => $d['search_terms'] ?? null,
        ]);
        return $ok ? (int)$this->db->lastInsertId() : null;
    }
}
