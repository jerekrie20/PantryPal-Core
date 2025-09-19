<?php

namespace Controllers;

use Helpers\Validator;
use Helpers\View;
use Models\Items;

class ItemsController
{

    protected $item;

    public function __construct()
    {
        $this->item = new Items();
    }

    public function index(): string
    {
        try {
            // 1. Get Inputs for Pagination
            // Get the current page from the URL query string (e.g., /items?page=2), default to 1
            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $itemsPerPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 15; // You can set how many items you want per page

            // 2. Get the Authenticated User's ID
            // The AuthMiddleware ensures this is set.
            $userId = $_SESSION['user_id'];

            // 3. Call the Model to Get Data
            // Use the findAll method to get the items and pagination details
            $results = $this->item->findAll($userId, $currentPage, $itemsPerPage);

            // 4. Prepare Data and Render the View
            // Pass the fetched items and the pagination metadata to the view.
            $data = [
                'title' => 'My Pantry',
                'items' => $results['items'],
                'pagination' => $results['pagination']
            ];

            return View::render('items/index', $data);

        } catch (\PDOException $e) {
            // Log the detailed database error for debugging purposes.
            error_log("Database Error in ItemsController::index(): " . $e->getMessage());

            // Display a generic, user-friendly error page.
            // It's important not to show the raw error message to the end-user.
            http_response_code(500);
            // Assuming you have an error view at views/errors/500.php
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    public function create(){
        return View::render('Items/create');
    }

    public function store(){


    }

    public function edit($id){}

    public function update(){}

    public function show($id){}

    public function validation(){
        global $db;
        $validator = new Validator($_POST, $db );
    }

    public function destroy(){}



}