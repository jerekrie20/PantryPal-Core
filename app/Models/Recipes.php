<?php

namespace Models;

use PDO;

class Recipes
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

    /** Upsert a recipe based on provider identity. Returns recipe ID. */
    public function upsertFromProvider(array $r, ?int $userId = null, string $source = 'fatsecret'): int
    {
        $apiId = null;
        if (isset($r['id'])) {
            $apiId = is_scalar($r['id']) ? (string)$r['id'] : null;
        } elseif (isset($r['api_id'])) {
            $apiId = is_scalar($r['api_id']) ? (string)$r['api_id'] : null;
        }
        $title = (string)($r['title'] ?? ($r['name'] ?? 'Recipe'));
        $image = isset($r['image']) && is_string($r['image']) ? $r['image'] : null;
        $desc  = isset($r['summary']) && is_string($r['summary']) ? $r['summary'] : ($r['description'] ?? null);
        $srcUrl = isset($r['sourceUrl']) && is_string($r['sourceUrl']) ? $r['sourceUrl'] : ($r['url'] ?? null);
        $raw = json_encode($r, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        // Normalize/allow source strings; permit null if unknown
        $allowedSources = ['fdc','off','manual','fatsecret'];
        $src = in_array($source, $allowedSources, true) ? $source : null;

        // Try update existing by (api_source, api_id) when api_id is available and source is usable
        if ($apiId !== null && $src !== null) {
            $sel = $this->db->prepare("SELECT id FROM recipes WHERE api_source = :src AND api_id = :api_id LIMIT 1");
            $sel->bindValue(':src', $src, PDO::PARAM_STR);
            $sel->bindValue(':api_id', $apiId, PDO::PARAM_STR);
            $sel->execute();
            $id = (int)($sel->fetchColumn() ?: 0);
            if ($id > 0) {
                $upd = $this->db->prepare("UPDATE recipes
                    SET title = :title, description = :description, image_url = :image, source_url = :source_url, raw_payload = :raw
                    WHERE id = :id");
                $upd->bindValue(':title', $title, PDO::PARAM_STR);
                $upd->bindValue(':description', $desc, $desc === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':image', $image, $image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':source_url', $srcUrl, $srcUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':raw', $raw, PDO::PARAM_STR);
                $upd->bindValue(':id', $id, PDO::PARAM_INT);
                $upd->execute();
                return $id;
            }
        }

        // Insert new (with resilient fallback when ENUM might not include new sources)
        $ins = $this->db->prepare("INSERT INTO recipes (user_id, api_source, api_id, title, description, image_url, source_url, raw_payload)
            VALUES (:user_id, :src, :api_id, :title, :description, :image, :source_url, :raw)");
        if ($userId === null) { $ins->bindValue(':user_id', null, PDO::PARAM_NULL); } else { $ins->bindValue(':user_id', $userId, PDO::PARAM_INT); }
        if ($apiId === null) { $ins->bindValue(':api_id', null, PDO::PARAM_NULL); } else { $ins->bindValue(':api_id', $apiId, PDO::PARAM_STR); }
        $ins->bindValue(':src', $src, $src === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $ins->bindValue(':title', $title, PDO::PARAM_STR);
        $ins->bindValue(':description', $desc, $desc === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $ins->bindValue(':image', $image, $image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $ins->bindValue(':source_url', $srcUrl, $srcUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $ins->bindValue(':raw', $raw, PDO::PARAM_STR);
        try {
            $ins->execute();
        } catch (\Throwable $e) {
            // Fallback: retry with api_source = NULL in case ENUM doesn't include the provided source yet
            try {
                $ins2 = $this->db->prepare("INSERT INTO recipes (user_id, api_source, api_id, title, description, image_url, source_url, raw_payload)
                    VALUES (:user_id, NULL, :api_id, :title, :description, :image, :source_url, :raw)");
                if ($userId === null) { $ins2->bindValue(':user_id', null, PDO::PARAM_NULL); } else { $ins2->bindValue(':user_id', $userId, PDO::PARAM_INT); }
                if ($apiId === null) { $ins2->bindValue(':api_id', null, PDO::PARAM_NULL); } else { $ins2->bindValue(':api_id', $apiId, PDO::PARAM_STR); }
                $ins2->bindValue(':title', $title, PDO::PARAM_STR);
                $ins2->bindValue(':description', $desc, $desc === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $ins2->bindValue(':image', $image, $image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $ins2->bindValue(':source_url', $srcUrl, $srcUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $ins2->bindValue(':raw', $raw, PDO::PARAM_STR);
                $ins2->execute();
            } catch (\Throwable $e2) {
                throw $e2; // bubble up the original failure if fallback also fails
            }
        }
        return (int)$this->db->lastInsertId();
    }

    /** Ensure saved_recipes table and keys allow multiple saves per user. */
    private function ensureSavedSchema(): void
    {
        try {
            // Create table if missing with correct composite PK
            $this->db->exec("CREATE TABLE IF NOT EXISTS saved_recipes (
                user_id INT(11) NOT NULL,
                recipe_id INT(11) NOT NULL,
                saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, recipe_id),
                KEY idx_recipe_id (recipe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // If someone created a wrong PK (e.g., only user_id), try to fix it.
            try { $this->db->exec("ALTER TABLE saved_recipes DROP PRIMARY KEY, ADD PRIMARY KEY (user_id, recipe_id)"); } catch (\Throwable $e) { /* ignore */ }
            // Ensure supporting index exists (will fail harmlessly if it already exists with same name)
            try { $this->db->exec("CREATE INDEX idx_recipe_id ON saved_recipes (recipe_id)"); } catch (\Throwable $e) { /* ignore */ }
        } catch (\Throwable $e) {
            // Swallow to avoid breaking requests; saving will still attempt using whatever schema exists
        }
    }

    /** Ensure recipe_nutrition table exists for storing per-serving nutrients. */
    private function ensureNutritionSchema(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS recipe_nutrition (
                id INT(11) NOT NULL AUTO_INCREMENT,
                recipe_id INT(11) NOT NULL,
                per_serving JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_recipe (recipe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // Try to ensure the unique key exists (ignore if already there)
            try { $this->db->exec("CREATE UNIQUE INDEX uniq_recipe ON recipe_nutrition (recipe_id)"); } catch (\Throwable $e) { /* ignore */ }
        } catch (\Throwable $e) {
            // Do not throw; reads/writes will be guarded further down
        }
    }

    /** Mark a recipe as saved by the user. */
    public function saveForUser(int $recipeId, int $userId): bool
    {
        $this->ensureSavedSchema();
        // idempotent insert using composite PK
        $sql = "INSERT INTO saved_recipes (user_id, recipe_id) VALUES (:uid, :rid)
                ON DUPLICATE KEY UPDATE saved_at = saved_at";
        $st = $this->db->prepare($sql);
        $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st->bindValue(':rid', $recipeId, PDO::PARAM_INT);
        return $st->execute();
    }

    public function isSaved(int $recipeId, int $userId): bool
    {
        $this->ensureSavedSchema();
        $st = $this->db->prepare("SELECT 1 FROM saved_recipes WHERE user_id = :uid AND recipe_id = :rid LIMIT 1");
        $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st->bindValue(':rid', $recipeId, PDO::PARAM_INT);
        $st->execute();
        return (bool)$st->fetchColumn();
    }

    /** Search locally by title or raw JSON payload. Works across MySQL and MariaDB. */
    public function searchLocalByQuery(string $q, int $limit = 12): array
    {
        $q = trim($q);
        if ($q === '') return [];
        $like = '%' . $q . '%';

        // Try JSON-aware search (MySQL 5.7+/8.0+), but gracefully fall back to plain LIKE on raw_payload for MariaDB.
        try {
            $sql = "SELECT * FROM recipes
                    WHERE (title LIKE :q)
                       OR (raw_payload IS NOT NULL AND JSON_EXTRACT(raw_payload, '$.summary') LIKE :q)
                       OR (raw_payload IS NOT NULL AND JSON_SEARCH(raw_payload, 'one', :pat, NULL, '$') IS NOT NULL)
                    ORDER BY id DESC
                    LIMIT :lim";
            $st = $this->db->prepare($sql);
            $st->bindValue(':q', $like, PDO::PARAM_STR);
            $st->bindValue(':pat', $like, PDO::PARAM_STR);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map([$this, 'normalize'], $rows);
        } catch (\Throwable $e) {
            // Fallback for MariaDB (no JSON_SEARCH) or older MySQL: raw_payload is TEXT → LIKE works.
            try {
                $sql2 = "SELECT * FROM recipes
                         WHERE (title LIKE :q)
                            OR (raw_payload IS NOT NULL AND raw_payload LIKE :pat)
                         ORDER BY id DESC
                         LIMIT :lim";
                $st2 = $this->db->prepare($sql2);
                $st2->bindValue(':q', $like, PDO::PARAM_STR);
                $st2->bindValue(':pat', $like, PDO::PARAM_STR);
                $st2->bindValue(':lim', $limit, PDO::PARAM_INT);
                $st2->execute();
                $rows = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                return array_map([$this, 'normalize'], $rows);
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    /** Find locally by ingredient keywords in title/raw. If $requireAll true, all names must match (AND), otherwise any (OR). */
    public function findByIngredientsLocal(array $names, int $limit = 12, bool $requireAll = false): array
    {
        $names = array_values(array_filter(array_map(fn($s)=> trim((string)$s), $names)));
        if (!$names) return [];
        // Build conditions on title and raw_payload JSON string
        $conds = [];
        $params = [];
        $i = 0;
        foreach ($names as $n) {
            $i++;
            $keyTitle = ':k' . $i . 't';
            $keyRaw   = ':k' . $i . 'r';
            $conds[] = "(title LIKE $keyTitle OR (raw_payload IS NOT NULL AND raw_payload LIKE $keyRaw))";
            $params[$keyTitle] = '%' . $n . '%';
            $params[$keyRaw]   = '%' . $n . '%';
        }
        $join = $requireAll ? ' AND ' : ' OR ';
        $sql = "SELECT * FROM recipes WHERE (" . implode($join, $conds) . ") ORDER BY id DESC LIMIT :lim";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, PDO::PARAM_STR);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map([$this, 'normalize'], $rows);
    }

    /** Like searchLocalByQuery but restricted to user-generated recipes (user_id IS NOT NULL). */
    public function searchLocalUserByQuery(string $q, int $limit = 12): array
    {
        $q = trim($q);
        if ($q === '') return [];
        $like = '%' . $q . '%';
        try {
            $sql = "SELECT * FROM recipes
                    WHERE user_id IS NOT NULL AND (
                        (title LIKE :q)
                        OR (raw_payload IS NOT NULL AND JSON_EXTRACT(raw_payload, '$.summary') LIKE :q)
                        OR (raw_payload IS NOT NULL AND JSON_SEARCH(raw_payload, 'one', :pat, NULL, '$') IS NOT NULL)
                    )
                    ORDER BY id DESC
                    LIMIT :lim";
            $st = $this->db->prepare($sql);
            $st->bindValue(':q', $like, PDO::PARAM_STR);
            $st->bindValue(':pat', $like, PDO::PARAM_STR);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map([$this, 'normalize'], $rows);
        } catch (\Throwable $e) {
            try {
                $sql2 = "SELECT * FROM recipes
                         WHERE user_id IS NOT NULL AND (
                            (title LIKE :q)
                            OR (raw_payload IS NOT NULL AND raw_payload LIKE :pat)
                         )
                         ORDER BY id DESC
                         LIMIT :lim";
                $st2 = $this->db->prepare($sql2);
                $st2->bindValue(':q', $like, PDO::PARAM_STR);
                $st2->bindValue(':pat', $like, PDO::PARAM_STR);
                $st2->bindValue(':lim', $limit, PDO::PARAM_INT);
                $st2->execute();
                $rows = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                return array_map([$this, 'normalize'], $rows);
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    /** Ingredient keyword search limited to user-generated recipes (user_id IS NOT NULL). */
    public function findByIngredientsLocalUser(array $names, int $limit = 12, bool $requireAll = false): array
    {
        $names = array_values(array_filter(array_map(fn($s)=> trim((string)$s), $names)));
        if (!$names) return [];
        $conds = [];
        $params = [];
        $i = 0;
        foreach ($names as $n) {
            $i++;
            $keyTitle = ':k' . $i . 't';
            $keyRaw   = ':k' . $i . 'r';
            $conds[] = "(title LIKE $keyTitle OR (raw_payload IS NOT NULL AND raw_payload LIKE $keyRaw))";
            $params[$keyTitle] = '%' . $n . '%';
            $params[$keyRaw]   = '%' . $n . '%';
        }
        $join = $requireAll ? ' AND ' : ' OR ';
        $sql = "SELECT * FROM recipes WHERE user_id IS NOT NULL AND (" . implode($join, $conds) . ") ORDER BY id DESC LIMIT :lim";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, PDO::PARAM_STR);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map([$this, 'normalize'], $rows);
    }

    /** Paged local search by ingredient keywords with total count. If $requireAll true, all names must match (AND), otherwise any (OR). */
    public function findByIngredientsLocalPaged(array $names, int $page = 1, int $perPage = 12, bool $requireAll = false): array
    {
        $names = array_values(array_filter(array_map(fn($s)=> trim((string)$s), $names)));
        if (!$names) return ['results' => [], 'total' => 0];
        $conds = [];
        $params = [];
        $i = 0;
        foreach ($names as $n) {
            $i++;
            $keyTitle = ':k' . $i . 't';
            $keyRaw   = ':k' . $i . 'r';
            $conds[] = "(title LIKE $keyTitle OR (raw_payload IS NOT NULL AND raw_payload LIKE $keyRaw))";
            $params[$keyTitle] = '%' . $n . '%';
            $params[$keyRaw]   = '%' . $n . '%';
        }
        $join = $requireAll ? ' AND ' : ' OR ';
        $where = '(' . implode($join, $conds) . ')';
        // total
        $countSql = "SELECT COUNT(*) FROM recipes WHERE $where";
        $stc = $this->db->prepare($countSql);
        foreach ($params as $k => $v) $stc->bindValue($k, $v, PDO::PARAM_STR);
        $stc->execute();
        $total = (int)$stc->fetchColumn();
        // page
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM recipes WHERE $where ORDER BY id DESC LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_STR);
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return ['results' => array_map([$this, 'normalize'], $rows), 'total' => $total];
    }

    /** Paged ingredient search for user-generated recipes only (user_id IS NOT NULL). */
    public function findByIngredientsLocalPagedUser(array $names, int $page = 1, int $perPage = 12, bool $requireAll = false): array
    {
        $names = array_values(array_filter(array_map(fn($s)=> trim((string)$s), $names)));
        if (!$names) return ['results' => [], 'total' => 0];
        $conds = [];
        $params = [];
        $i = 0;
        foreach ($names as $n) {
            $i++;
            $keyTitle = ':k' . $i . 't';
            $keyRaw   = ':k' . $i . 'r';
            $conds[] = "(title LIKE $keyTitle OR (raw_payload IS NOT NULL AND raw_payload LIKE $keyRaw))";
            $params[$keyTitle] = '%' . $n . '%';
            $params[$keyRaw]   = '%' . $n . '%';
        }
        $join = $requireAll ? ' AND ' : ' OR ';
        $where = 'user_id IS NOT NULL AND (' . implode($join, $conds) . ')';
        // total
        $countSql = "SELECT COUNT(*) FROM recipes WHERE $where";
        $stc = $this->db->prepare($countSql);
        foreach ($params as $k => $v) $stc->bindValue($k, $v, PDO::PARAM_STR);
        $stc->execute();
        $total = (int)$stc->fetchColumn();
        // page
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM recipes WHERE $where ORDER BY id DESC LIMIT :lim OFFSET :off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_STR);
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return ['results' => array_map([$this, 'normalize'], $rows), 'total' => $total];
    }

    /** Return user's saved recipes (bookmarks), newest first. */
    public function getSavedForUser(int $userId, int $limit = 24): array
    {
        $sql = "SELECT r.* FROM saved_recipes sr JOIN recipes r ON r.id = sr.recipe_id WHERE sr.user_id = :uid ORDER BY sr.saved_at DESC LIMIT :lim";
        $st = $this->db->prepare($sql);
        $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map([$this, 'normalize'], $rows);
    }

    /** Update an existing recipe row with detailed provider payload. */
    public function updateFromProviderDetails(int $dbId, array $details, string $source = 'fatsecret'): bool
    {
        if ($dbId <= 0) return false;
        // Extract a few common meta fields
        $title = isset($details['title']) ? (string)$details['title'] : (isset($details['name']) ? (string)$details['name'] : null);
        $image = isset($details['image']) && is_string($details['image']) ? $details['image'] : null;
        $srcUrl = isset($details['sourceUrl']) && is_string($details['sourceUrl']) ? $details['sourceUrl'] : null;
        $raw = json_encode($details, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $sql = "UPDATE recipes
                SET title = COALESCE(:title, title),
                    image_url = COALESCE(:image, image_url),
                    source_url = COALESCE(:source_url, source_url),
                    raw_payload = :raw
                WHERE id = :id";
        $st = $this->db->prepare($sql);
        $st->bindValue(':title', $title, $title === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $st->bindValue(':image', $image, $image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $st->bindValue(':source_url', $srcUrl, $srcUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $st->bindValue(':raw', $raw, PDO::PARAM_STR);
        $st->bindValue(':id', $dbId, PDO::PARAM_INT);
        $ok = $st->execute();

        // Additionally, if provider details include per-serving nutrients, persist them immediately
        try {
            $per = [];
            $src = $details['nutrientsPerServing'] ?? null;
            if (is_array($src)) {
                // Map common keys (supports Suggestic and similar)
                $map = [
                    'calories' => ['Calories','kcal'],
                    'energy' => ['Calories','kcal'],
                    'protein' => ['Protein','g'],
                    'carbs' => ['Carbohydrates','g'],
                    'netcarbs' => ['Net Carbs','g'],
                    'fat' => ['Fat','g'],
                    'saturatedFat' => ['Saturated Fat','g'],
                    'transFat' => ['Trans Fat','g'],
                    'monounsaturatedFat' => ['Monounsaturated Fat','g'],
                    'polyunsaturatedFat' => ['Polyunsaturated Fat','g'],
                    'sugar' => ['Sugar','g'],
                    'fiber' => ['Fiber','g'],
                    'cholesterol' => ['Cholesterol','mg'],
                    'sodium' => ['Sodium','mg'],
                    'potassium' => ['Potassium','mg'],
                    'calcium' => ['Calcium','mg'],
                    'iron' => ['Iron','mg'],
                    'vitaminA' => ['Vitamin A','IU'],
                    'vitaminC' => ['Vitamin C','mg'],
                ];
                foreach ($map as $k => [$label, $unit]) {
                    if (array_key_exists($k, $src) && is_numeric($src[$k])) {
                        $per[$label] = ['amount' => (float)$src[$k], 'unit' => $unit];
                    }
                }
                if ($per) {
                    $this->upsertNutrition($dbId, $per);
                }
            }
        } catch (\Throwable $e) { /* ignore nutrition persistence errors */ }

        return $ok;
    }

    /** Basic normalizer turning DB rows into provider-like array */
    public function normalize(array $row): array
    {
        $raw = [];
        if (!empty($row['raw_payload'])) {
            $decoded = is_array($row['raw_payload']) ? $row['raw_payload'] : json_decode((string)$row['raw_payload'], true);
            if (is_array($decoded)) $raw = $decoded;
        }
        $id = $raw['id'] ?? null;
        if ($id === null && !empty($row['api_id'])) {
            $id = $row['api_id'];
        }
        $out = [
            'id' => $id,
            'db_id' => isset($row['id']) ? (int)$row['id'] : null,
            'title' => $row['title'] ?? ($raw['title'] ?? 'Recipe'),
            'image' => $row['image_url'] ?? ($raw['image'] ?? null),
            'sourceUrl' => $row['source_url'] ?? ($raw['sourceUrl'] ?? null),
            'usedIngredients' => $raw['usedIngredients'] ?? [],
            'missedIngredients' => $raw['missedIngredients'] ?? [],
        ];
        // Preserve extra lists from API Ninjas if present
        if (isset($raw['ingredients_list']) && is_array($raw['ingredients_list'])) {
            $out['ingredients_list'] = $raw['ingredients_list'];
        }
        if (isset($raw['instructions_list']) && is_array($raw['instructions_list'])) {
            $out['instructions_list'] = $raw['instructions_list'];
        }
        if (isset($raw['servings'])) $out['servings'] = $raw['servings'];
        return $out;
    }

    /** Fetch a recipe by DB id */
    public function findById(int $id): array|false
    {
        $st = $this->db->prepare("SELECT * FROM recipes WHERE id = :id LIMIT 1");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /** Admin: update basic recipe fields (title, description, image_url, source_url). */
    public function updateBasic(int $id, array $data): bool
    {
        if ($id <= 0) return false;
        $fields = [];
        $params = [':id' => $id];
        $map = [
            'title' => PDO::PARAM_STR,
            'description' => PDO::PARAM_STR,
            'image_url' => PDO::PARAM_STR,
            'source_url' => PDO::PARAM_STR,
            'api_source' => PDO::PARAM_STR,
            'api_id' => PDO::PARAM_STR,
            'user_id' => PDO::PARAM_INT,
            'raw_payload' => PDO::PARAM_STR,
        ];
        foreach ($map as $k => $type) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = :$k";
                $params[":$k"] = $data[$k];
            }
        }
        if (!$fields) return false;
        $sql = "UPDATE recipes SET " . implode(', ', $fields) . " WHERE id = :id";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $type = $map[ltrim($k, ':')] ?? PDO::PARAM_STR;
            if ($k === ':id') $type = PDO::PARAM_INT;
            if ($v === null) $type = PDO::PARAM_NULL;
            $st->bindValue($k, $v, $type);
        }
        return $st->execute();
    }

    /** Admin: delete a recipe and cascading relations */
    public function deleteById(int $id): bool
    {
        if ($id <= 0) return false;
        $st = $this->db->prepare("DELETE FROM recipes WHERE id = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        return $st->execute();
    }

    /** Unsave (remove bookmark) */
    public function unsaveForUser(int $recipeId, int $userId): bool
    {
        $this->ensureSavedSchema();
        $st = $this->db->prepare("DELETE FROM saved_recipes WHERE user_id = :uid AND recipe_id = :rid");
        $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st->bindValue(':rid', $recipeId, PDO::PARAM_INT);
        return $st->execute();
    }

    /** Fetch stored per-serving nutrition for a recipe as [label => ['amount'=>..,'unit'=>..], ...] or null. */
    public function getNutrition(int $recipeId): ?array
    {
        if ($recipeId <= 0) return null;
        $this->ensureNutritionSchema();
        try {
            $st = $this->db->prepare("SELECT per_serving FROM recipe_nutrition WHERE recipe_id = :rid LIMIT 1");
            $st->bindValue(':rid', $recipeId, PDO::PARAM_INT);
            $st->execute();
            $json = $st->fetchColumn();
            if ($json === false || $json === null) return null;
            $arr = is_array($json) ? $json : json_decode((string)$json, true);
            return is_array($arr) ? $arr : null;
        } catch (\Throwable $e) {
            // If table truly doesn't exist and couldn't be created, fail gracefully
            return null;
        }
    }

    /** Upsert per-serving nutrition for a recipe. */
    public function upsertNutrition(int $recipeId, array $perServing): bool
    {
        if ($recipeId <= 0) return false;
        $this->ensureNutritionSchema();
        // Limit size to avoid huge payloads
        if (count($perServing) > 200) {
            $perServing = array_slice($perServing, 0, 200, true);
        }
        $json = json_encode($perServing, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        try {
            // Try update first
            $upd = $this->db->prepare("UPDATE recipe_nutrition SET per_serving = :json WHERE recipe_id = :rid");
            $upd->bindValue(':json', $json, PDO::PARAM_STR);
            $upd->bindValue(':rid', $recipeId, PDO::PARAM_INT);
            $upd->execute();
            if ($upd->rowCount() > 0) return true;
            // Insert
            $ins = $this->db->prepare("INSERT INTO recipe_nutrition (recipe_id, per_serving) VALUES (:rid, :json)");
            $ins->bindValue(':rid', $recipeId, PDO::PARAM_INT);
            $ins->bindValue(':json', $json, PDO::PARAM_STR);
            return $ins->execute();
        } catch (\Throwable $e) {
            try {
                // In case of race, try update again
                $upd2 = $this->db->prepare("UPDATE recipe_nutrition SET per_serving = :json WHERE recipe_id = :rid");
                $upd2->bindValue(':json', $json, PDO::PARAM_STR);
                $upd2->bindValue(':rid', $recipeId, PDO::PARAM_INT);
                return $upd2->execute();
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }

    /** Count how many recipes the user has saved. */
    public function countSavedForUser(int $userId): int
    {
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM saved_recipes WHERE user_id = :uid");
            $st->bindValue(':uid', $userId, PDO::PARAM_INT);
            $st->execute();
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
