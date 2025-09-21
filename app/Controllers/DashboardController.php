<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Models\Products;

class DashboardController
{
    protected Items $item;

    public function __construct(){
        $this->item = new Items();
    }

    /**
     * Display the user's dashboard.
     *
     * This method fetches all necessary data for the dashboard and passes it
     * to the view for rendering.
     */
    public function index(): string {
        $username = $_SESSION['username'];
        $userId   = $_SESSION['user_id'];

        $itemsPage = $this->item->findAll($userId);

        $pantry_stats = [
            'total_items'      => $itemsPage['pagination']['totalItems'] ?? 0,
            'expiring_soon'    => $this->item->countExpiringSoon($userId, 3),
            'recipes_available'=> 7,
        ];

        $rawItems = $this->item->findRecent($userId, 6);
        $today    = new \DateTimeImmutable('today');
        $pantry_items = [];
        $ingModel = new Ingredients();
        $prodModel = new Products();

        foreach ($rawItems as $row) {
            $name = 'Item';
            $category = null;
            $image = null;

            if (!empty($row['ingredient_id'])) {
                $ing = $ingModel->find((int)$row['ingredient_id']);
                if ($ing) {
                    $name = $ing['name'] ?? $name;
                    $category = $this->stringifyCategory($ing['category'] ?? null);
                    $image = $ing['image_url'] ?? null;
                }
            } elseif (!empty($row['product_id'])) {
                $prod = $prodModel->find((int)$row['product_id']);
                if ($prod) {
                    $name = $prod['title'] ?? $name;
                    $category = $this->stringifyCategory($prod['category'] ?? null);
                    $image = $prod['image_url'] ?? null;
                }
            }

            // Status/badge
            $status = 'In Stock';
            $badge  = 'badge-success';
            $expiredFlag = false;

            if (!empty($row['expiration_date'])) {
                try {
                    $exp      = new \DateTimeImmutable($row['expiration_date']);
                    $diffDays = (int)$today->diff($exp)->format('%r%a');
                    if ($diffDays < 0) {
                        $status = 'Expired ' . (abs($diffDays) === 1 ? '1 day ago' : abs($diffDays) . ' days ago');
                        $badge  = 'badge-danger';
                        $expiredFlag = true;
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
                    // leave defaults
                }
            }

            $url = '/items/view/' . (int)$row['id'];
            if (!empty($row['ingredient_id'])) {
                $url = '/ingredients/view/' . (int)$row['id'];
            } elseif (!empty($row['product_id'])) {
                $url = '/products/view/' . (int)$row['id'];
            }

            $pantry_items[] = [
                'id'          => (int)$row['id'],
                'name'        => $name,
                'status'      => $status,
                'category'    => $category ?? 'Uncategorized',
                'badge_class' => $badge,
                'image'       => $image,
                'expired'     => $expiredFlag,
                'url'         => $url,
            ];
        }

        $data = [
            'title'         => 'Dashboard',
            'username'      => $username,
            'pantry_stats'  => $pantry_stats,
            'pantry_items'  => $pantry_items
        ];

        return View::render('/Users/dashboard', $data);
    }

    /** Collapse category values that might arrive as arrays / JSON strings. */
    private function stringifyCategory($cat): ?string
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
            return $cat;
        }
        if (is_array($cat)) {
            if (isset($cat['categoryPath']) && is_array($cat['categoryPath'])) {
                return implode(' › ', array_filter($cat['categoryPath'], 'is_string'));
            }
            $vals = [];
            foreach ($cat as $v) if (is_string($v)) $vals[] = $v;
            return $vals ? implode(' › ', $vals) : null;
        }
        return null;
    }


}