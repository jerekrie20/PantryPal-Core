<?php

namespace Controllers;

use Helpers\View;
use Models\Items;

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
        $userId = $_SESSION['user_id'];

        // Total items and pagination meta (already implemented)
        $itemsPage = $this->item->findAll($userId);

        // Stats
        $pantry_stats = [
            'total_items' => $itemsPage['pagination']['totalItems'] ?? 0,
            'expiring_soon' => $this->item->countExpiringSoon($userId, 3),
            'recipes_available' => 7,
        ];

        // Recent pantry items with global data (name, category, image)
        $rawItems = $this->item->findRecentWithGlobal($userId, 6);
        $today = new \DateTimeImmutable('today');
        $pantry_items = [];
        foreach ($rawItems as $row) {
            $status = 'In Stock';
            $badge = 'badge-success';
            $expiredFlag = false;

            if (!empty($row['expiration_date'])) {
                try {
                    $exp = new \DateTimeImmutable($row['expiration_date']);
                    $diffDays = (int)$today->diff($exp)->format('%r%a');
                    if ($diffDays < 0) {
                        $status = 'Expired ' . (abs($diffDays) === 1 ? '1 day ago' : abs($diffDays) . ' days ago');
                        $badge = 'badge-danger';
                        $expiredFlag = true;
                    } elseif ($diffDays === 0) {
                        $status = 'Expires today';
                        $badge = 'badge-warning';
                    } elseif ($diffDays <= 3) {
                        $status = 'Expires in ' . ($diffDays === 1 ? '1 day' : $diffDays . ' days');
                        $badge = 'badge-warning';
                    } else {
                        $status = 'Expires in ' . $diffDays . ' days';
                        $badge = 'badge-neutral';
                    }
                } catch (\Exception $e) {
                    // leave default status
                }
            }

            $pantry_items[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'status' => $status,
                'category' => $row['category'] ?? 'Uncategorized',
                'badge_class' => $badge,
                'image' => $row['image_url'] ?? null,
                'expired' => $expiredFlag,
            ];
        }

        // Prepare the data to be passed to the view
        $data = [
            'title' => 'Dashboard',
            'username' => $username,
            'pantry_stats' => $pantry_stats,
            'pantry_items' => $pantry_items
        ];

        return View::render('/Users/dashboard', $data);
    }

}