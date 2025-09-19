<?php

namespace Controllers;

use Helpers\View;

class DashboardController
{

    /**
     * Display the user's dashboard.
     *
     * This method fetches all necessary data for the dashboard and passes it
     * to the view for rendering.
     */
    public function index(): string {


        $username = $_SESSION['username'];

        $pantry_stats = [
            'total_items' => 24,
            'expiring_soon' => 3,
            'recipes_available' => 7,
        ];

        $pantry_items = [
            ['id' => 1, 'name' => 'Organic Milk', 'status' => 'Expires in 7 days', 'category' => 'Dairy', 'badge_class' => 'badge-neutral', 'image' => 'Milk'],
            ['id' => 2, 'name' => 'Free-Range Eggs', 'status' => 'Expires in 3 days', 'category' => 'Dairy', 'badge_class' => 'badge-warning', 'image' => 'Eggs'],
            ['id' => 3, 'name' => 'Sourdough Bread', 'status' => 'Expired Yesterday', 'category' => 'Bakery', 'badge_class' => 'badge-danger', 'image' => 'Bread', 'expired' => true],
            ['id' => 4, 'name' => 'Avocados', 'status' => 'Expires in 2 days', 'category' => 'Produce', 'badge_class' => 'badge-warning', 'image' => 'Avocado'],
            ['id' => 5, 'name' => 'Chicken Breast', 'status' => 'Expires in 1 day', 'category' => 'Meat', 'badge_class' => 'badge-warning', 'image' => 'Chicken'],
            ['id' => 6, 'name' => 'Quinoa', 'status' => 'In Stock', 'category' => 'Grains', 'badge_class' => 'badge-success', 'image' => 'Quinoa'],
        ];

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