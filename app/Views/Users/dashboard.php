<?php
// In a real application, you would have a session check here to ensure the user is logged in.
// if (!isset($_SESSION['user_id'])) {
//     header('Location: /login.php');
//     exit();
// }

// Placeholder data - in your app, this would come from the database.
$username = "Alex";
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

// Include the main header for the application
// require_once __DIR__ . '/partials/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' · ' : '' ?>PantryPal</title>
    <?php echo vite_tags(); ?>

<body class="antialiased">
<!-- This is a placeholder for your header. Assuming it's included above. -->
<div class="bg-bg-component shadow-sm">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center space-x-2">
                <div style="width: var(--spacing-5); height: var(--spacing-5); background-color: var(--color-brand-accent); border-radius: 0 var(--radius-full) 0 var(--radius-full); transform: rotate(-45deg);" class="inline-block"></div>
                <a href="/" class="text-xl font-bold text-text-base" aria-label="PantryPal Home">PantryPal</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="#" class="btn btn-subtle btn-md hidden sm:inline-flex">Recipes</a>
                <button aria-label="User menu">
                    <img class="h-8 w-8 rounded-full object-cover" src="https://placehold.co/64x64/E0E0E0/36454F?text=A" alt="User Avatar for <?php echo htmlspecialchars($username); ?>">
                </button>
            </div>
        </div>
    </div>
</div>
<!-- End of placeholder header -->


<main class="container mx-auto p-4 sm:p-6 lg:p-8">

    <!-- Dashboard Header -->
    <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-text-heading">Welcome Back, <?php echo htmlspecialchars($username); ?>!</h1>
            <p class="text-text-muted mt-1">Here's a snapshot of your pantry today.</p>
        </div>
        <a href="/items/new" class="btn btn-cta btn-md mt-4 sm:mt-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" role="img" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            <span>Add New Item</span>
        </a>
    </section>

    <!-- Stat Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Total Items Card -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-text-heading">Total Items</h2>
            <p class="text-4xl font-bold text-text-base mt-2"><?php echo $pantry_stats['total_items']; ?></p>
            <p class="text-sm text-text-muted mt-1">Currently in your pantry</p>
        </div>

        <!-- Expiring Soon Card -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-text-heading">Expiring Soon</h2>
            <p class="text-4xl font-bold text-warning-text mt-2"><?php echo $pantry_stats['expiring_soon']; ?></p>
            <p class="text-sm text-text-muted mt-1">Items expiring in the next 3 days</p>
        </div>

        <!-- Recipes Available Card -->
        <div class="card p-6 flex flex-col justify-between">
            <div>
                <h2 class="text-lg font-semibold text-text-heading">Recipes Available</h2>
                <p class="text-4xl font-bold text-text-base mt-2"><?php echo $pantry_stats['recipes_available']; ?></p>
                <p class="text-sm text-text-muted mt-1">Based on your current ingredients</p>
            </div>
            <a href="/recipes/suggested" class="btn btn-secondary btn-sm mt-4 self-start">Find a Recipe</a>
        </div>
    </section>

    <!-- Pantry Item List -->
    <section>
        <h2 class="text-2xl font-bold text-text-heading mb-4">Your Pantry</h2>
        <div class="bg-bg-component rounded-xl shadow-md">
            <ul class="divide-y divide-border-default">
                <?php foreach ($pantry_items as $item): ?>
                    <li class="p-4 flex items-center justify-between <?php echo isset($item['expired']) && $item['expired'] ? 'opacity-60' : ''; ?>">
                        <div class="flex items-center">
                            <img src="https://placehold.co/80x80/E8F5E9/36454F?text=<?php echo urlencode($item['image']); ?>" class="w-12 h-12 rounded-lg object-cover mr-4" alt="Image of <?php echo htmlspecialchars($item['name']); ?>">
                            <div>
                                <p class="font-semibold text-text-base"><?php echo htmlspecialchars($item['name']); ?></p>
                                <p class="text-sm text-text-muted"><?php echo htmlspecialchars($item['status']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="badge <?php echo $item['badge_class']; ?> hidden sm:inline-block"><?php echo htmlspecialchars($item['category']); ?></span>
                            <a href="/items/edit/<?php echo $item['id']; ?>" class="btn btn-subtle btn-sm" aria-label="Edit <?php echo htmlspecialchars($item['name']); ?>">Edit</a>
                            <button class="btn btn-danger btn-sm" aria-label="Delete <?php echo htmlspecialchars($item['name']); ?>">Delete</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

</main>

<!-- This is a placeholder for your footer. Assuming it's included below. -->
<footer class="bg-bg-footer mt-12 py-8 border-t border-border-default">
    <div class="container mx-auto text-center text-text-muted text-sm">
        <p>&copy; <?php echo date("Y"); ?> PantryPal. All rights reserved.</p>
    </div>
</footer>
<!-- End of placeholder footer -->

</body>
</html>
