<?php
/**
 * User Dashboard View
 * This file is a template and expects the following variables to be passed to it from the controller:
 * @var string $title The page title.
 * @var string $username The current user's name.
 * @var array $pantry_stats An array with dashboard statistics.
 * @var array $pantry_items An array of the user's pantry items.
 */

// --- Templating Logic ---
// Start output buffering to capture the main content for the layout.
ob_start();
?>

<!-- Dashboard Header -->
<section class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8" aria-labelledby="dashboard-heading">
    <div>
        <h1 id="dashboard-heading" class="text-3xl font-bold text-text-heading">Welcome Back, <?php echo htmlspecialchars($username); ?>!</h1>
        <p class="text-text-muted mt-1">Here's a snapshot of your pantry today.</p>
    </div>
    <a href="/items/new" class="btn btn-cta btn-md mt-4 sm:mt-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" role="img" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
        <span>Add New Item</span>
    </a>
</section>

<!-- Stat Cards -->
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" aria-label="Pantry Statistics">
    <?php
    // Total Items Card
    $title_card = "Total Items";
    $value = $pantry_stats['total_items'];
    $description = "Currently in your pantry";
    include VIEW_PATH . '/Components/stat_card.php';

    // Expiring Soon Card
    $title_card = "Expiring Soon";
    $value = $pantry_stats['expiring_soon'];
    $description = "Items expiring in the next 3 days";
    $accent_color = "text-warning-text";
    include VIEW_PATH . '/Components/stat_card.php';
    unset($accent_color); // Unset optional variables

    // Recipes Available Card
    $title_card = "Recipes Available";
    $value = $pantry_stats['recipes_available'];
    $description = "Based on your current ingredients";
    $link_href = "/recipes/suggested";
    $link_text = "Find a Recipe";
    include VIEW_PATH . '/Components/stat_card.php';
    unset($link_href, $link_text); // Unset optional variables
    ?>
</section>

<!-- Pantry Item List -->
<section aria-labelledby="pantry-list-heading">
    <h2 id="pantry-list-heading" class="text-2xl font-bold text-text-heading mb-4">Your Pantry</h2>
    <div class="bg-bg-component rounded-xl shadow-md">
        <ul class="divide-y divide-border-default">
            <?php if (empty($pantry_items)): ?>
                <li class="p-8 text-center text-text-muted">Your pantry is empty. Add your first item!</li>
            <?php else: ?>
                <?php foreach ($pantry_items as $item): ?>
                    <?php include VIEW_PATH . '/Components/pantry_item.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</section>

<?php
// Get the captured content and assign it to a variable for the layout
$content = ob_get_clean();

// Include the main application layout, which will render the content
require_once VIEW_PATH . '/layouts/Users/layout.php';
?>
