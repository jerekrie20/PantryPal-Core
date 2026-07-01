<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Models\Products;
use Models\Recipes;
use Models\Updates;
use Services\Pantry\CategoryFormatter;

class DashboardController
{
    protected Items $item;
    protected Recipes $recipes;
    protected Updates $updates;

    public function __construct(){
        $this->item = new Items();
        $this->recipes = new Recipes();
        $this->updates = new Updates();
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

        // Cache keys
        $statsKey  = 'pp:user:' . (int)$userId . ':dashboard:stats:v1';
        $recentKey = 'pp:user:' . (int)$userId . ':items:recent:v1';
        $pantry_stats = \Helpers\Cache::get($statsKey);
        $rawItems = \Helpers\Cache::get($recentKey);

        if (!is_array($pantry_stats) || !is_array($rawItems)) {
            // Compute fresh values when cache miss
            $itemsPage = $this->item->findAll($userId);
            $savedCount = 0;
            try { $savedCount = $this->recipes->countSavedForUser((int)$userId); } catch (\Throwable $e) { $savedCount = 0; }

            $pantry_stats = [
                'total_items'      => $itemsPage['pagination']['totalItems'] ?? 0,
                'expiring_soon'    => $this->item->countExpiringSoon($userId, 3),
                'recipes_saved'    => $savedCount,
            ];

            $rawItems = $this->item->findRecentWithGlobal($userId, 6);
            // Cache briefly to keep dashboard snappy
            try { \Helpers\Cache::set($statsKey, $pantry_stats, 120); } catch (\Throwable $e) { /* ignore */ }
            try { \Helpers\Cache::set($recentKey, $rawItems, 60); } catch (\Throwable $e) { /* ignore */ }
        }

        $today    = new \DateTimeImmutable('today');
        $pantry_items = [];

        foreach ($rawItems as $row) {
            $name = 'Item';
            $category = null;
            $image = null;

            if (!empty($row['ingredient_id'])) {
                $name = $row['ingredient_name'] ?? $name;
                $category = CategoryFormatter::stringify($row['ingredient_category'] ?? null);
                $image = $row['ingredient_image_url'] ?? null;
            } elseif (!empty($row['product_id'])) {
                $name = $row['product_title'] ?? $name;
                $category = CategoryFormatter::stringify($row['product_category'] ?? null);
                $image = $row['product_image_url'] ?? null;
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

        // Admin/user updates
        $updates = [];
        try { $updates = $this->updates->listActiveForUser((int)$userId, 5); } catch (\Throwable $e) { $updates = []; }

        $data = [
            'title'         => 'Dashboard',
            'username'      => $username,
            'pantry_stats'  => $pantry_stats,
            'pantry_items'  => $pantry_items,
            'updates'       => $updates,
        ];

        return View::render('/Users/dashboard', $data);
    }

}