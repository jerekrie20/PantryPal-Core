<?php

namespace Services\Recipes;

use GuzzleHttp\Client;

class ApiNinjasProvider implements RecipesProvider
{
    private Client $http;
    private ?string $apiKey;
    private array $browseSeeds = ['chicken','pasta','beef','salad','soup','rice','vegetarian','vegan','dessert','fish'];

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey
                    ?? ($_ENV['API_NINJAS_KEY'] ?? null)
                    ?? ($_SERVER['API_NINJAS_KEY'] ?? null)
                    ?? (getenv('API_NINJAS_KEY') ?: null);
        $this->http = new Client([
            'base_uri' => 'https://api.api-ninjas.com/',
            'timeout' => 8,
        ]);
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function searchByQuery(string $query, int $number = 12): array
    {
        if (!$this->isConfigured()) return [];
        try {
            $lim = max(1, min(30, (int)$number));
            $resp = $this->http->get('v1/recipe', [
                'headers' => [ 'X-Api-Key' => $this->apiKey ],
                'query' => [
                    'query' => $query,
                    'limit' => $lim,
                ],
            ]);
            $arr = json_decode((string)$resp->getBody(), true) ?: [];
            $arr = is_array($arr) ? $arr : [];
            $out = [];
            foreach ($arr as $r) {
                $out[] = $this->normalize($r);
                if (count($out) >= $lim) break;
            }
            return $out;
        } catch (\Throwable $e) {
            error_log('ApiNinjasProvider::searchByQuery error: '.$e->getMessage());
            return [];
        }
    }

    public function findByIngredients(array $ingredients, int $number = 12): array
    {
        // API Ninjas does not have a distinct ingredients endpoint; use the same query by joining keywords.
        $ingredients = array_values(array_filter(array_map(fn($s)=>trim((string)$s), $ingredients)));
        if (!$ingredients) return [];
        $q = implode(' ', $ingredients);
        return $this->searchByQuery($q, $number);
    }

    private function normalize(array $r): array
    {
        // API returns: title, ingredients (string with | or ,), servings, instructions
        $ings = [];
        if (!empty($r['ingredients']) && is_string($r['ingredients'])) {
            // Split on '|' first, then commas; trim items
            $parts = preg_split('/\||,|\n|\r/u', $r['ingredients']);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') $ings[] = $p;
            }
        }
        $steps = [];
        if (!empty($r['instructions']) && is_string($r['instructions'])) {
            $raw = preg_split('/\n+|\r+|\.(\s|$)/u', $r['instructions']);
            foreach ($raw as $s) {
                $s = trim($s);
                if ($s !== '') $steps[] = rtrim($s, '.');
            }
        }
        return [
            'id' => null, // API Ninjas doesn't provide a stable numeric id
            'title' => $r['title'] ?? 'Recipe',
            'image' => null, // endpoint doesn't provide images
            'sourceUrl' => null,
            'usedIngredients' => [],
            'missedIngredients' => [],
            // extra fields kept in payload when upserting
            'ingredients_list' => $ings,
            'instructions_list' => $steps,
            'servings' => $r['servings'] ?? null,
        ];
    }

    /** Browse using API Ninjas with limit/offset. Falls back to seeded keyword if no filter provided. */
    public function browseAll(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        if (!$this->isConfigured()) return ['results' => [], 'total' => 0];
        $lim = max(1, min(30, (int)$perPage));
        $offset = max(0, ((int)$page - 1) * $lim);
        // Choose a keyword: use provided cuisine/type/diet if present, else rotate seeded terms
        $query = '';
        foreach (['type','cuisine','diet'] as $k) {
            if (!empty($filters[$k]) && is_string($filters[$k])) { $query = (string)$filters[$k]; break; }
        }
        if ($query === '') {
            $seedIndex = max(0, $page - 1) % max(1, count($this->browseSeeds));
            $query = $this->browseSeeds[$seedIndex];
        }
        try {
            $resp = $this->http->get('v1/recipe', [
                'headers' => [ 'X-Api-Key' => $this->apiKey ],
                'query' => [
                    'query' => $query,
                    'limit' => $lim,
                    'offset' => $offset,
                ],
            ]);
            $arr = json_decode((string)$resp->getBody(), true) ?: [];
            $arr = is_array($arr) ? $arr : [];
            $results = [];
            foreach ($arr as $r) {
                $results[] = $this->normalize($r);
                if (count($results) >= $lim) break;
            }
            // API Ninjas does not return total count; return 0 to indicate unknown
            return ['results' => $results, 'total' => 0];
        } catch (\Throwable $e) {
            error_log('ApiNinjasProvider::browseAll error: '.$e->getMessage());
            return ['results' => [], 'total' => 0];
        }
    }
}
