<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Models\Products;
use Models\Recipes;
use Policies\RecipePolicy;
use Services\ImageService;
use Services\Pantry\IngredientCanonicalizer;
use Services\Pantry\PantryTermNormalizer;
use Services\Recipes\FatSecretRecipesProvider;

class RecipesController
{
    private ?FatSecretRecipesProvider $provider = null;
    private Items $items;
    private Ingredients $ingredients;
    private Products $products;
    private Recipes $recipes;

    public function __construct()
    {
        $fs = new FatSecretRecipesProvider();
        $this->provider = $fs->isConfigured() ? $fs : null;
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
            $ugcOnly = isset($_GET['ugc']) && (string)$_GET['ugc'] === '1'; // user-generated recipes only
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
            $notice = null;
            $mode = 'saved';
            $pagination = null;
            // Collect pantry keywords for the toggleable pantry UI on the search page
            $names = $this->collectPantryNames((int)$userId);
            // Selected subset from query (Use My Pantry selections)
            // AI-canonicalized: "Red Seedless Grapes" searches as "grapes"
            $selectedPantry = [];
            if (!empty($_GET['pantry']) && is_array($_GET['pantry'])) {
                $map = (new IngredientCanonicalizer())->canonicalizeAll(
                    array_map('strval', $_GET['pantry'])
                );
                $selectedPantry = array_values(array_unique(array_values($map)));
            }
            // Pantry match mode: all (AND) or any (OR). Default to 'all'.
            $pantryMode = isset($_GET['pantry_mode']) ? strtolower(trim((string)$_GET['pantry_mode'])) : 'all';
            if ($pantryMode !== 'any' && $pantryMode !== 'all') { $pantryMode = 'all'; }
            $requireAll = ($pantryMode === 'all');

            if ($browse) {
                // Browse mode not supported by FatSecret free tier
                $mode = 'browse_api';
                $error = 'Browse mode is not available. Use the search box to find recipes.';
                $pagination = ['currentPage' => $page, 'perPage' => $perPage, 'total' => null, 'totalPages' => null];
            } elseif (!empty($selectedPantry)) {
                // Use selected pantry items (subset) to search DB and top up with API
                $mode = 'search';
                if ($ugcOnly) {
                    $local = $this->recipes->findByIngredientsLocalPagedUser($selectedPantry, $page, $perPage, $requireAll);
                } else {
                    $local = $this->recipes->findByIngredientsLocalPaged($selectedPantry, $page, $perPage, $requireAll);
                }
                $recipes = $local['results'] ?? [];
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
                // When UGC-only, do not top up from API — show only user-created recipes
                if (!$ugcOnly) {
                    if (count($recipes) < $perPage && $this->provider) {
                        $needed = $perPage - count($recipes);
                        $apiResults = $this->provider->findByIngredients($selectedPantry, $needed);
                        // Post-filter for AND mode so that all selected terms appear in title or ingredients list
                        if ($requireAll) {
                            $apiResults = array_values(array_filter($apiResults, function($r) use ($selectedPantry){
                                $hay = strtolower((string)($r['title'] ?? ''));
                                if (!empty($r['ingredients_list']) && is_array($r['ingredients_list'])) {
                                    $hay .= ' ' . strtolower(implode(' ', $r['ingredients_list']));
                                }
                                foreach ($selectedPantry as $t) {
                                    if ($t === '') continue;
                                    $t = strtolower($t);
                                    // Accept singular form too: "grapes" should match "grape salad"
                                    $singular = rtrim($t, 's');
                                    if (strpos($hay, $t) === false && ($singular === '' || strpos($hay, $singular) === false)) {
                                        return false;
                                    }
                                }
                                return true;
                            }));
                        }
                        foreach ($apiResults as $r) {
                            try { $id = $this->recipes->upsertFromProvider($r, null, 'fatsecret'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                            $k = isset($r['db_id']) ? ('db:' . $r['db_id']) : ('title:' . ($r['title'] ?? ''));
                            if (!isset($seen[$k])) { $recipes[] = $r; $seen[$k] = true; }
                        }
                    }
                    if ($total === 0 && !$this->provider) {
                        $error = 'No live recipe API configured. Set FATSECRET_CLIENT_ID and FATSECRET_CLIENT_SECRET in .env to enable live results.';
                    }
                }

                // Broaden-on-zero: All-mode with several items often matches nothing.
                // Retry as Any so users get results instead of a dead end.
                if (empty($recipes) && $requireAll && count($selectedPantry) > 1) {
                    $local = $ugcOnly
                        ? $this->recipes->findByIngredientsLocalPagedUser($selectedPantry, 1, $perPage, false)
                        : $this->recipes->findByIngredientsLocalPaged($selectedPantry, 1, $perPage, false);
                    $recipes = $local['results'] ?? [];
                    $total = (int)($local['total'] ?? 0);

                    if (!$ugcOnly && count($recipes) < $perPage && $this->provider) {
                        $seen = [];
                        foreach ($recipes as $r) {
                            $dbid = $r['db_id'] ?? null; $k = $dbid ? ('db:' . $dbid) : ('title:' . ($r['title'] ?? ''));
                            $seen[$k] = true;
                        }
                        // Query each term separately — the provider joins terms into one
                        // all-words expression, which is what caused the zero results.
                        $perTerm = (int)max(2, ceil($perPage / count($selectedPantry)));
                        foreach (array_slice($selectedPantry, 0, 5) as $term) {
                            if (count($recipes) >= $perPage) break;
                            foreach ($this->provider->searchByQuery($term, $perTerm) as $r) {
                                try { $id = $this->recipes->upsertFromProvider($r, null, 'fatsecret'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                                $k = isset($r['db_id']) ? ('db:' . $r['db_id']) : ('title:' . ($r['title'] ?? ''));
                                if (!isset($seen[$k])) { $recipes[] = $r; $seen[$k] = true; }
                            }
                        }
                    }

                    if (!empty($recipes)) {
                        $notice = 'No recipes use all of your selected items — showing recipes that use at least one of them.';
                        $pagination = [
                            'currentPage' => 1,
                            'perPage' => $perPage,
                            'total' => $total,
                            'totalPages' => (int)max(1, ($total ? ceil($total / $perPage) : 1)),
                        ];
                    }
                }
            } elseif ($q === '') {
                if ($ugcOnly) {
                    // My Recipes: show ONLY this user's manually-created recipes
                    $local = $this->recipes->findByCurrentUser((int)$userId, $page, $perPage);
                    $recipes = $local['results'] ?? [];
                    $total   = (int)($local['total'] ?? 0);
                    $pagination = [
                        'currentPage' => $page,
                        'perPage'     => $perPage,
                        'total'       => $total,
                        'totalPages'  => (int)max(1, $total ? ceil($total / $perPage) : 1),
                    ];
                    $mode = 'ugc';
                } else {
                    // Default: show user's saved/bookmarked recipes
                    $recipes = $this->recipes->getSavedForUser((int)$userId, 24);
                }
            } else {
                if ($forceApi) {
                    if ($this->provider) {
                        $apiResults = $this->provider->searchByQuery($q, $perPage);
                        $recipes = [];
                        foreach ($apiResults as $r) {
                            try { $id = $this->recipes->upsertFromProvider($r, null, 'fatsecret'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                            $recipes[] = $r;
                        }
                        $mode = 'search_api';
                        $pagination = ['currentPage' => $page, 'perPage' => $perPage, 'total' => null, 'totalPages' => null];
                    } else {
                        $error = 'No live recipe API configured. Set FATSECRET_CLIENT_ID and FATSECRET_CLIENT_SECRET in .env to enable live results.';
                        $recipes = $this->recipes->searchLocalByQuery($q, $perPage);
                        $mode = 'search';
                    }
                } else {
                    // Local-first search
                    if ($ugcOnly) {
                        $recipes = $this->recipes->searchLocalUserByQuery($q, $perPage);
                        $mode = 'ugc';
                    } else {
                        $recipes = $this->recipes->searchLocalByQuery($q, $perPage);
                        $seen = [];
                        foreach ($recipes as $r) {
                            $dbid = $r['db_id'] ?? null; $k = $dbid ? ('db:' . $dbid) : ('title:' . ($r['title'] ?? ''));
                            $seen[$k] = true;
                        }
                        if (count($recipes) < $perPage && $this->provider) {
                            $needed = $perPage - count($recipes);
                            $apiResults = $this->provider->searchByQuery($q, $needed);
                            foreach ($apiResults as $r) {
                                try { $id = $this->recipes->upsertFromProvider($r, null, 'fatsecret'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                                $k = isset($r['db_id']) ? ('db:' . $r['db_id']) : ('title:' . ($r['title'] ?? ''));
                                if (!isset($seen[$k])) { $recipes[] = $r; $seen[$k] = true; }
                            }
                        }
                        if (empty($recipes) && !$this->provider) {
                            $error = 'No live recipe API configured. Set FATSECRET_CLIENT_ID and FATSECRET_CLIENT_SECRET in .env to enable live results.';
                        }
                        $mode = 'search';
                    }
                }
            }

            // Broaden-on-zero for typed searches: product-style queries like
            // "red seedless grapes" match nothing; retry with the AI-canonicalized
            // ingredient term ("grapes") and tell the user what we did.
            if (empty($recipes) && $q !== '' && empty($selectedPantry) && !$browse && !$ugcOnly && $this->provider) {
                $broadQ = (new IngredientCanonicalizer())->canonicalize($q);
                if ($broadQ !== '' && strcasecmp($broadQ, $q) !== 0) {
                    foreach ($this->provider->searchByQuery($broadQ, $perPage) as $r) {
                        try { $id = $this->recipes->upsertFromProvider($r, null, 'fatsecret'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                        $recipes[] = $r;
                    }
                    if (!empty($recipes)) {
                        $notice = 'No exact matches for "' . $q . '" — showing recipes for "' . $broadQ . '".';
                    }
                }
            }

            return View::render('Recipes/index', [
                'title' => $browse ? 'Browse Recipes' : 'Find Recipes',
                'query' => $q,
                'recipes' => $recipes,
                'error' => $error,
                'notice' => $notice,
                'mode' => $mode,
                'filters' => $filters,
                'pagination' => $pagination,
                'pantryKeywords' => $names,
                'pantrySelected' => $selectedPantry,
                'pantryMode' => $pantryMode,
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
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = isset($_GET['perPage']) ? max(1, min(30, (int)$_GET['perPage'])) : 12;
            if (!$names) {
                $error = 'Your pantry looks empty. Add some items to get recipe suggestions.';
            } elseif (!$this->provider) {
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
                    $error = 'No live recipe API configured. Set FATSECRET_CLIENT_ID and FATSECRET_CLIENT_SECRET in .env to enable live results.';
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
                if (count($recipes) < $perPage && $this->provider) {
                    $needed = $perPage - count($recipes);
                    $apiResults = $this->provider->findByIngredients($names, $needed);
                    foreach ($apiResults as $r) {
                        try { $id = $this->recipes->upsertFromProvider($r, null, 'fatsecret'); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
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
            // Infer provider from hint; default to fatsecret for API-sourced recipes
            $src = 'fatsecret';
            if ($provider) {
                $p = strtolower((string)$provider);
                if (str_contains($p, 'manual')) $src = 'manual';
                elseif (str_contains($p, 'fdc')) $src = 'fdc';
                elseif (str_contains($p, 'off')) $src = 'off';
            }

            $recipeId = $this->recipes->upsertFromProvider($data, null, $src);
            $this->recipes->saveForUser($recipeId, (int)$userId);

            // The dashboard's saved-recipes stat derives from this
            try {
                \Helpers\Cache::del(\Services\Pantry\PantryCache::dashboardStatsKey((int)$userId));
            } catch (\Throwable $e) { /* ignore */ }

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
                // The dashboard's saved-recipes stat derives from this
                try {
                    \Helpers\Cache::del(\Services\Pantry\PantryCache::dashboardStatsKey((int)$userId));
                } catch (\Throwable $e) { /* ignore */ }
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

            // FatSecret: fetch detail from Redis cache (never store raw_payload permanently — ToS compliance)
            $needsDetail = (($row['api_source'] ?? null) === 'fatsecret') && isset($row['api_id']) && $this->provider;
            if ($needsDetail) {
                try {
                    $details = $this->provider->getRecipeById((string)$row['api_id']);
                    if (!empty($details)) {
                        // Persist only safe fields (no raw_payload)
                        $safeUpdate = array_intersect_key($details, array_flip(['title', 'image', 'sourceUrl', 'ingredients_list', 'instructions_list']));
                        if (!empty($safeUpdate)) {
                            $this->recipes->updateFromProviderDetails((int)$row['id'], $safeUpdate, 'fatsecret');
                            $row = $this->recipes->findById($id);
                            if ($row) {
                                $normalized = $this->normalizeForShow($row);
                            }
                        }
                        // Surface detail fields directly for the view
                        if (empty($normalized['ingredients']) && !empty($details['ingredients_list'])) {
                            $normalized['ingredients'] = $details['ingredients_list'];
                        }
                        if (empty($normalized['steps']) && !empty($details['instructions_list'])) {
                            $normalized['steps'] = $details['instructions_list'];
                        }
                        // Nutrition lives only in the Redis-cached detail — never persisted
                        if (!empty($details['nutrition_per_serving'])) {
                            $normalized['nutrition_per_serving'] = $details['nutrition_per_serving'];
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('RecipesController::show fatsecret fetch error: ' . $e->getMessage());
                }
            }

            $pantryIngredients = $this->collectPantryNames((int)$userId, 200);

            return View::render('Recipes/show', [
                'title' => $normalized['title'] ?? 'Recipe',
                'recipe' => $normalized,
                'isSaved' => $isSaved,
                'pantryIngredients' => $pantryIngredients,
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
        $norm['db_id']     = (int)$row['id'];
        $norm['api_source'] = $row['api_source'] ?? null;
        $norm['user_id']   = isset($row['user_id']) ? (int)$row['user_id'] : null;
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
    private function collectPantryNames(int $userId, int $limit = 15): array
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
        // Canonicalize to recipe-search terms (AI + permanent cache, rule-based fallback)
        $map = (new IngredientCanonicalizer())->canonicalizeAll($names);
        $names = array_values(array_unique(array_values($map)));
        // Keep a reasonable number for the API call
        if (count($names) > $limit) $names = array_slice($names, 0, $limit);
        return $names;
    }

    /** Render form for creating a user recipe */
    public function create(): string
    {
        try {
            if (session_status() !== \PHP_SESSION_ACTIVE) { @session_start(); }
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            return View::render('Recipes/recipe_form', [
                'title' => 'Add Recipe',
            ]);
        } catch (\Throwable $e) {
            error_log('RecipesController::create error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** Handle POST to create a user recipe */
    public function store()
    {
        try {
            if (session_status() !== \PHP_SESSION_ACTIVE) { @session_start(); }
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $title = trim((string)($_POST['title'] ?? ''));
            if ($title === '') {
                return View::render('Recipes/recipe_form', [
                    'title' => 'Add Recipe',
                    'error' => 'Title is required.',
                    'input' => $_POST,
                ]);
            }
            // Handle image: uploaded file takes priority over URL field
            $imageUrl = trim((string)($_POST['image_url'] ?? ''));
            $uploadedFile = $_FILES['image_file'] ?? null;
            if (!empty($uploadedFile['name'])) {
                try {
                    $imageUrl = (new ImageService())->storeRecipeImage($uploadedFile);
                } catch (\RuntimeException $imgErr) {
                    return View::render('Recipes/recipe_form', [
                        'title' => 'Add Recipe',
                        'error' => $imgErr->getMessage(),
                        'input' => $_POST,
                    ]);
                }
            }

            $ingInput = trim((string)($_POST['ingredients_list'] ?? ''));
            $instrInput = trim((string)($_POST['instructions_list'] ?? ''));
            $ingredients = $ingInput !== '' ? $this->parseArrayInput($ingInput) : null;
            $instructions = $instrInput !== '' ? $this->parseArrayInput($instrInput) : null;

            $data = [
                'title'             => $title,
                'description'       => $_POST['description'] ?? null,
                'image'             => $imageUrl !== '' ? $imageUrl : null,
                'sourceUrl'         => trim((string)($_POST['source_url'] ?? '')) ?: null,
                'id'                => null,
                'ingredients_list'  => $ingredients,
                'instructions_list' => $instructions,
            ];
            $rid = $this->recipes->upsertFromProvider($data, (int)$userId, 'manual');

            $nutritionInput = trim((string)($_POST['nutrition_per_serving'] ?? ''));
            if ($rid && $nutritionInput !== '') {
                $per = $this->parseNutritionInput($nutritionInput);
                if (is_array($per) && !empty($per)) {
                    $this->recipes->upsertNutrition((int)$rid, $per);
                }
            }

            header('Location: /recipes?ugc=1');
            exit;
        } catch (\Throwable $e) {
            error_log('RecipesController::store error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    // -------------------------------------------------------------------------
    // User recipe edit / delete
    // -------------------------------------------------------------------------

    /** GET /recipes/{id}/edit — show edit form for a user-owned recipe */
    public function edit(int $id): string
    {
        try {
            if (session_status() !== \PHP_SESSION_ACTIVE) { @session_start(); }
            if (empty($_SESSION['user_id'])) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $row = $this->recipes->findById($id);
            if (!$row) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Recipe Not Found']);
            }
            if (!RecipePolicy::canEdit($row)) {
                http_response_code(403);
                return View::render('Pages/403', ['title' => 'Forbidden']);
            }
            $normalized = $this->normalizeForShow($row);
            $nutrition  = $this->recipes->getNutrition($id);

            // Convert arrays back to textarea-friendly text
            $ingText   = implode("\n", $normalized['ingredients'] ?? []);
            $instrText = implode("\n", $normalized['steps'] ?? []);
            $nutriText = '';
            if (is_array($nutrition)) {
                $lines = [];
                foreach ($nutrition as $label => $n) {
                    $lines[] = $label . ': ' . $n['amount'] . ' ' . $n['unit'];
                }
                $nutriText = implode("\n", $lines);
            }

            return View::render('Recipes/recipe_form', [
                'title'    => 'Edit Recipe',
                'isEdit'   => true,
                'recipeId' => $id,
                'recipe'   => $normalized,
                'input'    => [
                    'title'               => $normalized['title'] ?? '',
                    'description'         => $row['description'] ?? '',
                    'image_url'           => $normalized['image'] ?? '',
                    'source_url'          => $normalized['sourceUrl'] ?? '',
                    'ingredients_list'    => $ingText,
                    'instructions_list'   => $instrText,
                    'nutrition_per_serving' => $nutriText,
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('RecipesController::edit error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** POST /recipes/{id}/edit — persist edits to a user-owned recipe */
    public function update(int $id)
    {
        try {
            if (session_status() !== \PHP_SESSION_ACTIVE) { @session_start(); }
            if (empty($_SESSION['user_id'])) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $row = $this->recipes->findById($id);
            if (!$row) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Recipe Not Found']);
            }
            if (!RecipePolicy::canEdit($row)) {
                http_response_code(403);
                return View::render('Pages/403', ['title' => 'Forbidden']);
            }

            $title = trim((string)($_POST['title'] ?? ''));
            if ($title === '') {
                $normalized = $this->normalizeForShow($row);
                return View::render('Recipes/recipe_form', [
                    'title'    => 'Edit Recipe',
                    'isEdit'   => true,
                    'recipeId' => $id,
                    'recipe'   => $normalized,
                    'error'    => 'Title is required.',
                    'input'    => $_POST,
                ]);
            }

            // Handle image upload (new file takes priority over URL field)
            $imageUrl = trim((string)($_POST['image_url'] ?? ''));
            $uploadedFile = $_FILES['image_file'] ?? null;
            if (!empty($uploadedFile['name'])) {
                try {
                    $svc = new ImageService();
                    // Delete old local image if present
                    if (!empty($row['image_url'])) {
                        $svc->deleteIfLocal($row['image_url']);
                    }
                    $imageUrl = $svc->storeRecipeImage($uploadedFile);
                } catch (\RuntimeException $e) {
                    $normalized = $this->normalizeForShow($row);
                    return View::render('Recipes/recipe_form', [
                        'title'    => 'Edit Recipe',
                        'isEdit'   => true,
                        'recipeId' => $id,
                        'recipe'   => $normalized,
                        'error'    => $e->getMessage(),
                        'input'    => $_POST,
                    ]);
                }
            }

            $ingInput   = trim((string)($_POST['ingredients_list'] ?? ''));
            $instrInput = trim((string)($_POST['instructions_list'] ?? ''));

            $data = [
                'title'             => $title,
                'description'       => $_POST['description'] ?? null,
                'image_url'         => $imageUrl !== '' ? $imageUrl : null,
                'image'             => $imageUrl !== '' ? $imageUrl : null,
                'source_url'        => trim((string)($_POST['source_url'] ?? '')) ?: null,
                'sourceUrl'         => trim((string)($_POST['source_url'] ?? '')) ?: null,
                'ingredients_list'  => $ingInput !== '' ? $this->parseArrayInput($ingInput) : [],
                'instructions_list' => $instrInput !== '' ? $this->parseArrayInput($instrInput) : [],
            ];
            $this->recipes->updateUserRecipe($id, $data);

            $nutritionInput = trim((string)($_POST['nutrition_per_serving'] ?? ''));
            if ($nutritionInput !== '') {
                $per = $this->parseNutritionInput($nutritionInput);
                if (!empty($per)) {
                    $this->recipes->upsertNutrition($id, $per);
                }
            }

            header('Location: /recipes/view/' . $id);
            exit;
        } catch (\Throwable $e) {
            error_log('RecipesController::update error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** POST /recipes/{id}/delete — delete a user-owned recipe */
    public function destroy(int $id)
    {
        try {
            if (session_status() !== \PHP_SESSION_ACTIVE) { @session_start(); }
            if (empty($_SESSION['user_id'])) {
                http_response_code(401);
                exit;
            }
            $row = $this->recipes->findById($id);
            if (!$row) {
                header('Location: /recipes');
                exit;
            }
            if (!RecipePolicy::canDelete($row)) {
                http_response_code(403);
                return View::render('Pages/403', ['title' => 'Forbidden']);
            }

            // Clean up local image if present
            if (!empty($row['image_url'])) {
                (new ImageService())->deleteIfLocal($row['image_url']);
            }

            $this->recipes->deleteById($id);

            header('Location: /recipes?ugc=1');
            exit;
        } catch (\Throwable $e) {
            error_log('RecipesController::destroy error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    // -------------------------------------------------------------------------
    // store() also needs to handle image upload — update here
    // -------------------------------------------------------------------------

    // --- Helpers copied from AdminController parsers (user-friendly inputs) ---
    private function parseArrayInput(string $input): array
    {
        $input = trim($input);
        if ($input === '') return [];
        $first = $input[0]; $last = substr($input, -1);
        if (($first === '[' && $last === ']') || ($first === '"' && $last === '"')) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                $arr = [];
                foreach ($decoded as $v) { if (is_string($v)) { $t = trim($v); if ($t !== '') $arr[] = $t; } }
                return $arr;
            }
        }
        $parts = preg_split('/\r?\n+|,/', $input);
        $out = [];
        foreach ($parts as $p) { $t = trim((string)$p); if ($t !== '') $out[] = $t; }
        return $out;
    }

    private function parseNutritionInput(string $input): array
    {
        $input = trim($input);
        if ($input === '') return [];
        if ($input[0] === '{' && substr($input, -1) === '}') {
            $obj = json_decode($input, true);
            if (is_array($obj)) return $obj;
        }
        $lines = preg_split('/\r?\n+|,/', $input);
        $out = [];
        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '') continue;
            // Accept formats like "Calories: 250 kcal" or "Protein 12 g"
            if (strpos($line, ':') !== false) {
                [$k, $v] = array_map('trim', explode(':', $line, 2));
            } else {
                $parts = preg_split('/\s+/', $line);
                $k = array_shift($parts);
                $v = implode(' ', $parts);
            }
            // Extract amount and unit
            if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*([a-zA-Z%]+)?/u', $v, $m)) {
                $amount = isset($m[1]) ? (float)$m[1] : null;
                $unit = isset($m[2]) ? $m[2] : '';
                if ($k !== '' && $amount !== null) {
                    $label = ucwords(trim($k));
                    $out[$label] = ['amount' => $amount, 'unit' => $unit ?: ''];
                }
            }
        }
        return $out;
    }
}
