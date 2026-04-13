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

    public function find(int $id, ?int $userId = null): array|false
    {
        $sql = "SELECT i.*, 
                       ing.name            AS ingredient_name,
                       ing.category        AS ingredient_category,
                       ing.image_url       AS ingredient_image_url,
                       ing.nutrition_info  AS ingredient_nutrition_info,
                       ing.api_source      AS ingredient_api_source,
                       ing.api_id          AS ingredient_api_id,
                       p.title             AS product_title,
                       p.brand             AS product_brand,
                       p.category          AS product_category,
                       p.image_url         AS product_image_url,
                       p.upc               AS product_upc,
                       p.nutrition_info    AS product_nutrition_info,
                       p.raw_payload       AS product_raw_payload,
                       p.api_source        AS product_api_source,
                       p.api_id            AS product_api_id
                FROM items i
                LEFT JOIN ingredients ing ON ing.id = i.ingredient_id
                LEFT JOIN products p      ON p.id = i.product_id
                WHERE i.id = :id";

        if ($userId !== null) {
            $sql .= " AND i.user_id = :user_id";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($userId !== null) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
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

        $lim = (int)$perPage; if ($lim < 1) { $lim = 25; } if ($lim > 100) { $lim = 100; }
        $off = (int)$offset; if ($off < 0) { $off = 0; }
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
                LIMIT $lim OFFSET $off";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
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

        $qty = (isset($data['quantity']) && $data['quantity'] !== null && $data['quantity'] !== '') ? $data['quantity'] : null;
        if ($qty === null) $st->bindValue(':quantity', null, PDO::PARAM_NULL);
        else               $st->bindValue(':quantity', $qty);
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
        return $this->find($id, $userId);
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

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM items WHERE id = :id AND user_id = :user_id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getExpirationStatus(?string $expirationDate): array
    {
        $status = 'In Stock';
        $badge = 'badge-success';

        if (!empty($expirationDate)) {
            try {
                $today = new \DateTimeImmutable('today');
                $exp = new \DateTimeImmutable($expirationDate);
                $diffDays = (int)$today->diff($exp)->format('%r%a');
                if ($diffDays < 0) {
                    $status = 'Expired ' . (abs($diffDays) === 1 ? '1 day ago' : abs($diffDays) . ' days ago');
                    $badge = 'badge-danger';
                } elseif ($diffDays === 0) {
                    $status = 'Expires today';
                    $badge = 'badge-warning';
                } elseif ($diffDays <= 3) {
                    $status = 'Expires in ' . ($diffDays === 1 ? '1 day' : $diffDays . ' days');
                    $badge = 'badge-warning';
                } else {
                    $status = 'Expires in ' . $diffDays . ' days';
                    $badge = 'badge-neutral';
                }
            } catch (\Exception $e) {
            }
        }

        return ['status' => $status, 'badge' => $badge];
    }

    public function stringifyCategory($cat): ?string
    {
        if ($cat === null || $cat === '') return null;

        if (is_string($cat)) {
            $trim = ltrim($cat);
            if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                $decoded = json_decode($cat, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->stringifyCategory($decoded);
                }
            }
            return $cat; // already a plain string
        }

        if (is_array($cat)) {
            // OFF sometimes gives category paths / arrays
            if (isset($cat['categoryPath']) && is_array($cat['categoryPath'])) {
                return implode(' › ', array_filter($cat['categoryPath'], 'is_string'));
            }
            // generic: collapse any stringy array
            $vals = [];
            foreach ($cat as $v) {
                if (is_string($v)) $vals[] = $v;
            }
            return $vals ? implode(' › ', $vals) : null;
        }

        return null;
    }

    public function normalizeNutrition($src): ?array
    {
        if (!is_array($src) || !$src) return null;

        // If we received a bare FDC-style list of FoodNutrient entries, wrap it.
        if (isset($src[0]) && is_array($src[0]) && (isset($src[0]['nutrient']) || isset($src[0]['nutrientName']) || (isset($src[0]['type']) && $src[0]['type'] === 'FoodNutrient'))) {
            $src = ['foodNutrients' => $src];
        }

        // Already in target form?
        if (isset($src['nutrients']) && is_array($src['nutrients'])) {
            return $src;
        }

        // ---------- FatSecret: food.servings ----------
        if (isset($src['servings']['serving']) && is_array($src['servings']['serving'])) {
            $servings = $src['servings']['serving'];
            // API can return a single object instead of an array if there's only one serving
            if (isset($servings['serving_id'])) {
                $servings = [$servings];
            }
            $serving = $servings[0] ?? null;
            foreach ($servings as $s) {
                if (!empty($s['is_default'])) {
                    $serving = $s;
                    break;
                }
            }
            
            if ($serving) {
                $map = [
                    'calories'            => ['Calories',        'kcal'],
                    'protein'             => ['Protein',          'g'],
                    'carbohydrate'        => ['Carbohydrates',    'g'],
                    'fat'                 => ['Fat',              'g'],
                    'saturated_fat'       => ['Saturated Fat',    'g'],
                    'polyunsaturated_fat' => ['Polyunsaturated Fat', 'g'],
                    'monounsaturated_fat' => ['Monounsaturated Fat', 'g'],
                    'trans_fat'           => ['Trans Fat',        'g'],
                    'fiber'               => ['Fiber',            'g'],
                    'sugar'               => ['Sugar',            'g'],
                    'sodium'              => ['Sodium',           'mg'],
                    'potassium'           => ['Potassium',        'mg'],
                    'cholesterol'         => ['Cholesterol',      'mg'],
                    'calcium'             => ['Calcium',          'mg'],
                    'iron'                => ['Iron',             'mg'],
                    'vitamin_a'           => ['Vitamin A',        'IU'],
                    'vitamin_c'           => ['Vitamin C',        'mg'],
                ];

                $nutrients = [];
                foreach ($map as $key => [$label, $unit]) {
                    if (isset($serving[$key]) && $serving[$key] !== '' && $serving[$key] !== null) {
                        $amount = (float)$serving[$key];
                        if ($amount > 0 || $key === 'calories' || $amount !== 0.0) {
                            $nutrients[] = ['name' => $label, 'amount' => $amount, 'unit' => $unit];
                        }
                    }
                }
                
                $servingText = $serving['serving_description'] ?? 'per serving';
                return $nutrients ? ['nutrients' => $nutrients, 'servings' => ['original' => $servingText]] : null;
            }
        }

        // ---------- FDC: labelNutrients ----------
        if (isset($src['labelNutrients']) && is_array($src['labelNutrients'])) {
            $ln = $src['labelNutrients'];
            $get = fn($k) => isset($ln[$k]['value']) ? (float)$ln[$k]['value'] : null;
            $nutrients = [
                ['name' => 'Calories', 'amount' => $get('calories'), 'unit' => 'kcal'],
                ['name' => 'Protein', 'amount' => $get('protein'), 'unit' => 'g'],
                ['name' => 'Fat', 'amount' => $get('fat'), 'unit' => 'g'],
                ['name' => 'Saturated Fat', 'amount' => $get('saturatedFat'), 'unit' => 'g'],
                ['name' => 'Carbohydrates', 'amount' => $get('carbohydrates'), 'unit' => 'g'],
                ['name' => 'Fiber', 'amount' => $get('fiber'), 'unit' => 'g'],
                ['name' => 'Sugar', 'amount' => $get('sugars'), 'unit' => 'g'],
                ['name' => 'Sodium', 'amount' => $get('sodium'), 'unit' => 'mg'],
                ['name' => 'Calcium', 'amount' => $get('calcium'), 'unit' => 'mg'],
                ['name' => 'Iron', 'amount' => $get('iron'), 'unit' => 'mg'],
                ['name' => 'Potassium', 'amount' => $get('potassium'), 'unit' => 'mg'],
                ['name' => 'Cholesterol', 'amount' => $get('cholesterol'), 'unit' => 'mg'],
            ];
            $nutrients = array_values(array_filter($nutrients, fn($n) => $n['amount'] !== null));

            $servingText = null;
            if (isset($src['servingSize'], $src['servingSizeUnit'])) {
                $servingText = $src['servingSize'] . ' ' . $src['servingSizeUnit'];
            } elseif (isset($src['householdServingFullText'])) {
                $servingText = $src['householdServingFullText'];
            }

            return $nutrients ? ['nutrients' => $nutrients, 'servings' => ['original' => $servingText ?? 'per serving']] : null;
        }

        // ---------- FDC: foodNutrients ----------
        if (isset($src['foodNutrients']) && is_array($src['foodNutrients'])) {
            $core = [
                'Energy' => ['Calories', 'kcal'],
                'Energy (Atwater General Factors)' => ['Calories', 'kcal'],
                'Protein' => ['Protein', 'g'],
                'Total lipid (fat)' => ['Fat', 'g'],
                'Carbohydrate, by difference' => ['Carbohydrates', 'g'],
                'Fiber, total dietary' => ['Fiber', 'g'],
                'Sugars, total including NLEA' => ['Sugar', 'g'],
                'Sugars, total' => ['Sugar', 'g'],
                'Sodium, Na' => ['Sodium', 'mg'],
                'Calcium, Ca' => ['Calcium', 'mg'],
                'Iron, Fe' => ['Iron', 'mg'],
                'Potassium, K' => ['Potassium', 'mg'],
                'Cholesterol' => ['Cholesterol', 'mg'],
            ];
            $bucket = [];
            $extras = [];
            foreach ($src['foodNutrients'] as $fn) {
                $name = $fn['nutrient']['name'] ?? $fn['nutrientName'] ?? null;
                $amount = $fn['amount'] ?? $fn['value'] ?? null;
                $unit = $fn['nutrient']['unitName'] ?? $fn['unitName'] ?? null;
                if (!$name || $amount === null) continue;
                $out = [
                    'name' => $core[$name][0] ?? $name,
                    'amount' => (float)$amount,
                    'unit' => $unit ?: ($core[$name][1] ?? ''),
                ];
                if (isset($core[$name])) {
                    $bucket[$out['name']] = $out;
                } else {
                    $extras[] = $out;
                }
            }
            $list = array_values($bucket);
            foreach ($extras as $ex) {
                if (!isset($bucket[$ex['name']])) $list[] = $ex;
            }
            if ($list) {
                $servingText = null;
                if (isset($src['servingSize'], $src['servingSizeUnit'])) {
                    $servingText = $src['servingSize'] . ' ' . $src['servingSizeUnit'];
                } elseif (isset($src['householdServingFullText'])) {
                    $servingText = $src['householdServingFullText'];
                }
                if (count($list) > 200) $list = array_slice($list, 0, 200);
                return ['nutrients' => $list, 'servings' => ['original' => $servingText ?? 'per 100 g']];
            }
        }

        // ---------- OFF: nutriments ----------
        $nutriments = $src['nutriments'] ?? null;
        if (!$nutriments && $this->looksLikeOffNutriments($src)) {
            $nutriments = $src;
        }
        if (is_array($nutriments)) {
            $pick = function (string $base) use ($nutriments) {
                if (isset($nutriments[$base . '_serving'])) return ['v' => (float)$nutriments[$base . '_serving'], 'scope' => 'per serving'];
                if (isset($nutriments[$base . '_100g'])) return ['v' => (float)$nutriments[$base . '_100g'], 'scope' => 'per 100 g'];
                return null;
            };
            $unit = function (string $base, string $default) use ($nutriments) {
                $k = $base . '_unit';
                return isset($nutriments[$k]) && is_string($nutriments[$k]) ? $nutriments[$k] : $default;
            };

            $map = [
                ['Calories', 'energy-kcal', 'kcal'],
                ['Protein', 'proteins', 'g'],
                ['Fat', 'fat', 'g'],
                ['Saturated Fat', 'saturated-fat', 'g'],
                ['Carbohydrates', 'carbohydrates', 'g'],
                ['Fiber', 'fiber', 'g'],
                ['Sugar', 'sugars', 'g'],
                ['Sodium', 'sodium', 'mg'],
                ['Calcium', 'calcium', 'mg'],
                ['Iron', 'iron', 'mg'],
                ['Potassium', 'potassium', 'mg'],
            ];

            $scope = null;
            $out = [];
            $taken = [];
            foreach ($map as [$name, $base, $defUnit]) {
                $picked = $pick($base);
                if ($picked) {
                    $out[] = ['name' => $name, 'amount' => $picked['v'], 'unit' => $unit($base, $defUnit)];
                    $scope = $scope ?? $picked['scope'];
                    $taken[$base] = true;
                }
            }
            foreach ($nutriments as $key => $val) {
                if (!is_scalar($val)) continue;
                if (preg_match('/^(.+?)_(serving|100g)$/', (string)$key, $m)) {
                    $base = $m[1];
                    if (isset($taken[$base])) continue;
                    $v = (float)$val;
                    if (!is_finite($v)) continue;
                    $nm = ucwords(str_replace(['-', '_'], [' ', ' '], $base));
                    $out[] = ['name' => $nm, 'amount' => $v, 'unit' => $unit($base, '')];
                }
            }
            if ($out) {
                if (count($out) > 200) $out = array_slice($out, 0, 200);
                return ['nutrients' => $out, 'servings' => ['original' => $scope ?? 'per 100 g']];
            }
        }

        // Flat label-like structure
        $flatKeys = ['calories', 'protein', 'fat', 'saturatedFat', 'transFat', 'carbohydrates', 'fiber', 'sugars', 'sodium', 'calcium', 'iron', 'potassium', 'cholesterol', 'addedSugars'];
        $hasFlat = false;
        foreach ($flatKeys as $k) {
            if (isset($src[$k]) && is_array($src[$k]) && array_key_exists('value', $src[$k])) {
                $hasFlat = true;
                break;
            }
        }
        if ($hasFlat) {
            $get = function (string $k) use ($src) {
                return isset($src[$k]['value']) ? (float)$src[$k]['value'] : null;
            };
            $nutrients = [
                ['name' => 'Calories', 'amount' => $get('calories'), 'unit' => 'kcal'],
                ['name' => 'Protein', 'amount' => $get('protein'), 'unit' => 'g'],
                ['name' => 'Fat', 'amount' => $get('fat'), 'unit' => 'g'],
                ['name' => 'Saturated Fat', 'amount' => $get('saturatedFat'), 'unit' => 'g'],
                ['name' => 'Trans Fat', 'amount' => $get('transFat'), 'unit' => 'g'],
                ['name' => 'Carbohydrates', 'amount' => $get('carbohydrates'), 'unit' => 'g'],
                ['name' => 'Fiber', 'amount' => $get('fiber'), 'unit' => 'g'],
                ['name' => 'Sugar', 'amount' => $get('sugars'), 'unit' => 'g'],
                ['name' => 'Added Sugars', 'amount' => $get('addedSugars'), 'unit' => 'g'],
                ['name' => 'Sodium', 'amount' => $get('sodium'), 'unit' => 'mg'],
                ['name' => 'Calcium', 'amount' => $get('calcium'), 'unit' => 'mg'],
                ['name' => 'Iron', 'amount' => $get('iron'), 'unit' => 'mg'],
                ['name' => 'Potassium', 'amount' => $get('potassium'), 'unit' => 'mg'],
                ['name' => 'Cholesterol', 'amount' => $get('cholesterol'), 'unit' => 'mg'],
            ];
            $nutrients = array_values(array_filter($nutrients, fn($n) => $n['amount'] !== null));
            return $nutrients ? ['nutrients' => $nutrients, 'servings' => ['original' => 'per serving']] : null;
        }

        return null;
    }

    private function looksLikeOffNutriments(array $a): bool
    {
        foreach (['energy-kcal_100g', 'fat_100g', 'proteins_100g', 'carbohydrates_100g'] as $k) {
            if (array_key_exists($k, $a)) return true;
        }
        return false;
    }
}
