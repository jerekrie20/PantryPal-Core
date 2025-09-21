<?php
namespace Services\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Services\FoodProvider;

class FdcProvider implements FoodProvider
{
    protected Client $client;
    protected string $apiKey;

    public function __construct(?Client $client = null, ?string $apiKey = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => 'https://api.nal.usda.gov/fdc/',
            'timeout'  => 6.0,
        ]);
        $this->apiKey = $apiKey ?? ($_ENV['FDC_API_KEY'] ?? '');
    }

    public function getSource(): string { return 'fdc'; }

    public function searchIngredients(string $q, int $limit = 5): array
    {
        try {
            $r = $this->client->get('v1/foods/search', [
                'query' => [
                    'query'    => $q,
                    'pageSize' => $limit,
                    'dataType' => 'Branded,Foundation,SR Legacy',
                    'api_key'  => $this->apiKey,
                ],
            ]);
            $d = json_decode($r->getBody(), true) ?: [];
            $out = [];
            foreach ($d['foods'] ?? [] as $f) {
                $out[] = [
                    'api_id'    => $f['fdcId'] ?? null,
                    'name'      => $f['description'] ?? '',
                    'brand'     => $f['brandOwner'] ?? null,
                    'image_url' => null, // FDC doesn't provide images
                    'type'      => 'ingredient',
                    'source'    => $this->getSource(),
                ];
            }
            return $out;
        } catch (GuzzleException $e) {
            error_log('FDC search error: '.$e->getMessage());
            return [];
        }
    }

    public function fetchIngredient(int|string $id): ?array
    {
        try {
            $r = $this->client->get("v1/food/{$id}", [
                'query' => ['api_key' => $this->apiKey],
            ]);
            $d = json_decode($r->getBody(), true) ?: null;
            if (!$d) return null;

            $category = $d['foodCategory'] ?? ($d['brandedFoodCategory'] ?? null);
            $nutrition = $d['labelNutrients'] ?? ($d['foodNutrients'] ?? null);

            return [
                'name'           => $d['description'] ?? '',
                'image_url'      => null,
                'category'       => $category,
                'nutrition_info' => $nutrition,
                'raw'            => $d,
            ];
        } catch (GuzzleException $e) {
            error_log('FDC detail error: '.$e->getMessage());
            return null;
        }
    }

    public function searchProducts(string $q, int $limit = 5): array { return []; }
    public function fetchProduct(int|string $id): ?array { return null; }
}
