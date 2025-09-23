<?php

namespace Services\Recipes;

use GuzzleHttp\Client;

class SpoonacularProvider implements RecipesProvider
{
    private Client $http;
    private ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey
                    ?? ($_ENV['SPOONACULAR_API_KEY'] ?? null)
                    ?? ($_SERVER['SPOONACULAR_API_KEY'] ?? null)
                    ?? (getenv('SPOONACULAR_API_KEY') ?: null);
        $this->http = new Client([
            'base_uri' => 'https://api.spoonacular.com/',
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
            $resp = $this->http->get('recipes/complexSearch', [
                'query' => [
                    'apiKey' => $this->apiKey,
                    'query' => $query,
                    'number' => max(1, min(20, $number)),
                    'addRecipeInformation' => true,
                ]
            ]);
            $data = json_decode((string)$resp->getBody(), true) ?: [];
            $results = $data['results'] ?? [];
            return array_map(function($r){ return $this->normalizeRecipe($r); }, $results);
        } catch (\Throwable $e) {
            error_log('SpoonacularProvider::searchByQuery error: '.$e->getMessage());
            return [];
        }
    }

    public function findByIngredients(array $ingredients, int $number = 12): array
    {
        if (!$this->isConfigured()) return [];
        $ingredients = array_values(array_filter(array_map(function($s){
            $s = trim((string)$s);
            // Spoonacular likes commas separated keywords; avoid commas in tokens
            return $s !== '' ? str_replace(',', ' ', $s) : '';
        }, $ingredients)));
        if (!$ingredients) return [];
        try {
            $resp = $this->http->get('recipes/findByIngredients', [
                'query' => [
                    'apiKey' => $this->apiKey,
                    'ingredients' => implode(',', $ingredients),
                    'number' => max(1, min(20, $number)),
                    'ranking' => 1,
                ]
            ]);
            $arr = json_decode((string)$resp->getBody(), true) ?: [];

            // Enrich results with detailed information via informationBulk
            $ids = [];
            foreach ($arr as $r) {
                if (isset($r['id']) && is_numeric($r['id'])) $ids[] = (int)$r['id'];
            }
            $detailsById = [];
            if ($ids) {
                $details = $this->getInformationBulk($ids);
                foreach ($details as $det) {
                    if (isset($det['id'])) $detailsById[(int)$det['id']] = $det;
                }
            }
            $out = [];
            foreach ($arr as $r) {
                $id = isset($r['id']) ? (int)$r['id'] : null;
                if ($id !== null && isset($detailsById[$id])) {
                    // Merge detail into base; let details override/add fields
                    $r = array_merge($r, $detailsById[$id]);
                }
                $out[] = $this->normalizeRecipe($r);
            }
            return $out;
        } catch (\Throwable $e) {
            error_log('SpoonacularProvider::findByIngredients error: '.$e->getMessage());
            return [];
        }
    }

    /** Get full recipe information by Spoonacular ID. */
    public function getRecipeInformation(int $id): array
    {
        if (!$this->isConfigured() || $id <= 0) return [];
        try {
            $resp = $this->http->get("recipes/{$id}/information", [
                'query' => [
                    'apiKey' => $this->apiKey,
                    'includeNutrition' => 'false',
                ]
            ]);
            $data = json_decode((string)$resp->getBody(), true) ?: [];
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            error_log('SpoonacularProvider::getRecipeInformation error: ' . $e->getMessage());
            return [];
        }
    }

    /** Bulk fetch recipe information for up to ~50 IDs per request. */
    public function getInformationBulk(array $ids): array
    {
        if (!$this->isConfigured()) return [];
        // sanitize and limit
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, fn($v)=>is_numeric($v) && (int)$v > 0))));
        if (!$ids) return [];
        // Spoonacular allows many IDs; to be safe, chunk by 50
        $all = [];
        $chunks = array_chunk($ids, 50);
        foreach ($chunks as $chunk) {
            try {
                $resp = $this->http->get('recipes/informationBulk', [
                    'query' => [
                        'apiKey' => $this->apiKey,
                        'ids' => implode(',', $chunk),
                        'includeNutrition' => 'false',
                    ]
                ]);
                $data = json_decode((string)$resp->getBody(), true) ?: [];
                if (is_array($data)) $all = array_merge($all, $data);
            } catch (\Throwable $e) {
                error_log('SpoonacularProvider::getInformationBulk error: ' . $e->getMessage());
                // continue other chunks
            }
        }
        return $all;
    }

    private function normalizeRecipe(array $r): array
    {
        // Try to keep a consistent structure across endpoints
        $used = [];
        $missed = [];
        if (!empty($r['usedIngredients']) && is_array($r['usedIngredients'])) {
            foreach ($r['usedIngredients'] as $ing) {
                if (isset($ing['name'])) $used[] = (string)$ing['name'];
            }
        }
        if (!empty($r['missedIngredients']) && is_array($r['missedIngredients'])) {
            foreach ($r['missedIngredients'] as $ing) {
                if (isset($ing['name'])) $missed[] = (string)$ing['name'];
            }
        }
        // When using complexSearch with addRecipeInformation
        if (empty($used) && !empty($r['extendedIngredients']) && is_array($r['extendedIngredients'])) {
            foreach ($r['extendedIngredients'] as $ing) {
                if (isset($ing['name'])) $used[] = (string)$ing['name'];
            }
        }
        $sourceUrl = $r['sourceUrl'] ?? ($r['spoonacularSourceUrl'] ?? null);
        return [
            'id' => $r['id'] ?? null,
            'title' => $r['title'] ?? ($r['name'] ?? 'Recipe'),
            'image' => $r['image'] ?? null,
            'sourceUrl' => $sourceUrl,
            'usedIngredients' => $used,
            'missedIngredients' => $missed,
        ];
    }

    /** Browse all recipes using complexSearch with filters and pagination. */
    public function browseAll(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        if (!$this->isConfigured()) return ['results' => [], 'total' => 0];
        // Map allowed filters to Spoonacular params
        $map = [
            'diet' => 'diet',
            'cuisine' => 'cuisine',
            'type' => 'type',
            'intolerances' => 'intolerances',
            'maxReadyTime' => 'maxReadyTime',
            'sort' => 'sort', // e.g., popularity, healthiness, time
        ];
        $q = [
            'apiKey' => $this->apiKey,
            'addRecipeInformation' => true,
            'number' => max(1, min(50, $perPage)),
            'offset' => max(0, ($page - 1) * max(1, min(50, $perPage))),
        ];
        foreach ($map as $in => $param) {
            if (!empty($filters[$in])) {
                $q[$param] = is_array($filters[$in]) ? implode(',', $filters[$in]) : $filters[$in];
            }
        }
        try {
            $resp = $this->http->get('recipes/complexSearch', ['query' => $q]);
            $data = json_decode((string)$resp->getBody(), true) ?: [];
            $results = $data['results'] ?? [];
            $norm = array_map(function($r){ return $this->normalizeRecipe($r); }, $results);
            $total = isset($data['totalResults']) ? (int)$data['totalResults'] : 0;
            return ['results' => $norm, 'total' => $total];
        } catch (\Throwable $e) {
            error_log('SpoonacularProvider::browseAll error: ' . $e->getMessage());
            return ['results' => [], 'total' => 0];
        }
    }
}
