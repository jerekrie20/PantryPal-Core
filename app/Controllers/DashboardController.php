<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Models\Products;
use Models\Recipes;
use Models\Updates;
use Services\Pantry\PantryItemAssembler;

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

        $pantry_items = array_map([PantryItemAssembler::class, 'summary'], $rawItems);

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