<?php
/**
 * User Dashboard View
 * Expects:
 *   $username string
 *   $pantry_stats array{total_items:int, expiring_soon:int, recipes_saved:int}
 *   $pantry_items array — recent items (already enriched: name, status, badge_class, category, image, expired, url)
 *   $updates array|null
 */

require_once VIEW_PATH . '/Components/ui_elements.php';

ob_start();

// Surface up to 3 items that are expired or expire within 3 days, for the Cook Tonight hero.
$urgent = [];
foreach ($pantry_items ?? [] as $row) {
    if (in_array($row['badge_class'] ?? '', ['badge-warning', 'badge-danger'], true)) {
        $urgent[] = $row;
        if (count($urgent) >= 3) break;
    }
}
$expiringCount = (int)($pantry_stats['expiring_soon'] ?? 0);

// Build the recipe-suggested URL with the urgent items pre-selected (works with the existing /recipes route).
$suggestedHref = '/recipes/suggested';
if (!empty($urgent)) {
    $params = [];
    foreach ($urgent as $u) {
        $params[] = 'pantry[]=' . urlencode($u['name']);
    }
    $params[] = 'pantry_mode=any';
    $suggestedHref = '/recipes?' . implode('&', $params);
}
?>

<!-- Greeting strip -->
<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
    <div>
        <p class="eyebrow mb-1"><?= htmlspecialchars(date('l, F j')) ?></p>
        <h1 class="text-text-heading">Hi <?= htmlspecialchars($username) ?>.</h1>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/items/create" class="btn btn-cta btn-md">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            <span>Add item</span>
        </a>
        <a href="/shopping-list" class="btn btn-secondary btn-md">Shopping list</a>
    </div>
</section>

<!-- Cook Tonight hero -->
<section class="card hero-bg mb-8" aria-labelledby="cook-tonight-heading">
    <div class="flex flex-col lg:flex-row gap-6 lg:items-end lg:justify-between">
        <div class="max-w-xl">
            <p class="eyebrow mb-2">Cook tonight</p>
            <h2 id="cook-tonight-heading" class="text-text-heading">
                <?php if ($expiringCount > 0): ?>
                    Use these before they spoil.
                <?php else: ?>
                    What can you make with what you have?
                <?php endif; ?>
            </h2>
            <p class="text-text-muted mt-3">
                <?php if ($expiringCount > 0): ?>
                    You have <?= $expiringCount ?> item<?= $expiringCount === 1 ? '' : 's' ?> expiring soon. We'll suggest recipes that use them first.
                <?php else: ?>
                    Nothing's about to spoil — nice. Get a recipe suggestion based on your full pantry.
                <?php endif; ?>
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($suggestedHref) ?>" class="btn btn-cta btn-md">
                    Find a recipe
                </a>
                <a href="/recipes" class="btn btn-secondary btn-md">Browse saved</a>
            </div>
        </div>

        <?php if (!empty($urgent)): ?>
            <div class="flex flex-col gap-2 min-w-0 lg:w-80">
                <p class="text-xs font-semibold uppercase tracking-wider text-text-muted">Cook these first</p>
                <?php foreach ($urgent as $u): ?>
                    <a href="<?= htmlspecialchars($u['url']) ?>" class="flex items-center gap-3 p-3 rounded-lg bg-bg-component border border-border-default hover:border-border-strong transition">
                        <span class="<?= htmlspecialchars($u['badge_class']) ?> shrink-0"><?= htmlspecialchars($u['status']) ?></span>
                        <span class="truncate font-medium text-text-base"><?= htmlspecialchars($u['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Stat strip -->
<section class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10" aria-label="Pantry statistics">
    <div class="card">
        <p class="text-sm font-semibold text-text-muted">Expiring soon</p>
        <p class="font-display text-4xl mt-2 <?= $expiringCount > 0 ? 'text-warning' : 'text-text-heading' ?>"><?= (int)($pantry_stats['expiring_soon'] ?? 0) ?></p>
        <p class="text-sm text-text-muted mt-1">In the next 3 days</p>
        <?php if ($expiringCount > 0): ?>
            <a href="/items" class="btn btn-subtle btn-sm mt-4 -ml-3">Review pantry →</a>
        <?php endif; ?>
    </div>
    <div class="card">
        <p class="text-sm font-semibold text-text-muted">Total items</p>
        <p class="font-display text-4xl mt-2 text-text-heading"><?= (int)($pantry_stats['total_items'] ?? 0) ?></p>
        <p class="text-sm text-text-muted mt-1">Tracked in your pantry</p>
        <a href="/items" class="btn btn-subtle btn-sm mt-4 -ml-3">Open pantry →</a>
    </div>
    <div class="card">
        <p class="text-sm font-semibold text-text-muted">Recipes saved</p>
        <p class="font-display text-4xl mt-2 text-text-heading"><?= (int)($pantry_stats['recipes_saved'] ?? 0) ?></p>
        <p class="text-sm text-text-muted mt-1">Bookmarked for later</p>
        <a href="/recipes" class="btn btn-subtle btn-sm mt-4 -ml-3">View recipes →</a>
    </div>
</section>

<!-- Recent pantry -->
<section aria-labelledby="pantry-list-heading" class="mb-10">
    <div class="flex items-end justify-between mb-4">
        <div>
            <h2 id="pantry-list-heading" class="text-text-heading">Recently added</h2>
            <p class="text-text-muted text-sm mt-1">The last few items you've tracked.</p>
        </div>
        <a href="/items" class="btn btn-ghost btn-sm">View all</a>
    </div>

    <?php if (empty($pantry_items)): ?>
        <?php ui_empty_state(
            'Your pantry is empty',
            'Scan a barcode or add your first item — we\'ll suggest recipes that use it before it spoils.',
            'Add your first item',
            '/items/create',
            '🛒'
        ); ?>
    <?php else: ?>
        <div class="card-flush">
            <ul class="divide-y divide-border-default">
                <?php foreach ($pantry_items as $item): ?>
                    <?php include VIEW_PATH . '/Components/pantry_item.php'; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</section>

<!-- Updates -->
<?php if (!empty($updates)): ?>
    <section id="updates" aria-label="Updates" class="mt-10">
        <h2 class="text-text-heading mb-4">Updates</h2>
        <div class="space-y-3">
            <?php foreach ($updates as $u): ?>
                <article class="card">
                    <div class="flex items-center justify-between mb-1 gap-3">
                        <h3 class="text-text-heading"><?= htmlspecialchars($u['title'] ?? 'Update') ?></h3>
                        <span class="text-xs text-text-muted shrink-0"><?= htmlspecialchars(substr((string)($u['created_at'] ?? ''), 0, 16)) ?></span>
                    </div>
                    <p class="text-text-base whitespace-pre-line"><?= nl2br(htmlspecialchars($u['message'] ?? '')) ?></p>
                    <?php if (!empty($u['author_username'])): ?>
                        <p class="mt-2 text-xs text-text-muted">Posted by <?= htmlspecialchars($u['author_username']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
