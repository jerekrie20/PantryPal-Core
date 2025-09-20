<?php

namespace Controllers;


use Helpers\Validator;
use Helpers\View;
use Models\GlobalItems;
use Models\Items;
use Services\SpoonacularService;

class ItemsController
{

    protected Items $item;
    protected GlobalItems $globalItemsModel;
    protected SpoonacularService $spoonacularService;

    public function __construct()
    {
        // Initialize required models and services
        $this->item = new Items();
        $this->globalItemsModel = new GlobalItems();
        $this->spoonacularService = new SpoonacularService();
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

            return View::render('Items/index', $data);

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

    public function create(): string
    {
        return View::render('Items/create');
    }

    /**
     * Handles the form submission for creating a new item.
     */
    public function store()
    {
        // Validate and early-return on failure
        $validationResult = $this->validation();
        if ($validationResult) {
            return $validationResult;
        }

        $itemName = $_POST['name'];

        // First, check if a global item with a similar normalized name already exists.
        $existingGlobalItem = $this->globalItemsModel->findByNormalizedName(strtolower(trim($itemName)));
        if ($existingGlobalItem) {
            // If it exists, skip the API and add it directly to the user's pantry.
            $this->item->insert($_POST, $existingGlobalItem['id']);
            header('Location: /dashboard');
            exit();
        }

        // If not in our DB, search the Spoonacular API for choices.
        $choices = $this->spoonacularService->searchForChoices($itemName);

        if (empty($choices)) {
            return View::render('Items/create', [
                'errors' => ['name' => 'Could not find any matching items. Please try a different name.'],
                'input' => $_POST
            ]);
        }

        // If choices are found, show the confirmation page.
        return View::render('Items/confirm', [
            'title' => 'Confirm Your Item',
            'choices' => $choices,
            'original_input' => $_POST
        ]);
    }

    /**
     * Step 2 of adding an item: Store the user's confirmed choice from the selection page.
     */
    public function storeConfirmed()
    {
        $apiId = (int)($_POST['api_id'] ?? 0);
        $originalInput = $_POST['original_input'] ?? [];

        if ($apiId <= 0) {
            // Handle error - this can happen if the form is submitted incorrectly.
            return View::render('Items/create', ['errors' => ['name' => 'Please select an item to confirm.'], 'input' => $originalInput]);
        }

        // Use the service to fetch final details and create the global item record in our DB.
        $apiKind = $_POST['api_kind'] ?? null;
        $globalItem = $this->spoonacularService->createGlobalItemFromApi($apiId, $apiKind, $originalInput['name']);

        if (!$globalItem) {
            return View::render('Items/create', ['errors' => ['name' => 'An error occurred while saving the item details.'], 'input' => $originalInput]);
        }

        // Add the new item to the user's personal pantry table, linking to the global item.
        $this->item->insert($originalInput, $globalItem['id']);

        // Redirect to a success page.
        header('Location: /dashboard');
        exit();
    }

    public function edit($id)
    {
    }

    public function update()
    {
    }

    public function show($id)
    {
        $userId = $_SESSION['user_id'] ?? null;
        $id = (int)$id;
        if (!$userId || $id <= 0) {
            http_response_code(404);
            return View::render('Pages/404', ['title' => 'Item Not Found']);
        }

        try {
            $row = $this->item->findWithGlobalById($id, $userId);
        } catch (\PDOException $e) {
            error_log('ItemsController::show DB error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }

        if (!$row) {
            http_response_code(404);
            return View::render('Pages/404', ['title' => 'Item Not Found']);
        }

        // Compute status similar to dashboard
        $status = 'In Stock';
        $badge = 'badge-success';
        $expiredFlag = false;
        if (!empty($row['expiration_date'])) {
            try {
                $today = new \DateTimeImmutable('today');
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
                // ignore
            }
        }

        $data = [
            'title' => $row['name'] . ' • Pantry Item',
            'item' => [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'category' => $row['category'] ?? 'Uncategorized',
                'image' => $row['image_url'] ?? null,
                'quantity' => $row['quantity'] ?? null,
                'unit' => $row['unit'] ?? null,
                'purchase_date' => $row['purchase_date'] ?? null,
                'expiration_date' => $row['expiration_date'] ?? null,
                'status' => $status,
                'badge_class' => $badge,
                'expired' => $expiredFlag,
                // Pass nutrition info decoded from JSON (if present)
                'nutrition' => (function($raw) {
                    if ($raw === null || $raw === '') { return null; }
                    if (is_array($raw)) { return $raw; }
                    $decoded = json_decode($raw, true);
                    return $decoded ?: null;
                })($row['nutrition_info'] ?? null),
            ]
        ];

        return View::render('Items/show', $data);
    }

    public function validation(): ?string
    {
        // Your Validator's constructor requires the database connection for the 'unique' rule.
        // We'll need to make it available here.
        global $conn;
        $validator = new Validator($_POST, $conn);

        $rules = [
            'name' => ['required' => true, 'min' => 2, 'max' => 255],
            'quantity' => ['required' => true, 'numeric' => true],
            'unit' => ['required' => false, 'max' => 10, 'string' => true],
            'purchase_date' => ['required' => false, 'date' => true],
            'expiration_date' => ['required' => false, 'date' => true]
        ];

        // Define the rules for your Validator's `check` method
        $validator->check($rules);

        // Use the `passed()` method to check if validation succeeded
        if (!$validator->passed()) {
            // If validation fails, return to the form with errors and original input
            return View::render('Items/create', [
                'title' => 'Add New Item',
                'errors' => $validator->errors(), // Use the errors() method
                'input' => $_POST
            ]);
        }

        return null; // success
    }

    public function destroy()
    {
    }


}