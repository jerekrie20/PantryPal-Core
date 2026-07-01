<?php

namespace Services\Pantry;

use Services\FoodService;
use Services\Nutrition\Normalizer as NutritionNormalizer;

/**
 * Builds display-ready pantry item arrays from joined `items` rows
 * (as returned by Models\Items::find / findAll / findRecentWithGlobal).
 *
 * Single source for logic that previously lived in four places:
 * DashboardController, ItemsController::show, IngredientsController::show,
 * and the Items/index view.
 *
 * - expirationStatus(): date string -> status text / badge class / expired flag
 * - summary():          row -> light list-item shape (dashboard, pantry index)
 * - detail():           row -> full detail shape (show pages, incl. nutrition)
 */
class PantryItemAssembler
{
    public function __construct(private ?FoodService $food = null)
    {
        // FoodService is constructed lazily inside detail(); its constructor
        // pulls in models that need a live DB connection.
    }

    /**
     * @return array{status: string, badge: string, expired: bool}
     */
    public static function expirationStatus(?string $expirationDate): array
    {
        $status  = 'In Stock';
        $badge   = 'badge-success';
        $expired = false;

        if (!empty($expirationDate)) {
            try {
                $today    = new \DateTimeImmutable('today');
                $exp      = new \DateTimeImmutable($expirationDate);
                $diffDays = (int)$today->diff($exp)->format('%r%a');
                if ($diffDays < 0) {
                    $status  = 'Expired ' . (abs($diffDays) === 1 ? '1 day ago' : abs($diffDays) . ' days ago');
                    $badge   = 'badge-danger';
                    $expired = true;
                } elseif ($diffDays === 0) {
                    $status = 'Expires today';
                    $badge  = 'badge-warning';
                } elseif ($diffDays <= 3) {
                    $status = 'Expires in ' . ($diffDays === 1 ? '1 day' : $diffDays . ' days');
                    $badge  = 'badge-warning';
                } else {
                    $status = 'Expires in ' . $diffDays . ' days';
                    $badge  = 'badge-neutral';
                }
            } catch (\Exception $e) {
                // unparseable date: keep defaults
            }
        }

        return ['status' => $status, 'badge' => $badge, 'expired' => $expired];
    }

    /**
     * Light list-item shape for the dashboard and pantry index.
     *
     * @return array{id:int, kind:string, name:string, status:string, badge_class:string,
     *               category:string, image:?string, expired:bool, url:string}
     */
    public static function summary(array $row): array
    {
        $id           = (int)($row['id'] ?? 0);
        $isIngredient = !empty($row['ingredient_id']);
        $isProduct    = !$isIngredient && !empty($row['product_id']);
        $kind         = $isProduct ? 'product' : 'ingredient';

        if ($isIngredient) {
            $name     = $row['ingredient_name'] ?? ($row['entered_name'] ?? null);
            $category = CategoryFormatter::stringify($row['ingredient_category'] ?? null);
            $image    = $row['ingredient_image_url'] ?? null;
            $url      = '/ingredients/view/' . $id;
        } elseif ($isProduct) {
            $name     = $row['product_title'] ?? ($row['entered_name'] ?? null);
            $category = CategoryFormatter::stringify($row['product_category'] ?? null);
            $image    = $row['product_image_url'] ?? null;
            $url      = '/products/view/' . $id;
        } else {
            $name     = $row['entered_name'] ?? null;
            $category = null;
            $image    = null;
            $url      = '/items/view/' . $id;
        }

        if (!$name) {
            $name = ($isProduct ? 'Product #' : ($isIngredient ? 'Ingredient #' : 'Item #')) . $id;
        }

        $exp = self::expirationStatus($row['expiration_date'] ?? null);

        return [
            'id'          => $id,
            'kind'        => $kind,
            'name'        => $name,
            'status'      => $exp['status'],
            'badge_class' => $exp['badge'],
            'category'    => $category ?? 'Uncategorized',
            'image'       => $image,
            'expired'     => $exp['expired'],
            'url'         => $url,
        ];
    }

    /**
     * Full detail shape for the show pages. Expects the joined row from
     * Models\Items::find (ingredient_* and product_* aliases present).
     *
     * Nutrition resolution is one chain; ingredient_* fields are null on
     * product-backed rows and vice versa, so each side no-ops for the other:
     *   1. live FatSecret fetch when the ingredient came from FatSecret
     *   2. stored ingredient nutrition_info (tolerant JSON decode)
     *   3. live FatSecret fetch when the product came from FatSecret
     *   4. product raw_payload (OFF; unwrap 'product', patch brand/upc/image)
     *   5. stored product nutrition_info
     */
    public function detail(array $row): array
    {
        $nutrition  = null;
        $rawNutri   = null;
        $productRaw = null;

        // 1. Ingredient sourced from FatSecret: live fetch is freshest.
        if (($row['ingredient_api_source'] ?? '') === 'fatsecret' && !empty($row['ingredient_api_id'])) {
            $fsData = $this->foodService()->getFatSecretFood($row['ingredient_api_id']);
            if ($fsData && isset($fsData['food'])) {
                $rawNutri  = $fsData['food'];
                $nutrition = NutritionNormalizer::normalize($rawNutri);
            }
        }

        // 2. Stored ingredient nutrition.
        if ($nutrition === null) {
            $ingNutri = $row['ingredient_nutrition_info'] ?? ($row['nutrition_info'] ?? null);
            $decoded  = self::decodeLoose($ingNutri);
            if (is_array($decoded)) {
                $rawNutri  = $decoded;
                $nutrition = NutritionNormalizer::normalize($decoded);
            }
        }

        // 3. Product sourced from FatSecret: live fetch.
        if ($nutrition === null && ($row['product_api_source'] ?? '') === 'fatsecret' && !empty($row['product_api_id'])) {
            $fsData = $this->foodService()->getFatSecretFood($row['product_api_id']);
            if ($fsData && isset($fsData['food'])) {
                $productRaw = $fsData['food'];
                $nutrition  = NutritionNormalizer::normalize($productRaw);
            }
        }

        // 4. Product raw payload (OFF shape).
        if ($nutrition === null && !empty($row['product_raw_payload'])) {
            $payload = is_array($row['product_raw_payload'])
                ? $row['product_raw_payload']
                : json_decode((string)$row['product_raw_payload'], true);

            if (is_array($payload) && isset($payload['product']) && is_array($payload['product'])) {
                $payload = $payload['product']; // OFF embeds under 'product'
            }
            if (is_array($payload)) {
                if (!isset($payload['brand']) && isset($payload['brands'])) {
                    $payload['brand'] = $payload['brands'];
                }
                if (!isset($payload['upc'])) {
                    $payload['upc'] = $payload['code'] ?? ($row['product_upc'] ?? null);
                }
                if (!isset($payload['image']) && isset($payload['image_url'])) {
                    $payload['image'] = $payload['image_url'];
                }
                $productRaw = $payload;
                $nutrition  = NutritionNormalizer::normalize($payload);
            }
        }

        // 5. Stored product nutrition.
        if ($nutrition === null && !empty($row['product_nutrition_info'])) {
            $pn = is_array($row['product_nutrition_info'])
                ? $row['product_nutrition_info']
                : json_decode((string)$row['product_nutrition_info'], true);
            if (is_array($pn)) {
                $nutrition = NutritionNormalizer::normalize($pn);
            }
        }

        $name = $row['ingredient_name']
            ?? ($row['product_title'] ?? ($row['entered_name'] ?? 'Item'));

        $exp = self::expirationStatus($row['expiration_date'] ?? null);

        return [
            'id'              => (int)($row['id'] ?? 0),
            'name'            => $name,
            'category'        => CategoryFormatter::stringify($row['ingredient_category'] ?? ($row['product_category'] ?? null)),
            'image'           => $row['ingredient_image_url'] ?? ($row['product_image_url'] ?? ($productRaw['image'] ?? null)),
            'quantity'        => $row['quantity'] ?? null,
            'unit'            => $row['unit'] ?? null,
            'purchase_date'   => $row['purchase_date'] ?? null,
            'expiration_date' => $row['expiration_date'] ?? null,
            'status'          => $exp['status'],
            'badge_class'     => $exp['badge'],
            'nutrition'       => $nutrition,
            'nutrition_raw'   => $rawNutri,
            'brand'           => $row['product_brand'] ?? ($row['ingredient_brand'] ?? ($row['entered_brand'] ?? null)),
            'product_title'   => $row['product_title'] ?? null,
            'product_raw'     => $productRaw,
        ];
    }

    /**
     * Tolerant JSON decode for nutrition blobs that may be arrays already,
     * plain JSON, escaped JSON, or JSON nested inside a JSON string.
     */
    private static function decodeLoose(mixed $value): ?array
    {
        if ($value === null || $value === '') return null;
        if (is_array($value)) return $value;

        $s = (string)$value;
        $decoded = json_decode($s, true);
        if (is_array($decoded)) return $decoded;

        $decoded = json_decode(stripslashes($s), true);
        if (is_array($decoded)) return $decoded;

        $once = json_decode($s, true);
        if (is_string($once)) {
            $decoded = json_decode($once, true);
            if (is_array($decoded)) return $decoded;
        } elseif (is_array($once)) {
            foreach ($once as $vv) {
                if (is_string($vv) && strlen($vv) > 10 && ($vv[0] === '{' || $vv[0] === '[')) {
                    $decoded = json_decode($vv, true);
                    if (is_array($decoded)) return $decoded;
                }
            }
        }
        return null;
    }

    private function foodService(): FoodService
    {
        return $this->food ??= new FoodService();
    }
}
