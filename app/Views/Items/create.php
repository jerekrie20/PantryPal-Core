<?php
/**
 * Add New Pantry Item View
 */


// --- Templating Logic ---
// Start output buffering to capture the main content
ob_start();

// Include the reusable form components
require_once VIEW_PATH . '/components/form_elements.php';
?>

<div class="max-w-2xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-text-heading">Add a New Item</h1>
        <p class="text-text-muted mt-1">Fill out the details below to add an item to your pantry.</p>
    </div>

    <!-- Add Item Form -->
    <div class="card p-6 md:p-8">
        <form action="/items" method="POST" class="space-y-6">

            <?php form_input('name', 'Item Name', 'text', [
                'placeholder' => 'e.g., Organic Milk',
                'required' => true,
                'value' => $input['name'] ?? '',
                'error' => $errors['name'] ?? null
            ]); ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php form_input('quantity', 'Quantity', 'number', [
                    'value' => $input['quantity'] ?? '1',
                    'required' => true,
                    'step' => '0.01', // Allows decimal quantities
                    'error' => $errors['quantity'] ?? null
                ]); ?>

                <?php form_input('unit', 'Unit', 'text', [
                    'placeholder' => 'e.g., L, kg, pcs',
                    'required' => false,
                    'value' => $input['unit'] ?? '',
                    'error' => $errors['unit'] ?? null
                ]); ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php form_input('purchase_date', 'Purchase Date', 'date', [
                    'required' => false,
                    'value' => $input['purchase_date'] ?? '',
                    'error' => $errors['purchase_date'] ?? null
                ]); ?>

                <?php form_input('expiration_date', 'Expiration Date', 'date', [
                    'required' => false,
                    'value' => $input['expiration_date'] ?? '',
                    'error' => $errors['expiration_date'] ?? null
                ]); ?>
            </div>

            <div class="pt-6 border-t border-border-default flex flex-col sm:flex-row-reverse gap-3">
                <?php form_button('Add Item to Pantry', 'cta', ['size' => 'md', 'fullWidth' => true, 'class' => 'sm:w-auto']); ?>
                <a href="/dashboard" class="btn btn-secondary btn-md w-full sm:w-auto flex justify-center">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php
// Get the captured content and assign it to a variable for the layout
$content = ob_get_clean();

// Include the main application layout, which will render the content
require_once VIEW_PATH . '/layouts/Users/layout.php';
?>
