<?php

namespace Services\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\FoodProvider;

class OffProvider implements FoodProvider
{
    protected Client $clientWorld;
    protected Client $clientUS;
    protected string $userAgent;

    public function __construct(?Client $client = null, ?string $userAgent = null)
    {
        $this->userAgent = $userAgent ?? ($_ENV['OFF_USER_AGENT'] ?? 'PantryPal/1.0 (your-email@example.com)');

        // Build a handler with retry middleware
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry(
            $this->decider(),
            $this->delay()
        ));

        $baseOpts = [
            'timeout' => 12.0,      // total request timeout
            'connect_timeout' => 3.0,       // time to establish TCP
            'http_errors' => false,     // don’t throw on 4xx/5xx automatically
            'handler' => $stack,
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
            ],
            // Prefer IPv4 can help on some networks
            'curl' => defined('CURL_IPRESOLVE_V4') ? [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4] : [],
        ];

        $this->clientWorld = new Client(array_merge($baseOpts, [
            'base_uri' => 'https://world.openfoodfacts.org/',
        ]));
        $this->clientUS = new Client(array_merge($baseOpts, [
            'base_uri' => 'https://us.openfoodfacts.org/',
        ]));
    }

    public function getSource(): string
    {
        return 'off';
    }

    public function searchIngredients(string $q, int $limit = 5): array
    {
        return [];
    }

    public function fetchIngredient(int|string $id): ?array
    {
        return null;
    }

    public function searchProducts(string $q, int $limit = 5): array
    {
        // try world → fallback to us; second attempt uses smaller page_size
        $attempts = [
            [$this->clientWorld, $limit],
            [$this->clientUS, max(3, (int)ceil($limit / 2))],
        ];

        foreach ($attempts as [$client, $pageSize]) {
            try {
                $resp = $client->get('cgi/search.pl', [
                    'query' => [
                        'search_terms' => $q,
                        'search_simple' => 1,
                        'json' => 1,
                        'page_size' => $pageSize,
                        'fields' => 'code,product_name,brands,image_url,categories',
                    ],
                ]);

                if ($resp->getStatusCode() !== 200) {
                    continue;
                }

                $data = json_decode((string)$resp->getBody(), true);
                if (!is_array($data)) {
                    continue;
                }

                $out = [];
                foreach ($data['products'] ?? [] as $p) {
                    $brand = null;
                    if (!empty($p['brands'])) {
                        $brand = trim(explode(',', $p['brands'])[0]);
                    }
                    $out[] = [
                        'api_id' => $p['code'] ?? null,
                        'name' => $p['product_name'] ?? '',
                        'brand' => $brand,
                        'image_url' => $p['image_url'] ?? null,
                        'type' => 'product',
                        'source' => $this->getSource(),
                    ];
                }
                if (!empty($out)) return $out;
            } catch (GuzzleException $e) {
                error_log('OFF search error: ' . $e->getMessage());
                // fall through to next attempt
            }
        }

        // final fallback: no results
        return [];
    }

    public function fetchProduct(int|string $id): ?array
    {
        // try world → fallback to us
        foreach ([$this->clientWorld, $this->clientUS] as $client) {
            try {
                $resp = $client->get("api/v2/product/{$id}.json");
                if ($resp->getStatusCode() !== 200) {
                    continue;
                }
                $d = json_decode((string)$resp->getBody(), true);
                if (!$d || empty($d['product'])) continue;

                $p = $d['product'];
                $brand = null;
                if (!empty($p['brands'])) {
                    $brand = trim(explode(',', $p['brands'])[0]);
                }

                return [
                    'name' => $p['product_name'] ?? '',
                    'brand' => $brand,
                    'upc' => $p['code'] ?? null,
                    'size_text' => $p['quantity'] ?? null,
                    'image_url' => $p['image_url'] ?? null,
                    'category' => $p['categories'] ?? null,
                    'nutrition_info' => $p['nutriments'] ?? null,
                    'raw' => $p,
                ];
            } catch (GuzzleException $e) {
                error_log('OFF detail error: ' . $e->getMessage());
                // try next client
            }
        }
        return null;
    }

    /** Retry on timeouts, 429, 5xx */
    private function decider(): callable
    {
        return function (
            $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Throwable $exception = null
        ): bool {
            if ($retries >= 2) return false; // 2 retries max (3 total attempts)

            // Network timeouts / connect issues
            if ($exception) {
                $msg = $exception->getMessage();
                if (stripos($msg, 'cURL error 28') !== false || stripos($msg, 'Connection timed out') !== false) {
                    return true;
                }
            }

            if ($response) {
                $code = $response->getStatusCode();
                if ($code == 429) return true;           // rate limited
                if ($code >= 500 && $code < 600) return true; // server errors
            }
            return false;
        };
    }

    /** Exponential backoff: 300ms, 900ms */
    private function delay(): callable
    {
        return function ($retries) {
            return (int)(300 * (3 ** ($retries - 1)));
        };
    }
}
