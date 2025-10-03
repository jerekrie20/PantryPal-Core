<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Recipes;
use Models\Updates;
use Models\User;

class AdminController
{
    private User $users;
    private Items $items;
    private Recipes $recipes;
    private Updates $updates;

    private function parseArrayInput(string $input): array
    {
        $input = trim($input);
        if ($input === '') return [];
        $first = substr($input, 0, 1);
        if ($first === '[' || $first === '{') {
            $arr = json_decode($input, true);
            if (is_array($arr)) {
                // If object provided, try to extract values
                if (array_keys($arr) !== range(0, count($arr)-1)) {
                    return array_values(array_filter($arr, fn($v)=>is_scalar($v) || is_array($v)));
                }
                return array_values(array_filter(array_map(fn($v)=> is_scalar($v) ? trim((string)$v) : $v, $arr), fn($v)=> $v !== '' && $v !== null));
            }
        }
        // Fallback: split by new lines and commas
        $tokens = [];
        // Split by newlines first
        $lines = preg_split('/\r?\n/', $input);
        foreach ($lines as $line) {
            // Further split by commas to allow comma-separated entries
            $parts = preg_split('/,/', (string)$line);
            foreach ($parts as $p) {
                $t = trim((string)$p);
                if ($t !== '') $tokens[] = $t;
            }
        }
        return $tokens;
    }

    private function parseObjectInput(string $input): array|null
    {
        $input = trim($input);
        if ($input === '') return null;
        $first = substr($input, 0, 1);
        if ($first === '{' || $first === '[') {
            $obj = json_decode($input, true);
            if (is_array($obj)) return $obj;
        }
        return null;
    }

    /**
     * Nutrition input parser: accepts either JSON object or human-friendly lines.
     * Human format examples (one per line or comma-separated):
     *   Calories: 250 kcal
     *   Protein 12 g
     *   Fiber=5 g
     * Returns [label => ['amount'=>float, 'unit'=>string|null], ...]
     */
    private function parseNutritionInput(string $input): ?array
    {
        $input = trim($input);
        if ($input === '') return null;
        $first = substr($input, 0, 1);
        if ($first === '{' || $first === '[') {
            $obj = json_decode($input, true);
            if (is_array($obj)) return $obj; // assume already in correct structure
        }
        $out = [];
        $lines = preg_split('/\r?\n/', $input);
        foreach ($lines as $line) {
            // Allow multiple entries comma-separated on a single line
            $entries = preg_split('/,/', (string)$line);
            foreach ($entries as $entry) {
                $e = trim((string)$entry);
                if ($e === '') continue;
                // Patterns like: Label: 250 kcal | Label 12 g | Label - 5 mg | Label=10 %
                if (preg_match('/^\s*([^:=-]+?)\s*(?:[:=-])?\s*([0-9]+(?:\.[0-9]+)?)\s*([a-zA-Z%]+)?\s*$/', $e, $m)) {
                    $label = trim($m[1]);
                    $amount = (float)$m[2];
                    $unit = isset($m[3]) && $m[3] !== '' ? $m[3] : null;
                    if ($label !== '') {
                        $out[$label] = ['amount' => $amount, 'unit' => $unit];
                    }
                }
            }
        }
        return $out ?: null;
    }

    public function __construct()
    {
        $this->users = new User();
        $this->items = new Items();
        $this->recipes = new Recipes();
        $this->updates = new Updates();
    }

    public function index(): string
    {
        return View::render('Admin/index', [
            'title' => 'Admin Dashboard'
        ]);
    }

    public function users(): string
    {
        // Filters
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $role = isset($_GET['role']) ? (string)$_GET['role'] : '';
        $isAdmin = $role === 'admin' ? '1' : ($role === 'user' ? '0' : '');
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, min(100, (int)$_GET['perPage'])) : 25;

        $result = $this->users->listPaged([
            'q' => $q,
            'is_admin' => $isAdmin,
        ], $page, $perPage);

        return View::render('Admin/users', [
            'title' => 'All Users',
            'users' => $result['rows'],
            'pagination' => $result['pagination'],
            'filters' => [
                'q' => $q,
                'role' => $role,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function userToggleAdmin(int $id)
    {
        // flip is_admin for the user
        $this->users->toggleAdmin($id);
        header('Location: /admin/users');
        exit();
    }

    public function userDelete(int $id)
    {
        $this->users->deleteById($id);
        header('Location: /admin/users');
        exit();
    }

    public function items(): string
    {
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $userId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, min(100, (int)$_GET['perPage'])) : 25;
        $data = $this->items->findAllAdminPaged([
            'q' => $q,
            'user_id' => $userId,
        ], $page, $perPage);
        return View::render('Admin/items', [
            'title' => 'All Items',
            'items' => $data['items'],
            'pagination' => $data['pagination'],
            'filters' => [
                'q' => $q,
                'user_id' => $userId,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function itemEdit(int $id): string
    {
        $row = $this->items->find($id);
        if (!$row) {
            http_response_code(404);
            return View::render('Pages/404', ['title' => 'Not Found']);
        }
        return View::render('Admin/item_form', [
            'title' => 'Edit Item',
            'item' => $row
        ]);
    }

    public function itemUpdate(int $id)
    {
        $data = [
            'ingredient_id' => $_POST['ingredient_id'] !== '' ? (int)$_POST['ingredient_id'] : null,
            'product_id' => $_POST['product_id'] !== '' ? (int)$_POST['product_id'] : null,
            'quantity' => $_POST['quantity'] ?? null,
            'unit' => $_POST['unit'] ?? null,
            'purchase_date' => $_POST['purchase_date'] ?? null,
            'expiration_date' => $_POST['expiration_date'] ?? null,
            'entered_name' => $_POST['entered_name'] ?? null,
            'entered_brand' => $_POST['entered_brand'] ?? null,
        ];
        $this->items->update($id, $data, null); // admin: no user guard
        header('Location: /admin/items');
        exit();
    }

    public function itemDelete(int $id)
    {
        $this->items->delete($id, null); // admin delete
        header('Location: /admin/items');
        exit();
    }

    public function recipes(): string
    {
        // Filters
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $source = isset($_GET['source']) ? trim((string)$_GET['source']) : '';
        $ownerId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, min(100, (int)$_GET['perPage'])) : 25;

        $result = $this->recipesListPaged([
            'q' => $q,
            'source' => $source,
            'user_id' => $ownerId,
        ], $page, $perPage);

        return View::render('Admin/recipes', [
            'title' => 'All Recipes',
            'recipes' => $result['rows'],
            'pagination' => $result['pagination'],
            'filters' => [
                'q' => $q,
                'source' => $source,
                'user_id' => $ownerId,
                'perPage' => $perPage,
            ],
        ]);
    }

    private function recipesListStmt(int $limit)
    {
        $ref = new \ReflectionClass($this->recipes);
        $dbProp = $ref->getProperty('db');
        $dbProp->setAccessible(true);
        $db = $dbProp->getValue($this->recipes);
        $lim = (int)$limit; if ($lim < 1) { $lim = 25; } if ($lim > 1000) { $lim = 1000; }
        $st = $db->prepare("SELECT * FROM recipes ORDER BY created_at DESC, id DESC LIMIT $lim");
        $st->execute();
        return $st;
    }

    private function recipesListPaged(array $filters, int $page, int $perPage): array
    {
        $ref = new \ReflectionClass($this->recipes);
        $dbProp = $ref->getProperty('db');
        $dbProp->setAccessible(true);
        /** @var \PDO $db */
        $db = $dbProp->getValue($this->recipes);

        $where = [];
        $params = [];
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(title LIKE :q OR api_id LIKE :q OR source_url LIKE :q OR description LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }
        $source = trim((string)($filters['source'] ?? ''));
        if ($source !== '') {
            $where[] = "(api_source = :src)";
            $params[':src'] = $source;
        }
        $ownerId = $filters['user_id'] ?? null;
        if ($ownerId !== null) {
            $where[] = "(user_id = :uid)";
            $params[':uid'] = (int)$ownerId;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count
        $countSql = "SELECT COUNT(*) FROM recipes $whereSql";
        $stc = $db->prepare($countSql);
        foreach ($params as $k => $v) {
            $stc->bindValue($k, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stc->execute();
        $total = (int)$stc->fetchColumn();

        // Page
        $offset = ($page - 1) * $perPage;
        $lim = (int)$perPage; if ($lim < 1) { $lim = 25; } if ($lim > 100) { $lim = 100; }
        $off = (int)$offset; if ($off < 0) { $off = 0; }
        $sql = "SELECT * FROM recipes $whereSql ORDER BY created_at DESC, id DESC LIMIT $lim OFFSET $off";
        $st = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $st->execute();
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $totalPages = (int)max(1, ceil($total / max(1, $perPage)));

        return [
            'rows' => $rows,
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'totalItems' => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }

    public function recipeCreate(): string
    {
        return View::render('Admin/recipe_form', [
            'title' => 'Add Recipe'
        ]);
    }

    public function recipeEdit(int $id): string
    {
        $row = $this->recipes->findById($id);
        if (!$row) { http_response_code(404); return View::render('Pages/404', ['title' => 'Not Found']); }
        $raw = [];
        if (!empty($row['raw_payload'])) {
            $decoded = is_array($row['raw_payload']) ? $row['raw_payload'] : json_decode((string)$row['raw_payload'], true);
            if (is_array($decoded)) $raw = $decoded;
        }
        // Pull per-serving nutrition if available
        $nutrition = null;
        try { $nutrition = $this->recipes->getNutrition($id); } catch (\Throwable $e) { $nutrition = null; }
        return View::render('Admin/recipe_form', [
            'title' => 'Edit Recipe',
            'input' => [
                'title' => $row['title'] ?? '',
                'description' => $row['description'] ?? '',
                'image_url' => $row['image_url'] ?? '',
                'source_url' => $row['source_url'] ?? '',
                'api_source' => $row['api_source'] ?? '',
                'api_id' => $row['api_id'] ?? '',
                'user_id' => $row['user_id'] ?? '',
                'ingredients_list' => $raw['ingredients_list'] ?? ($raw['usedIngredients'] ?? null),
                'instructions_list' => $raw['instructions_list'] ?? ($raw['instructions'] ?? null),
                'nutrition_per_serving' => $nutrition,
            ],
            'recipe_id' => $id
        ]);
    }

    public function recipeUpdate(int $id)
    {
        $apiSource = trim((string)($_POST['api_source'] ?? ''));
        $apiId = trim((string)($_POST['api_id'] ?? ''));
        $userId = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;
        $data = [
            'title' => trim((string)($_POST['title'] ?? '')),
            'description' => $_POST['description'] ?? null,
            'image_url' => $_POST['image_url'] ?? null,
            'source_url' => $_POST['source_url'] ?? null,
            'api_source' => $apiSource !== '' ? $apiSource : null,
            'api_id' => $apiId !== '' ? $apiId : null,
            'user_id' => $userId,
        ];
        $this->recipes->updateBasic($id, $data);

        // Merge and update raw_payload with ingredients/instructions
        $row = $this->recipes->findById($id);
        $raw = [];
        if ($row && !empty($row['raw_payload'])) {
            $decoded = is_array($row['raw_payload']) ? $row['raw_payload'] : json_decode((string)$row['raw_payload'], true);
            if (is_array($decoded)) $raw = $decoded;
        }
        $ingInput = trim((string)($_POST['ingredients_list'] ?? ''));
        $instrInput = trim((string)($_POST['instructions_list'] ?? ''));
        if ($ingInput !== '') { $raw['ingredients_list'] = $this->parseArrayInput($ingInput); }
        if ($instrInput !== '') { $raw['instructions_list'] = $this->parseArrayInput($instrInput); }
        if (!empty($raw)) {
            $json = json_encode($raw, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $this->recipes->updateBasic($id, ['raw_payload' => $json]);
        }

        // Nutrition per serving
        $nutritionInput = trim((string)($_POST['nutrition_per_serving'] ?? ''));
        if ($nutritionInput !== '') {
            $per = $this->parseNutritionInput($nutritionInput);
            if (is_array($per)) { $this->recipes->upsertNutrition($id, $per); }
        }

        header('Location: /admin/recipes');
        exit();
    }

    public function recipeStore()
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) { session_start(); }
        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '') {
            return View::render('Admin/recipe_form', ['title' => 'Add Recipe', 'error' => 'Title is required', 'input' => $_POST]);
        }
        $apiSource = trim((string)($_POST['api_source'] ?? 'manual'));
        $apiId = trim((string)($_POST['api_id'] ?? ''));
        $ownerId = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : ((int)($_SESSION['user_id'] ?? 0) ?: null);

        // Parse ingredients and instructions (JSON array or one per line)
        $ingInput = trim((string)($_POST['ingredients_list'] ?? ''));
        $instrInput = trim((string)($_POST['instructions_list'] ?? ''));
        $ingredients = null; $instructions = null;
        if ($ingInput !== '') {
            $ingredients = $this->parseArrayInput($ingInput);
        }
        if ($instrInput !== '') {
            $instructions = $this->parseArrayInput($instrInput);
        }

        $data = [
            'title' => $title,
            'description' => $_POST['description'] ?? null,
            'image' => $_POST['image_url'] ?? null,
            'sourceUrl' => $_POST['source_url'] ?? null,
            'id' => $apiId !== '' ? $apiId : null,
            'ingredients_list' => $ingredients,
            'instructions_list' => $instructions,
        ];
        $rid = $this->recipes->upsertFromProvider($data, $ownerId, $apiSource !== '' ? $apiSource : 'manual');

        // Nutrition per serving (JSON object or line-based)
        $nutritionInput = trim((string)($_POST['nutrition_per_serving'] ?? ''));
        if ($rid && $nutritionInput !== '') {
            $per = $this->parseNutritionInput($nutritionInput);
            if (is_array($per)) {
                $this->recipes->upsertNutrition((int)$rid, $per);
            }
        }

        header('Location: /admin/recipes');
        exit();
    }

    public function recipeDelete(int $id)
    {
        $this->recipes->deleteById($id);
        header('Location: /admin/recipes');
        exit();
    }

    public function updatesIndex(): string
    {
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $isActive = isset($_GET['is_active']) ? (string)$_GET['is_active'] : '';
        $targetUserId = isset($_GET['target_user_id']) && $_GET['target_user_id'] !== '' ? (int)$_GET['target_user_id'] : null;
        $createdBy = isset($_GET['created_by']) && $_GET['created_by'] !== '' ? (int)$_GET['created_by'] : null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, min(100, (int)$_GET['perPage'])) : 25;

        $result = $this->updates->listPaged([
            'q' => $q,
            'is_active' => $isActive,
            'target_user_id' => $targetUserId,
            'created_by' => $createdBy,
        ], $page, $perPage);

        return View::render('Admin/updates', [
            'title' => 'Updates',
            'updates' => $result['rows'],
            'pagination' => $result['pagination'],
            'filters' => [
                'q' => $q,
                'is_active' => $isActive,
                'target_user_id' => $targetUserId,
                'created_by' => $createdBy,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function updatesStore()
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) { session_start(); }
        $title = trim((string)($_POST['title'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        if ($title === '' || $message === '') {
            return View::render('Admin/updates_form', [
                'title' => 'Post Update',
                'error' => 'Title and message are required.',
                'input' => $_POST
            ]);
        }
        $target = isset($_POST['target_user_id']) && $_POST['target_user_id'] !== '' ? (int)$_POST['target_user_id'] : null;
        $id = $this->updates->create([
            'target_user_id' => $target,
            'title' => $title,
            'message' => $message,
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            'created_by' => (int)($_SESSION['user_id'] ?? 0)
        ]);
        header('Location: /admin/updates');
        exit();
    }

    public function updatesCreate(): string
    {
        return View::render('Admin/updates_form', [
            'title' => 'Post Update'
        ]);
    }

    public function updatesEdit(int $id): string
    {
        $row = $this->updates->findById($id);
        if (!$row) { http_response_code(404); return View::render('Pages/404', ['title' => 'Not Found']); }
        return View::render('Admin/updates_form', [
            'title' => 'Edit Update',
            'input' => $row,
            'updateId' => $id,
        ]);
    }

    public function updatesUpdate(int $id)
    {
        $title = trim((string)($_POST['title'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        if ($title === '' || $message === '') {
            return View::render('Admin/updates_form', [
                'title' => 'Edit Update',
                'error' => 'Title and message are required.',
                'input' => $_POST + ['id' => $id],
                'updateId' => $id,
            ]);
        }
        $target = isset($_POST['target_user_id']) && $_POST['target_user_id'] !== '' ? (int)$_POST['target_user_id'] : null;
        $this->updates->update($id, [
            'target_user_id' => $target,
            'title' => $title,
            'message' => $message,
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ]);
        header('Location: /admin/updates');
        exit();
    }

    public function updatesDelete(int $id)
    {
        $this->updates->delete($id);
        header('Location: /admin/updates');
        exit();
    }
}
