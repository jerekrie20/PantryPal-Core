<?php
/**
 * Item Details View
 *
 * Expects:
 * - $title string
 * - $item array: id, name, category, image, quantity, unit, purchase_date, expiration_date, status, badge_class
 */

ob_start();

// Determine image source
$imageSrc = null;
if (!empty($item['image']) && preg_match('#^https?://#i', $item['image'])) {
    $imageSrc = $item['image'];
} else {
    $placeholderText = !empty($item['name']) ? $item['name'] : 'Item';
    $imageSrc = 'https://placehold.co/240x240/E8F5E9/36454F?text=' . urlencode($placeholderText);
}
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-start gap-6 mb-8">
        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Image of <?php echo htmlspecialchars($item['name']); ?>" class="w-24 h-24 rounded-xl object-cover bg-bg-subtle flex-shrink-0">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-text-heading mb-2"><?php echo htmlspecialchars($item['name']); ?></h1>
            <div class="flex items-center gap-2">
                <span class="badge <?php echo htmlspecialchars($item['badge_class']); ?>"><?php echo htmlspecialchars($item['status']); ?></span>
                <span class="badge badge-neutral"><?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?></span>
            </div>
        </div>
        <div class="flex flex-col gap-2">
            <a href="/items/<?php echo (int)$item['id']; ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
            <form action="/items/<?php echo (int)$item['id']; ?>/delete" method="POST">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>

    <!-- Details Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="card p-6">
            <h2 class="text-xl font-semibold mb-4">Item Details</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-text-muted">Quantity</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars((string)($item['quantity'] ?? '')); ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-text-muted">Purchased</dt>
                    <dd class="font-medium"><?php echo $item['purchase_date'] ? htmlspecialchars($item['purchase_date']) : '—'; ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-text-muted">Expires</dt>
                    <dd class="font-medium"><?php echo $item['expiration_date'] ? htmlspecialchars($item['expiration_date']) : '—'; ?></dd>
                </div>
            </dl>
        </div>

        <!-- Nutrition Card -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold mb-4">Nutrition</h2>
            <?php if (!empty($item['nutrition'])): ?>
                <?php
                // Try to extract common nutrients when available
                $nutri = $item['nutrition'];
                // Spoonacular 'nutrition' may be an array with 'nutrients' list, or already normalized
                $nutrientsList = $nutri['nutrients'] ?? (isset($nutri[0]) ? $nutri : []);
                $byName = [];
                if (is_array($nutrientsList)) {
                    foreach ($nutrientsList as $n) {
                        if (isset($n['name'])) {
                            $byName[$n['name']] = $n;
                        }
                    }
                }
                $cal = $byName['Calories']['amount'] ?? null;
                $calUnit = $byName['Calories']['unit'] ?? 'kcal';
                $carbs = $byName['Carbohydrates']['amount'] ?? $byName['Carbohydrates, by difference']['amount'] ?? null;
                $carbsUnit = ($byName['Carbohydrates']['unit'] ?? $byName['Carbohydrates, by difference']['unit'] ?? 'g');
                $protein = $byName['Protein']['amount'] ?? null;
                $proteinUnit = $byName['Protein']['unit'] ?? 'g';
                $fat = $byName['Fat']['amount'] ?? $byName['Total lipid (fat)']['amount'] ?? null;
                $fatUnit = ($byName['Fat']['unit'] ?? $byName['Total lipid (fat)']['unit'] ?? 'g');
                ?>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-text-muted">Calories</dt>
                        <dd class="font-medium"><?php echo $cal !== null ? htmlspecialchars((string)round($cal)) . ' ' . htmlspecialchars($calUnit) : '—'; ?></dd>
                    </div>
                    <div>
                        <dt class="text-text-muted">Carbs</dt>
                        <dd class="font-medium"><?php echo $carbs !== null ? htmlspecialchars((string)round($carbs, 1)) . ' ' . htmlspecialchars($carbsUnit) : '—'; ?></dd>
                    </div>
                    <div>
                        <dt class="text-text-muted">Protein</dt>
                        <dd class="font-medium"><?php echo $protein !== null ? htmlspecialchars((string)round($protein, 1)) . ' ' . htmlspecialchars($proteinUnit) : '—'; ?></dd>
                    </div>
                    <div>
                        <dt class="text-text-muted">Fat</dt>
                        <dd class="font-medium"><?php echo $fat !== null ? htmlspecialchars((string)round($fat, 1)) . ' ' . htmlspecialchars($fatUnit) : '—'; ?></dd>
                    </div>
                </dl>

                <?php if (empty($nutrientsList)): ?>
                    <p class="text-text-muted mt-4">Additional nutrition data is available but in an unrecognized format.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-text-muted">Nutrition details are not available for this item.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recipes Finder Placeholder -->
    <div class="card p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold mb-2">Recipe Finder</h2>
                <p class="text-text-muted">Find recipes using <?php echo htmlspecialchars($item['name']); ?> and other pantry items. This feature is coming soon.</p>
            </div>
            <button class="btn btn-cta btn-md" disabled aria-disabled="true">Coming Soon</button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/layouts/Users/layout.php';
?>
