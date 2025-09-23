<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Models\Products;
use Models\Recipes;
use Services\Recipes\SpoonacularProvider;
use Services\Recipes\SuggesticProvider;
use Services\Recipes\RecipesProvider;

class RecipesController
{
    private RecipesProvider $provider; // default provider (now API Ninjas)
    private ?SpoonacularProvider $spoon = null; // optional fallback
    private Items $items;
    private Ingredients $ingredients;
    private Products $products;
    private Recipes $recipes;

    public function __construct()
    {
        // Use Suggestic as the default provider
        $this->provider = new SuggesticProvider();
        // Temporarily disable Spoonacular completely to avoid quota/limit errors.
        // Leave the property in place but set to null so guarded calls are no-ops.
        $this->spoon = null;
        $this->items = new Items();
        $this->ingredients = new Ingredients();
        $this->products = new Products();
        $this->recipes = new Recipes();
    }

    /** GET /recipes?q=... — general search form + results */
    public function index(): string
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
            $forceApi = isset($_GET['api']) && (string)$_GET['api'] === '1';
            $browse = isset($_GET['browse']) && (string)$_GET['browse'] === '1';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            // Default to 10 per page for web search; cap to provider limit (30)
            $perPage = isset($_GET['perPage']) ? max(1, min(30, (int)$_GET['perPage'])) : 10;
            $filters = [
                'diet' => $_GET['diet'] ?? null,
                'cuisine' => $_GET['cuisine'] ?? null,
                'type' => $_GET['type'] ?? null,
                'intolerances' => isset($_GET['intolerances']) ? (is_array($_GET['intolerances']) ? $_GET['intolerances'] : explode(',', (string)$_GET['intolerances'])) : null,
                'maxReadyTime' => isset($_GET['maxReadyTime']) ? (int)$_GET['maxReadyTime'] : null,
                'sort' => $_GET['sort'] ?? null,
            ];

            $recipes = [];
            $error = null;
            $mode = 'saved';
            $pagination = null;

            // Determine source label for the active provider
            $srcName = ($this->provider instanceof SuggesticProvider) ? 'suggestic' : 'spoonacular';

            if ($browse) {
                $mode = 'browse_api';
                $total = 0;
                $results = [];
                $didApi = false;
                if (method_exists($this->provider, 'browseAll') && $this->provider->isConfigured()) {
                    $resp = $this->provider->browseAll($filters, $page, $perPage);
                    $results = $resp['results'] ?? [];
                    $total = (int)($resp['total'] ?? 0);
                    $didApi = true;
                }
                // Fallback to Spoonacular browse if available
                if (!$didApi && $this->spoon && method_exists($this->spoon, 'browseAll') && $this->spoon->isConfigured()) {
                    $resp = $this->spoon->browseAll($filters, $page, $perPage);
                    $results = $resp['results'] ?? [];
                    $total = (int)($resp['total'] ?? 0);
                    $didApi = true;
                }
                if (!$didApi) {
                    $error = 'No live recipe API configured. Set SUGGESTIC_API_KEY (preferred) or SPOONACULAR_API_KEY to enable live results.';
                }
                $recipes = [];
                foreach ($results as $r) {
                    try {
                        $src = isset($r['id']) && $r['id'] ? 'spoonacular' : 'suggestic';
                        $id = $this->recipes->upsertFromProvider($r, null, $src);
                        $r['db_id'] = $id;
                    } catch (\Throwable $e) { /* ignore */ }
                    $recipes[] = $r;
                }
                // Build pagination if total provided (>0)
                if ($total > 0) {
                    $totalPages = (int)max(1, ceil($total / $perPage));
                    $pagination = [
                        'currentPage' => $page,
                        'perPage' => $perPage,
                        'total' => $total,
                        'totalPages' => $totalPages,
                    ];
                } else {
                    // Unknown total: still provide basic page/perPage
                    $pagination = [
                        'currentPage' => $page,
                        'perPage' => $perPage,
                        'total' => null,
                        'totalPages' => null,
                    ];
                }
            } elseif ($q === '') {
                // Default: show user's saved recipes from DB
                $recipes = $this->recipes->getSavedForUser((int)$userId, 24);
            } else {
                if ($forceApi) {
                    $didApi = false;
                    if ($this->provider->isConfigured()) {
                        // Use provider-paged search to get exactly the requested page and know if there is a next page
                        if (method_exists($this->provider, 'searchPaged')) {
                            $resp = $this->provider->searchPaged($q, $page, $perPage);
                            $apiResults = $resp['results'] ?? [];
                            $hasNext = (bool)($resp['hasNext'] ?? false);
                        } else {
                            // Fallback to single page (no cursor) — behaves like page 1 only
                            $apiResults = $this->provider->searchByQuery($q, $perPage);
                            $hasNext = false;
                        }
                        $recipes = [];
                        foreach ($apiResults as $r) {
                            try { $id = $this->recipes->upsertFromProvider($r, null, $srcName); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                            $recipes[] = $r;
                        }
                        $mode = 'search_api';
                        $didApi = true;
                        // Provide pagination with unknown total but with hasNext hint
                        $pagination = [
                            'currentPage' => $page,
                            'perPage' => $perPage,
                            'total' => null,
                            'totalPages' => $hasNext ? null : $page, // if no next page, cap at current
                            'hasNext' => $hasNext,
                        ];
                    }
                    // Fallback to Spoonacular forced search
                    if (!$didApi && $this->spoon && $this->spoon->isConfigured()) {
                        $apiResults = $this->spoon->searchByQuery($q, $perPage);
                        $recipes = [];
                        foreach ($apiResults as $r) {
                            try { $id = $this->recipes->upsertFromProvider($r, null, 'spoonacular'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                            $recipes[] = $r;
                        }
                        $mode = 'search_api';
                        $didApi = true;
                        $pagination = [
                            'currentPage' => $page,
                            'perPage' => $perPage,
                            'total' => null,
                            'totalPages' => null,
                        ];
                    }
                    if (!$didApi) {
                        $error = 'No live recipe API configured. Set SUGGESTIC_API_KEY (preferred) or SPOONACULAR_API_KEY to enable live results.';
                        // fall back to local
                        $recipes = $this->recipes->searchLocalByQuery($q, $perPage);
                        $mode = 'search';
                    }
                } else {
                    // Local-first search
                    $recipes = $this->recipes->searchLocalByQuery($q, $perPage);
                    $seen = [];
                    foreach ($recipes as $r) {
                        $dbid = $r['db_id'] ?? null; $k = $dbid ? ('db:' . $dbid) : ('title:' . ($r['title'] ?? ''));
                        $seen[$k] = true;
                    }
                    if (count($recipes) < $perPage) {
                        $needed = $perPage - count($recipes);
                        if ($this->provider->isConfigured()) {
                            $apiResults = $this->provider->searchByQuery($q, $needed);
                            foreach ($apiResults as $r) {
                                try { $id = $this->recipes->upsertFromProvider($r, null, $srcName); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                                $k = isset($r['db_id']) ? ('db:' . $r['db_id']) : ('title:' . ($r['title'] ?? ''));
                                if (!isset($seen[$k])) { $recipes[] = $r; $seen[$k] = true; }
                            }
                        }
                        // Fallback to Spoonacular if still underfilled
                        if (count($recipes) < $perPage && $this->spoon && $this->spoon->isConfigured()) {
                            $needed = $perPage - count($recipes);
                            $apiResults = $this->spoon->searchByQuery($q, $needed);
                            foreach ($apiResults as $r) {
                                try { $id = $this->recipes->upsertFromProvider($r, null, 'spoonacular'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                                $k = isset($r['db_id']) ? ('db:' . $r['db_id']) : ('title:' . ($r['title'] ?? ''));
                                if (!isset($seen[$k])) { $recipes[] = $r; $seen[$k] = true; }
                            }
                        }
                    }
                    if (empty($recipes) && !$this->provider->isConfigured() && !($this->spoon && $this->spoon->isConfigured())) {
                        $error = 'No live recipe API configured. Set SUGGESTIC_API_KEY (preferred) or SPOONACULAR_API_KEY to enable live results.';
                    }
                    $mode = 'search';
                }
            }

            return View::render('Recipes/index', [
                'title' => $browse ? 'Browse Recipes' : 'Find Recipes',
                'query' => $q,
                'recipes' => $recipes,
                'error' => $error,
                'mode' => $mode,
                'filters' => $filters,
                'pagination' => $pagination,
            ]);
        } catch (\Throwable $e) {
            error_log('RecipesController::index error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** GET /recipes/suggested — use user's pantry ingredients to suggest */
    public function suggested(): string
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $error = null;
            $names = $this->collectPantryNames($userId);
            $recipes = [];
            $pagination = null;
            // Determine source label for the active provider
            $srcName = ($this->provider instanceof SuggesticProvider) ? 'suggestic' : 'spoonacular';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = isset($_GET['perPage']) ? max(1, min(30, (int)$_GET['perPage'])) : 12;
            if (!$names) {
                $error = 'Your pantry looks empty. Add some items to get recipe suggestions.';
            } elseif (!$this->provider->isConfigured() && !($this->spoon && $this->spoon->isConfigured())) {
                // No live API: paginate local only
                $local = $this->recipes->findByIngredientsLocalPaged($names, $page, $perPage);
                $recipes = $local['results'];
                $total = (int)($local['total'] ?? 0);
                $pagination = [
                    'currentPage' => $page,
                    'perPage' => $perPage,
                    'total' => $total,
                    'totalPages' => (int)max(1, ($total ? ceil($total / $perPage) : 1)),
                ];
                if ($total === 0) {
                    $error = 'No live recipe API configured. Set SUGGESTIC_API_KEY (preferred) or SPOONACULAR_API_KEY to enable live results.';
                }
            } else {
                // Local-first with pagination
                $local = $this->recipes->findByIngredientsLocalPaged($names, $page, $perPage);
                $recipes = $local['results'];
                $total = (int)($local['total'] ?? 0);
                $pagination = [
                    'currentPage' => $page,
                    'perPage' => $perPage,
                    'total' => $total,
                    'totalPages' => (int)max(1, ($total ? ceil($total / $perPage) : 1)),
                ];
                $seen = [];
                foreach ($recipes as $r) {
                    $dbid = $r['db_id'] ?? null; $k = $dbid ? ('db:' . $dbid) : ('title:' . ($r['title'] ?? ''));
                    $seen[$k] = true;
                }
                if (count($recipes) < $perPage && $this->provider->isConfigured()) {
                    $needed = $perPage - count($recipes);
                    $apiResults = $this->provider->findByIngredients($names, $needed);
                    foreach ($apiResults as $r) {
                        try { $id = $this->recipes->upsertFromProvider($r, null, $srcName); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                        $k = isset($r['db_id']) ? ('db:' . $r['db_id']) : ('title:' . ($r['title'] ?? ''));
                        if (!isset($seen[$k])) { $recipes[] = $r; $seen[$k] = true; }
                    }
                }
                // Spoonacular fallback disabled globally, keep guard in case enabled later
                if (count($recipes) < $perPage && $this->spoon && $this->spoon->isConfigured()) {
                    $needed = $perPage - count($recipes);
                    $apiResults = $this->spoon->findByIngredients($names, $needed);
                    foreach ($apiResults as $r) {
                        try { $id = $this->recipes->upsertFromProvider($r, null, 'spoonacular'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                        $k = isset($r['db_id']) ? ('db:' . $r['db_id']) : ('title:' . ($r['title'] ?? ''));
                        if (!isset($seen[$k])) { $recipes[] = $r; $seen[$k] = true; }
                    }
                }
            }

            return View::render('Recipes/index', [
                'title' => 'Suggested Recipes',
                'query' => '',
                'recipes' => $recipes,
                'error' => $error,
                'mode' => 'suggested',
                'pantryKeywords' => $names,
                'pagination' => $pagination,
            ]);
        } catch (\Throwable $e) {
            error_log('RecipesController::suggested error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** POST /recipes/save — save/bookmark a recipe for the user */
    public function save()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            // Accept minimal fields from client; support provider hint
            $payload = $_POST['payload'] ?? null;
            $provider = $_POST['provider'] ?? null;
            $data = [
                'id' => isset($_POST['id']) && $_POST['id'] !== '' ? (string)$_POST['id'] : null,
                'title' => $_POST['title'] ?? null,
                'image' => $_POST['image'] ?? null,
                'sourceUrl' => $_POST['sourceUrl'] ?? null,
            ];
            if ($payload) {
                $decoded = json_decode((string)$payload, true);
                if (is_array($decoded)) $data = array_merge($decoded, array_filter($data, fn($v)=>$v!==null));
            }
            if (empty($data['title'])) {
                http_response_code(422);
                return View::render('Pages/500', ['title' => 'Invalid recipe data']);
            }
            // Infer provider
            $src = 'spoonacular';
            if ($provider) {
                $p = strtolower((string)$provider);
                if (str_contains($p, 'suggestic')) $src = 'suggestic';
            } else {
                if (!isset($data['id'])) $src = 'suggestic';
            }

            $recipeId = $this->recipes->upsertFromProvider($data, null, $src);
            $this->recipes->saveForUser($recipeId, (int)$userId);

            // Redirect back to previous page
            $back = $_SERVER['HTTP_REFERER'] ?? '/recipes';
            header('Location: ' . $back);
            exit;
        } catch (\Throwable $e) {
            error_log('RecipesController::save error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** Unsave a recipe */
    public function unsave()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $rid = isset($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : 0;
            if ($rid > 0) {
                $this->recipes->unsaveForUser($rid, (int)$userId);
            }
            $back = $_SERVER['HTTP_REFERER'] ?? '/recipes';
            header('Location: ' . $back);
            exit;
        } catch (\Throwable $e) {
            error_log('RecipesController::unsave error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** Show a recipe details page */
    public function show(int $id): string
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $row = $this->recipes->findById($id);
            if (!$row) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Recipe Not Found']);
            }
            $isSaved = $this->recipes->isSaved((int)$row['id'], (int)$userId);
            $normalized = $this->normalizeForShow($row);

            // If ingredients or steps are missing, and it's a Spoonacular-backed recipe with an API id,
            // try fetching detailed information now and update the cache, then re-normalize.
            $needsDetails = (empty($normalized['ingredients']) || empty($normalized['steps']));
            if ($needsDetails && ($row['api_source'] ?? null) === 'spoonacular' && !empty($row['api_id'])) {
                if ($this->spoon && $this->spoon->isConfigured()) {
                    try {
                        $details = $this->spoon->getRecipeInformation((int)$row['api_id']);
                        if (!empty($details)) {
                            $this->recipes->updateFromProviderDetails((int)$row['id'], $details, 'spoonacular');
                            // Re-fetch and re-normalize to reflect new data
                            $row = $this->recipes->findById($id);
                            if ($row) {
                                $normalized = $this->normalizeForShow($row);
                            }
                        }
                    } catch (\Throwable $e) {
                        error_log('RecipesController::show detail fetch error: ' . $e->getMessage());
                    }
                }
            }

            // If any core fields are missing and this is a Suggestic recipe, try to fetch details now
            $needsAny = (empty($normalized['ingredients']) || empty($normalized['steps']) || empty($normalized['nutrition_per_serving']));

            if ($needsAny && (($row['api_source'] ?? null) === 'suggestic') && isset($row['api_id'])) {
                if (method_exists($this->provider, 'getRecipeById') && $this->provider->isConfigured()) {
                    try {
                        $details = $this->provider->getRecipeById((string)$row['api_id']);
                        if (!empty($details)) {
                            // persist raw for future loads (also persists nutrition if present)
                            $this->recipes->updateFromProviderDetails((int)$row['id'], $details, 'suggestic');
                            $row = $this->recipes->findById($id);
                            if ($row) {
                                $normalized = $this->normalizeForShow($row);
                            }
                        }
                    } catch (\Throwable $e) {
                        error_log('RecipesController::show suggestic fetch error: ' . $e->getMessage());
                    }
                }
            }

            return View::render('Recipes/show', [
                'title' => $normalized['title'] ?? 'Recipe',
                'recipe' => $normalized,
                'isSaved' => $isSaved,
            ]);
        } catch (\Throwable $e) {
            error_log('RecipesController::show error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** Prepare detailed data for the show view */
    private function normalizeForShow(array $row): array
    {
        $norm = $this->recipes->normalize($row);
        // Decode raw payload
        $raw = [];
        if (!empty($row['raw_payload'])) {
            $raw = is_array($row['raw_payload']) ? $row['raw_payload'] : json_decode((string)$row['raw_payload'], true);
            if (!is_array($raw)) $raw = [];
        }
        $norm['db_id'] = (int)$row['id'];
        $norm['api_source'] = $row['api_source'] ?? null;
        // Ingredients
        $ingredients = $norm['ingredients_list'] ?? [];
        // Spoonacular raw: extendedIngredients
        if (!$ingredients && !empty($raw['extendedIngredients']) && is_array($raw['extendedIngredients'])) {
            foreach ($raw['extendedIngredients'] as $ing) {
                $name = $ing['original'] ?? ($ing['name'] ?? null);
                if ($name) $ingredients[] = (string)$name;
            }
        }
        // Suggestic raw: ingredientLines (array of strings)
        if (!$ingredients && !empty($raw['ingredientLines']) && is_array($raw['ingredientLines'])) {
            foreach ($raw['ingredientLines'] as $line) {
                if (is_string($line)) {
                    $t = trim($line);
                    if ($t !== '') $ingredients[] = $t;
                }
            }
        }
        // Suggestic fallback: ingredients[].name
        if (!$ingredients && !empty($raw['ingredients']) && is_array($raw['ingredients'])) {
            foreach ($raw['ingredients'] as $ing) {
                if (isset($ing['name']) && is_string($ing['name'])) {
                    $t = trim($ing['name']);
                    if ($t !== '') $ingredients[] = $t;
                }
            }
        }
        $norm['ingredients'] = $ingredients;
        // Instructions
        $steps = $norm['instructions_list'] ?? [];
        if (!$steps) {
            // Spoonacular raw: analyzedInstructions[].steps[].step
            if (!empty($raw['analyzedInstructions'][0]['steps']) && is_array($raw['analyzedInstructions'][0]['steps'])) {
                foreach ($raw['analyzedInstructions'][0]['steps'] as $st) {
                    if (isset($st['step']) && is_string($st['step'])) {
                        $s = trim($st['step']); if ($s !== '') $steps[] = $s;
                    }
                }
            // Suggestic raw: instructions can be array of strings
            } elseif (!empty($raw['instructions']) && is_array($raw['instructions'])) {
                foreach ($raw['instructions'] as $s) {
                    if (is_string($s)) {
                        $t = trim($s);
                        if ($t !== '') $steps[] = rtrim($t, '.');
                    }
                }
            } elseif (!empty($raw['instructions']) && is_string($raw['instructions'])) {
                $parts = preg_split('/\n+|\r+|\.(\s|$)/u', $raw['instructions']);
                foreach ($parts as $s) { $s = trim($s); if ($s !== '') $steps[] = rtrim($s, '.'); }
            }
        }
        $norm['steps'] = $steps;
        // Price estimate if present (Spoonacular provides pricePerServing cents)
        $price = null; $servings = null;
        if (isset($raw['pricePerServing'])) {
            $servings = $raw['servings'] ?? null;
            $pps = (float)$raw['pricePerServing'];
            // Spoonacular is in US cents
            $price = $pps / 100.0;
        } elseif (isset($raw['estimatedCost']['value'])) {
            $val = (float)$raw['estimatedCost']['value'];
            $unit = $raw['estimatedCost']['unit'] ?? '';
            if (strtolower((string)$unit) === 'us cents' || strtolower((string)$unit) === 'cents') {
                $price = $val / 100.0;
            }
        } elseif (isset($norm['servings'])) {
            $servings = $norm['servings'];
        }
        $norm['estimated_price'] = $price;
        $norm['servings'] = $servings ?? ($norm['servings'] ?? null);

        // Nutrients per serving: prefer stored table value; else derive from provider raw payload and persist
        $per = [];
        // Try DB first
        try {
            $saved = $this->recipes->getNutrition((int)$row['id']);
            if (is_array($saved) && !empty($saved)) {
                $per = $saved;
            }
        } catch (\Throwable $e) { /* ignore */ }

        if (!$per && !empty($raw['nutrientsPerServing']) && is_array($raw['nutrientsPerServing'])) {
            $src = $raw['nutrientsPerServing'];
            // Some schemas use "energy" while examples show "calories"; prefer calories then energy
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
                if (isset($src[$k]) && is_numeric($src[$k])) {
                    $per[$label] = ['amount' => (float)$src[$k], 'unit' => $unit];
                }
            }
            if ($per) {
                try { $this->recipes->upsertNutrition((int)$row['id'], $per); } catch (\Throwable $e) { /* ignore */ }
            }
        }
        if ($per) $norm['nutrition_per_serving'] = $per;

        return $norm;
    }

    /** Collect pantry ingredient/product names for the given user. */
    private function collectPantryNames(int $userId): array
    {
        $page = $this->items->findAll($userId, 1, 200); // take up to 200 items with joined fields
        $rows = $page['items'] ?? [];
        $names = [];
        foreach ($rows as $row) {
            if (!empty($row['ingredient_id'])) {
                // Prefer joined ingredient name if present
                if (!empty($row['ingredient_name'])) {
                    $names[] = (string)$row['ingredient_name'];
                } else {
                    $ing = $this->ingredients->find((int)$row['ingredient_id']);
                    if ($ing && !empty($ing['name'])) $names[] = (string)$ing['name'];
                }
            } elseif (!empty($row['product_id'])) {
                if (!empty($row['product_title'])) {
                    $names[] = (string)$row['product_title'];
                } elseif (!empty($row['entered_name'])) {
                    $names[] = (string)$row['entered_name'];
                } else {
                    $prod = $this->products->find((int)$row['product_id']);
                    if ($prod && !empty($prod['title'])) $names[] = (string)$prod['title'];
                }
            } elseif (!empty($row['entered_name'])) {
                $names[] = (string)$row['entered_name'];
            }
        }
        // de-dup and limit length
        $names = array_values(array_unique(array_filter(array_map(function($s){
            $s = trim((string)$s);
            // keep it short to likely match ingredients
            if (strlen($s) > 64) $s = substr($s, 0, 64);
            return $s;
        }, $names))));
        // Keep a reasonable number for the API call
        if (count($names) > 15) $names = array_slice($names, 0, 15);
        return $names;
    }
}
