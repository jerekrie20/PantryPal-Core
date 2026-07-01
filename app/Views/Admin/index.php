<?php
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-8">
    <div>
        <p class="eyebrow mb-1">Admin</p>
        <h1 class="text-text-heading">Dashboard</h1>
    </div>
    <div class="hidden sm:flex gap-2">
        <a class="btn btn-secondary btn-md" href="/admin/recipes/create">Add recipe</a>
        <a class="btn btn-secondary btn-md" href="/admin/updates/create">Post update</a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php
    $tiles = [
        ['eyebrow' => 'Manage',      'title' => 'Users',        'body' => 'View all users and roles.',                'href' => '/admin/users'],
        ['eyebrow' => 'Audit',       'title' => 'Items',        'body' => 'Browse all pantry items.',                 'href' => '/admin/items'],
        ['eyebrow' => 'Content',     'title' => 'Recipes',      'body' => 'List and curate recipes.',                 'href' => '/admin/recipes'],
        ['eyebrow' => 'Create',      'title' => 'Add recipe',   'body' => 'Add a manual recipe entry.',               'href' => '/admin/recipes/create'],
        ['eyebrow' => 'Communicate', 'title' => 'Updates',      'body' => 'View announcements sent to users.',        'href' => '/admin/updates'],
        ['eyebrow' => 'Post',        'title' => 'New update',   'body' => 'Send a message to all users.',             'href' => '/admin/updates/create'],
    ];
    foreach ($tiles as $tile):
    ?>
        <a href="<?= htmlspecialchars($tile['href']) ?>" class="card block hover:border-border-strong transition group">
            <p class="eyebrow mb-2"><?= htmlspecialchars($tile['eyebrow']) ?></p>
            <h3 class="text-text-heading mb-1 group-hover:text-brand transition-colors"><?= htmlspecialchars($tile['title']) ?></h3>
            <p class="text-text-muted text-sm"><?= htmlspecialchars($tile['body']) ?></p>
        </a>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
