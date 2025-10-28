<?php

namespace Services\Recipes;

use GuzzleHttp\Client;

class SuggesticProvider implements RecipesProvider
{
    private Client $http;
    private ?string $apiKey;
    private ?string $sgUser;
    private ?string $partner;
    
    // Maximum items Suggestic allows per page in recipeSearch (conservative)
    private int $pageLimit = 30;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey
            ?? ($_ENV['SUGGESTIC_API_KEY'] ?? null)
            ?? ($_SERVER['SUGGESTIC_API_KEY'] ?? null)
            ?? (getenv('SUGGESTIC_API_KEY') ?: null);

        $this->sgUser = ($_ENV['SUGGESTIC_USER_ID'] ?? null)
            ?? ($_SERVER['SUGGESTIC_USER_ID'] ?? null)
            ?? (getenv('SUGGESTIC_USER_ID') ?: null);

        $this->partner = ($_ENV['SUGGESTIC_PARTNER'] ?? null)
            ?? ($_SERVER['SUGGESTIC_PARTNER'] ?? null)
            ?? (getenv('SUGGESTIC_PARTNER') ?: null);

        $this->http = new Client([
            'base_uri' => 'https://production.suggestic.com/',
            'timeout' => 10,
        ]);
    }

    public function isConfigured(): bool
    {
        // Token + sg-user are both required for Token auth flows
        return !empty($this->apiKey) && !empty($this->sgUser);
    }

    public function searchByQuery(string $query, int $number = 12): array
    {
        if (!$this->isConfigured()) return [];

        $first = max(1, min($this->pageLimit, (int)$number));

        // Prefer the docs-endorsed search by name or ingredient which returns onPlan + otherResults
        $out = $this->searchByNameOrIngredientInternal($query, $first, $errNOI);
        if (!empty($out)) {
            return $out;
        }

        // Fallback to recipeSearch (Relay connection then list) in case the above is not enabled in tenant
        // 1) Relay connection shape (edges/node)
        $gqlConn = <<<'GQL'
query RecipeSearchConn($query: String!, $first: Int!) {
  recipeSearch(query: $query, first: $first) {
    edges {
      node {
        id
        databaseId
        name
        numberOfServings
        ingredientLines
        ingredients { name }
        instructions
        source { recipeUrl }
        mainImage
      }
    }
    pageInfo { hasNextPage endCursor }
  }
}
GQL;

        $data = $this->exec($gqlConn, ['query' => $query, 'first' => $first], $err1);
        if ($data && isset($data['data']['recipeSearch']['edges'])) {
            $edges = $data['data']['recipeSearch']['edges'] ?? [];
            $out = [];
            foreach ($edges as $edge) {
                if (!empty($edge['node']) && is_array($edge['node'])) {
                    $out[] = $this->normalizeNode($edge['node']);
                    if (count($out) >= $first) break;
                }
            }
            if ($out) return $out;
            // empty results here could be legit; fall through to next fallback
        }

        // 2) Simplified connection fallback (still using edges but minimal fields)
        $gqlList = <<<'GQL'
query RecipeSearchSimple($query: String!, $first: Int!) {
  recipeSearch(query: $query, first: $first) {
    edges {
      node {
        databaseId
        name
        numberOfServings
        ingredientLines
        ingredients { name }
        instructions
        source { recipeUrl }
        mainImage
      }
    }
  }
}
GQL;

        $data = $this->exec($gqlList, ['query' => $query, 'first' => $first], $err2);

        if ($data && isset($data['data']['recipeSearch']['edges'])) {
            $edges = $data['data']['recipeSearch']['edges'] ?? [];
            $out = [];
            foreach ($edges as $edge) {
                if (!empty($edge['node']) && is_array($edge['node'])) {
                    $out[] = $this->normalizeNode($edge['node']);
                    if (count($out) >= $first) break;
                }
            }
            return $out;
        }

        // If attempts fail due to validation, log once for debugging
        if (!empty($errNOI) || $err1 || $err2) {
            error_log('Suggestic search fallback. nameOrIngErr=' . ($errNOI ?: 'none') . ' connErr=' . ($err1 ?: 'none') . ' listErr=' . ($err2 ?: 'none'));
        }

        return [];
    }

    /**
     * Paged search using cursor-based pagination. Returns one page of results and whether more pages exist.
     * @return array{results: array, hasNext: bool}
     */
    public function searchPaged(string $query, int $page = 1, int $perPage = 10): array
    {
        if (!$this->isConfigured()) return ['results' => [], 'hasNext' => false];
        $page = max(1, (int)$page);
        $perPage = max(1, min($this->pageLimit, (int)$perPage));

        $gql = <<<'GQL'
query RecipeSearchConn($query: String!, $first: Int!, $after: String) {
  recipeSearch(query: $query, first: $first, after: $after) {
    edges {
      node {
        id
        databaseId
        name
        numberOfServings
        ingredientLines
        ingredients { name }
        instructions
        source { recipeUrl }
        mainImage
      }
    }
    pageInfo { hasNextPage endCursor }
  }
}
GQL;

        $after = null;
        $hasNext = false;
        $err = null;
        // Advance to the requested page by iterating cursors
        for ($p = 1; $p <= $page; $p++) {
            $vars = ['query' => $query, 'first' => $perPage, 'after' => $after];
            $data = $this->exec($gql, $vars, $err);
            if (!$data || !isset($data['data']['recipeSearch'])) {
                return ['results' => [], 'hasNext' => false];
            }
            $conn = $data['data']['recipeSearch'];
            $hasNext = (bool)($conn['pageInfo']['hasNextPage'] ?? false);
            $after = $conn['pageInfo']['endCursor'] ?? null;
            if ($p === $page) {
                $out = [];
                $edges = $conn['edges'] ?? [];
                foreach ($edges as $edge) {
                    if (!empty($edge['node']) && is_array($edge['node'])) {
                        $out[] = $this->normalizeNode($edge['node']);
                    }
                }
                return ['results' => $out, 'hasNext' => $hasNext];
            }
            if (!$hasNext) {
                // Requested page exceeds available pages
                return ['results' => [], 'hasNext' => false];
            }
        }
        return ['results' => [], 'hasNext' => false];
    }

    public function findByIngredients(array $ingredients, int $number = 12): array
    {
        // Normalize terms from pantry items / inputs
        $terms = array_values(array_unique(array_filter(array_map(function($s){
            $t = trim((string)$s);
            if ($t === '') return '';
            // strip quotes and parentheticals
            $t = preg_replace("/^['\"]+|['\"]+$/", '', $t);
            $t = preg_replace('/\([^\)]*\)/', '', $t);
            // only take first segment before comma
            if (strpos($t, ',') !== false) { $t = trim(explode(',', $t)[0]); }
            // normalize dashes and slashes to spaces, lowercase
            $t = strtolower(str_replace(['–','—','-','/'], ' ', $t));
            // collapse whitespace
            $t = trim(preg_replace('/\s+/', ' ', $t));
            // keep only reasonably short words/phrases
            return $t !== '' ? (strlen($t) > 64 ? substr($t, 0, 64) : $t) : '';
        }, $ingredients))));
        if (!$terms) return [];

        // Canonicalize specific variants to base ingredients for better recall
        $canon = function(string $t): string {
            $map = [
                'milk chocolate' => 'chocolate',
                'milk chocolate chips' => 'chocolate',
                'semi sweet chocolate' => 'chocolate',
                'semisweet chocolate' => 'chocolate',
                'dark chocolate' => 'chocolate',
                'white chocolate' => 'chocolate',
                'chocolate chips' => 'chocolate',
                'honeycrisp apples' => 'apple',
                'honeycrisp apple' => 'apple',
            ];
            if (isset($map[$t])) return $map[$t];
            if (str_contains($t, 'apple')) {
                $appleCultivars = ['honeycrisp','gala','fuji','granny smith','pink lady','ambrosia','mcintosh','golden delicious','red delicious','braeburn','jonagold'];
                foreach ($appleCultivars as $cv) {
                    if (str_starts_with($t, $cv.' ') || $t === $cv || str_starts_with($t, $cv.' apple') || str_starts_with($t, $cv.' apples')) {
                        return 'apple';
                    }
                }
                if ($t === 'apples') return 'apple';
            }
            return $t;
        };
        $terms = array_values(array_unique(array_map($canon, $terms)));

        // Prefer up to 5 concise terms to avoid over-constraining the search
        usort($terms, function($a, $b){ return strlen($a) <=> strlen($b); });
        $terms = array_slice($terms, 0, 5);

        $target = max(1, min(30, (int)$number));
        $seen = [];
        $out = [];

        // 1) Try Suggestic's mustIngredients endpoint to match ALL selected terms
        $errMI = null;
        $mustResults = $this->searchByIngredientsMustInternal($terms, $target, $errMI);
        foreach ($mustResults as $r) {
            $key = strtolower(trim(($r['title'] ?? '') . '|' . ($r['image'] ?? '')));
            if ($key === '' || isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = $r;
            if (count($out) >= $target) break;
        }

        // If nothing matched strictly, try a broadened pass collapsing to single tokens (e.g., "milk chocolate"->"chocolate")
        if (empty($out)) {
            $broad = [];
            foreach ($terms as $t) {
                $parts = preg_split('/\s+/', $t);
                if (count($parts) >= 2) {
                    $broad[] = end($parts); // last token often is canonical noun (e.g., chocolate, apples)
                } else {
                    $broad[] = $t;
                }
            }
            $broad = array_values(array_unique(array_map(function($x){ return $x === 'apples' ? 'apple' : $x; }, $broad)));
            if ($broad !== $terms) {
                $must2 = $this->searchByIngredientsMustInternal($broad, $target, $errMI2);
                foreach ($must2 as $r) {
                    $key = strtolower(trim(($r['title'] ?? '') . '|' . ($r['image'] ?? '')));
                    if ($key === '' || isset($seen[$key])) continue;
                    $seen[$key] = true;
                    $out[] = $r;
                    if (count($out) >= $target) break;
                }
            }
        }

        // 2) If still under target, broaden by searching per-term (OR-like) using name-or-ingredient search
        foreach ($terms as $t) {
            if (count($out) >= $target) break;
            $needed = $target - count($out);
            $results = $this->searchByNameOrIngredientInternal($t, $needed, $errNOI);
            if (!$results) {
                // Fallback to generic recipeSearch
                $results = $this->searchByQuery($t, $needed);
            }
            foreach ($results as $r) {
                $key = strtolower(trim(($r['title'] ?? '') . '|' . ($r['image'] ?? '')));
                if ($key === '' || isset($seen[$key])) continue;
                $seen[$key] = true;
                $out[] = $r;
                if (count($out) >= $target) break;
            }
        }

        // 3) As a last attempt, try a combined query of the first two terms if still underfilled
        if (count($out) < $target && count($terms) >= 2) {
            $needed = $target - count($out);
            $comboQ = $terms[0] . ' ' . $terms[1];
            $results = $this->searchByNameOrIngredientInternal($comboQ, $needed, $errNOI2);
            if (!$results) {
                $results = $this->searchByQuery($comboQ, $needed);
            }
            foreach ($results as $r) {
                $key = strtolower(trim(($r['title'] ?? '') . '|' . ($r['image'] ?? '')));
                if ($key === '' || isset($seen[$key])) continue;
                $seen[$key] = true;
                $out[] = $r;
                if (count($out) >= $target) break;
            }
        }

        return $out;
    }

    public function browseAll(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        if (!$this->isConfigured()) return ['results' => [], 'total' => 0];
        $seed = '';
        foreach (['type', 'cuisine', 'diet'] as $k) {
            if (!empty($filters[$k]) && is_string($filters[$k])) {
                $seed = (string)$filters[$k];
                break;
            }
        }
        if ($seed === '') $seed = 'dinner';
        $results = $this->searchByQuery($seed, $perPage);
        return ['results' => $results, 'total' => 0];
    }

    /**
     * Fetch a single recipe by global GraphQL id (preferred for Suggestic) and return raw data.
     * Returns [] on error.
     */
    public function getRecipeById(string $id): array
    {
        if (!$this->isConfigured()) return [];
        $gql = <<<'GQL'
query GetRecipe($id: ID!) {
  recipe(id: $id) {
    id
    databaseId
    name
    numberOfServings
    ingredientLines
    ingredients { name }
    instructions
    source { recipeUrl }
    mainImage
    nutrientsPerServing {
      calories
      protein
      carbs
      netcarbs
      fat
      saturatedFat
      transFat
      monounsaturatedFat
      polyunsaturatedFat
      sugar
      fiber
      cholesterol
      sodium
      potassium
      calcium
      iron
      vitaminA
      vitaminC
    }
  }
}
GQL;

        $err = null;
        $data = $this->exec($gql, ['id' => $id], $err);
        if (!$data || !empty($data['errors'])) {
            if ($err) error_log('SuggesticProvider::getRecipeById error: ' . $err);
            return [];
        }
        $r = $data['data']['recipe'] ?? null;
        return is_array($r) ? $r : [];
    }

    private function exec(string $gql, array $vars, ?string &$err = null): ?array
    {
        $err = null;
        // Cache key based on GraphQL query + vars
        $ckey = null;
        $useCache = false;
        try {
            if (class_exists('Helpers\\Cache')) {
                $ckey = 'sg:gql:' . sha1($gql . '|' . json_encode($vars));
                $useCache = \Helpers\Cache::ready();
                if ($useCache) {
                    $cached = \Helpers\Cache::get($ckey);
                    if (is_array($cached)) {
                        return $cached; // return cached JSON array
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore cache errors
        }

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $this->apiKey, // Token auth per Suggestic
                'sg-user' => $this->sgUser,            // required user context
            ];
            if (!empty($this->partner)) {
                $headers['Suggestic-Partner'] = $this->partner;
            }

            $resp = $this->http->post('graphql', [
                'headers' => $headers,
                'json' => ['query' => $gql, 'variables' => $vars],
            ]);
            $json = json_decode((string)$resp->getBody(), true);
            if (isset($json['errors'][0]['message'])) {
                $err = $json['errors'][0]['message'];
            }

            // Cache successful responses briefly (10 minutes)
            if ($useCache && is_array($json) && empty($json['errors'])) {
                try { \Helpers\Cache::set($ckey, $json, 600); } catch (\Throwable $e) { /* ignore */ }
            }

            return $json;
        } catch (\Throwable $e) {
            $err = $e->getMessage();
            error_log('SuggesticProvider exec error: ' . $err);
            return null;
        }
    }

    /**
     * Docs-based API: searchRecipeByNameOrIngredient(query: String!)
     * Merges onPlan and otherResults lists and returns up to $limit normalized recipes.
     */
    private function searchByNameOrIngredientInternal(string $query, int $limit, ?string &$err = null): array
    {
        $err = null;
        $gql = <<<'GQL'
query SearchByNameOrIngredient($query: String!) {
  searchRecipeByNameOrIngredient(query: $query) {
    onPlan { id name author adherence ingredients { name } ingredientLines source { recipeUrl } mainImage numberOfServings }
    otherResults { id name author adherence ingredients { name } ingredientLines source { recipeUrl } mainImage numberOfServings }
  }
}
GQL;
        $data = $this->exec($gql, ['query' => $query], $err);
        if (!$data || !isset($data['data']['searchRecipeByNameOrIngredient'])) return [];
        $obj = $data['data']['searchRecipeByNameOrIngredient'];
        $list = [];
        foreach (['onPlan','otherResults'] as $k) {
            if (!empty($obj[$k]) && is_array($obj[$k])) {
                foreach ($obj[$k] as $node) {
                    if (is_array($node)) $list[] = $this->normalizeNode($node);
                    if (count($list) >= $limit) break 2;
                }
            }
        }
        return $list;
    }

    /**
     * Docs-based API: searchRecipesByIngredients(mustIngredients: [String]!)
     * Returns up to $limit normalized recipes.
     */
    private function searchByIngredientsMustInternal(array $must, int $limit, ?string &$err = null): array
    {
        $err = null;
        // sanitize must list
        $must = array_values(array_filter(array_map(function($s){ $t = trim((string)$s); return $t === '' ? null : $t; }, $must)));
        if (!$must) return [];
        $gql = <<<'GQL'
query SearchByMustIngredients($must: [String!]!) {
  searchRecipesByIngredients(mustIngredients: $must) {
    edges {
      node {
        id
        name
        numberOfServings
        ingredientLines
        ingredients { name }
        source { recipeUrl }
        mainImage
      }
    }
  }
}
GQL;
        $data = $this->exec($gql, ['must' => $must], $err);
        if (!$data || !isset($data['data']['searchRecipesByIngredients'])) return [];
        $edges = $data['data']['searchRecipesByIngredients']['edges'] ?? [];
        $out = [];
        foreach ($edges as $edge) {
            if (!empty($edge['node']) && is_array($edge['node'])) {
                $out[] = $this->normalizeNode($edge['node']);
                if (count($out) >= $limit) break;
            }
        }
        return $out;
    }

    private function normalizeNode(array $n): array
    {
        // Prefer full ingredientLines; else fallback to ingredients names
        $ings = [];
        if (!empty($n['ingredientLines']) && is_array($n['ingredientLines'])) {
            foreach ($n['ingredientLines'] as $line) {
                $t = is_string($line) ? trim($line) : '';
                if ($t !== '') $ings[] = $t;
            }
        } elseif (!empty($n['ingredients']) && is_array($n['ingredients'])) {
            foreach ($n['ingredients'] as $ing) {
                if (isset($ing['name']) && is_string($ing['name'])) {
                    $t = trim($ing['name']);
                    if ($t !== '') $ings[] = $t;
                }
            }
        }

        // Instructions: array or string
        $steps = [];
        if (!empty($n['instructions']) && is_array($n['instructions'])) {
            foreach ($n['instructions'] as $s) {
                if (is_string($s)) {
                    $t = trim($s);
                    if ($t !== '') $steps[] = rtrim($t, '.');
                }
            }
        } elseif (!empty($n['instructions']) && is_string($n['instructions'])) {
            $parts = preg_split('/\n+|\r+|\.(\s|$)/u', $n['instructions']);
            foreach ($parts as $s) {
                $s = trim($s);
                if ($s !== '') $steps[] = rtrim($s, '.');
            }
        }

        $sourceUrl = null;
        if (!empty($n['source']) && is_array($n['source'])) {
            $sourceUrl = $n['source']['recipeUrl'] ?? null;
        }

        return [
            'id' => $n['id'] ?? ($n['databaseId'] ?? null),
            'title' => $n['name'] ?? 'Recipe',
            'image' => $n['mainImage'] ?? null,
            'sourceUrl' => $sourceUrl,
            'servings' => $n['numberOfServings'] ?? null,
            'ingredients_list' => $ings,
            'instructions_list' => $steps,
            'usedIngredients' => [],
            'missedIngredients' => [],
            'provider' => 'suggestic',
        ];
    }
}
