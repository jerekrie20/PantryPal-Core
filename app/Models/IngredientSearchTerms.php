<?php

namespace Models;

/**
 * PDO model for the ingredient_search_terms table.
 *
 * Permanent cache mapping raw pantry item names to AI-canonicalized
 * recipe-search terms. Unlike fatsecret_cache this never expires —
 * "Red Seedless Grapes" will always mean "grapes".
 */
class IngredientSearchTerms
{
    private \PDO $pdo;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? $GLOBALS['conn'];
    }

    public static function hashName(string $rawName): string
    {
        return hash('sha256', mb_strtolower(trim($rawName)));
    }

    /**
     * Fetch cached terms for a list of raw names.
     *
     * @param string[] $rawNames
     * @return array<string, string> raw name => search term (only cached entries)
     */
    public function getForNames(array $rawNames): array
    {
        if (empty($rawNames)) {
            return [];
        }

        $hashToRaw = [];
        foreach ($rawNames as $name) {
            $hashToRaw[self::hashName($name)] = $name;
        }

        $placeholders = implode(',', array_fill(0, count($hashToRaw), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT raw_hash, search_term FROM ingredient_search_terms WHERE raw_hash IN ({$placeholders})"
        );
        $stmt->execute(array_keys($hashToRaw));

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $raw = $hashToRaw[$row['raw_hash']] ?? null;
            if ($raw !== null) {
                $result[$raw] = (string)$row['search_term'];
            }
        }
        return $result;
    }

    /**
     * Store a batch of raw name => search term mappings.
     *
     * @param array<string, string> $map
     */
    public function storeMany(array $map): void
    {
        if (empty($map)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ingredient_search_terms (raw_hash, raw_name, search_term)
             VALUES (:hash, :raw, :term)
             ON DUPLICATE KEY UPDATE search_term = VALUES(search_term)'
        );
        foreach ($map as $raw => $term) {
            $stmt->execute([
                ':hash' => self::hashName((string)$raw),
                ':raw'  => mb_substr((string)$raw, 0, 255),
                ':term' => mb_substr((string)$term, 0, 100),
            ]);
        }
    }
}
