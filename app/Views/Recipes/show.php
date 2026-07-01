<?php
/**
 * Recipe Details View
 * Expects:
 * - $title string
 * - $recipe array with keys: db_id, title, image, sourceUrl, ingredients (array), steps (array),
 *   estimated_price (float|null), servings (int|null), api_source, nutrition_per_serving
 * - $isSaved bool
 * - $pantryIngredients array|null  Pantry keywords for match-highlighting
 */
require_once VIEW_PATH . '/Components/ui_elements.php';
ob_start();

if (!function_exists('e')) { function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); } }

$img = (!empty($recipe['image']) && (preg_match('#^https?://#i', $recipe['image']) || str_starts_with($recipe['image'], '/')))
    ? $recipe['image']
    : ('https://placehold.co/640x360/FAF5EC/B45309?text=' . urlencode($recipe['title'] ?? 'Recipe'));

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin       = !empty($_SESSION['is_admin']);
$recipeOwner   = (int)($recipe['user_id'] ?? 0);
$isManual      = ($recipe['api_source'] ?? null) === 'manual';
$canEdit       = $isManual && ($isAdmin || ($sessionUserId > 0 && $recipeOwner === $sessionUserId));
$canDelete     = $isAdmin || ($sessionUserId > 0 && $recipeOwner === $sessionUserId);
$dbId          = (int)($recipe['db_id'] ?? 0);
$csrf          = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');

// Build actions HTML for the page header
$actionsParts = [];
if ($canEdit && $dbId > 0) {
    $actionsParts[] = '<a href="/recipes/' . $dbId . '/edit" class="btn btn-ghost btn-md">Edit</a>';
}
if ($canDelete && $dbId > 0) {
    $actionsParts[] = '<form action="/recipes/' . $dbId . '/delete" method="POST" class="inline" onsubmit="return confirm(&quot;Permanently delete this recipe?&quot;);">'
        . '<input type="hidden" name="csrf_token" value="' . $csrf . '">'
        . '<button type="submit" class="btn btn-danger btn-md">Delete</button></form>';
}
if (!empty($isSaved)) {
    $actionsParts[] = '<form action="/recipes/unsave" method="POST" class="inline">'
        . '<input type="hidden" name="csrf_token" value="' . $csrf . '" />'
        . '<input type="hidden" name="recipe_id" value="' . $dbId . '" />'
        . '<button type="submit" class="btn btn-secondary btn-md">Unsave</button></form>';
} else {
    $prov = 'fatsecret';
    if (!empty($recipe['api_source'])) { $prov = (string)$recipe['api_source']; }
    elseif (!empty($recipe['provider'])) { $prov = (string)$recipe['provider']; }
    $actionsParts[] = '<form action="/recipes/save" method="POST" class="inline">'
        . '<input type="hidden" name="csrf_token" value="' . $csrf . '" />'
        . '<input type="hidden" name="id" value="' . e((string)($recipe['id'] ?? '')) . '" />'
        . '<input type="hidden" name="title" value="' . e($recipe['title'] ?? 'Recipe') . '" />'
        . '<input type="hidden" name="image" value="' . e($recipe['image'] ?? '') . '" />'
        . '<input type="hidden" name="sourceUrl" value="' . e($recipe['sourceUrl'] ?? '') . '" />'
        . '<input type="hidden" name="payload" value=\'' . e(json_encode($recipe)) . '\' />'
        . '<input type="hidden" name="provider" value="' . e($prov) . '" />'
        . '<button type="submit" class="btn btn-cta btn-md">Save recipe</button></form>';
}
$actionsHtml = implode('', $actionsParts);

$subtitleParts = [];
if (!empty($recipe['servings'])) $subtitleParts[] = (int)$recipe['servings'] . ' serving' . ((int)$recipe['servings'] === 1 ? '' : 's');
if (!empty($recipe['api_source'])) $subtitleParts[] = 'Source: ' . $recipe['api_source'];
$subtitle = $subtitleParts ? implode(' · ', $subtitleParts) : null;
?>

<?php ui_page_header(
    $recipe['title'] ?? 'Recipe',
    $subtitle,
    $actionsHtml,
    'Recipe'
); ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

        <!-- Hero image -->
        <div class="card-flush overflow-hidden">
            <img src="<?= e($img) ?>" alt="<?= e($recipe['title'] ?? 'Recipe') ?>" class="w-full h-64 sm:h-80 object-cover" />
        </div>

        <!-- Ingredients -->
        <section class="card">
            <h3 class="text-text-heading mb-4">Ingredients</h3>
            <?php if (!empty($recipe['ingredients']) && is_array($recipe['ingredients'])):
                $pantrySet   = $pantryIngredients ?? [];
                $hasPantry   = !empty($pantrySet);
                $missingIngs = [];
            ?>
                <?php if ($hasPantry): ?>
                    <p class="eyebrow text-accent-700 mb-3">
                        <svg class="w-3.5 h-3.5 inline-block -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        In your pantry
                    </p>
                <?php endif; ?>

                <ul class="space-y-2 text-sm">
                    <?php foreach ($recipe['ingredients'] as $ing):
                        $inPantry = false;
                        if ($hasPantry) {
                            $ingLower = strtolower((string)$ing);
                            foreach ($pantrySet as $pName) {
                                if ($pName !== '' && stripos($ingLower, $pName) !== false) {
                                    $inPantry = true;
                                    break;
                                }
                            }
                        }
                        if (!$inPantry) $missingIngs[] = (string)$ing;
                    ?>
                        <li class="flex items-start gap-2.5">
                            <?php if ($inPantry): ?>
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-accent-600);" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <?php else: ?>
                                <span class="w-4 h-4 mt-1 shrink-0 flex items-center justify-center" aria-hidden="true">
                                    <span class="w-1.5 h-1.5 rounded-full bg-border-strong"></span>
                                </span>
                            <?php endif; ?>
                            <span class="<?= $inPantry ? 'font-medium text-text-heading' : 'text-text-base' ?>"><?= e($ing) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!empty($missingIngs)): ?>
                    <form action="/shopping-list/add-from-recipe" method="POST" class="mt-5 pt-4 border-t border-border-default">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="recipe_id"    value="<?= (int)($recipe['db_id'] ?? 0) ?>">
                        <input type="hidden" name="recipe_title" value="<?= e($recipe['title'] ?? '') ?>">
                        <?php foreach ($missingIngs as $mi): ?>
                            <input type="hidden" name="ingredients[]" value="<?= e($mi) ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-cta btn-md w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 7h13L17 13M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                            </svg>
                            Add <?= count($missingIngs) ?> missing to shopping list
                        </button>
                    </form>
                <?php elseif ($hasPantry): ?>
                    <div class="alert-success text-sm mt-5">You have every ingredient. Get cooking.</div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-text-muted text-sm">Ingredients not listed.</p>
            <?php endif; ?>
        </section>

        <!-- Instructions -->
        <section class="card">
            <h3 class="text-text-heading mb-4">Instructions</h3>
            <?php if (!empty($recipe['steps']) && is_array($recipe['steps'])): ?>
                <ol class="space-y-4 text-sm text-text-base">
                    <?php foreach ($recipe['steps'] as $i => $s): ?>
                        <li class="flex gap-3">
                            <span class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center font-display text-sm" style="background: var(--color-brand-100); color: var(--color-brand-700); border: 1px solid var(--color-brand-200);"><?= (int)$i + 1 ?></span>
                            <span class="pt-1"><?= e($s) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="text-text-muted text-sm">No instructions available.</p>
            <?php endif; ?>
        </section>
    </div>

    <aside class="space-y-6">
        <?php if (!empty($recipe['nutrition_per_serving']) && is_array($recipe['nutrition_per_serving'])): ?>
            <section class="card">
                <h3 class="text-text-heading mb-4">Nutrition per serving</h3>
                <dl class="space-y-2 text-sm">
                    <?php
                    $order = ['Calories', 'Protein', 'Carbohydrates', 'Net Carbs', 'Fat', 'Saturated Fat', 'Trans Fat', 'Sugar', 'Fiber', 'Sodium', 'Cholesterol', 'Potassium', 'Calcium', 'Iron', 'Vitamin C', 'Vitamin A'];
                    $per = $recipe['nutrition_per_serving'];
                    $printed = [];
                    foreach ($order as $label) {
                        if (isset($per[$label])) {
                            $n = $per[$label];
                            echo '<div class="flex justify-between gap-3"><dt class="text-text-muted">' . e($label) . '</dt><dd class="text-text-base font-medium">' . e(number_format((float)$n['amount'], ($label === 'Calories' ? 0 : 1))) . ' ' . e($n['unit']) . '</dd></div>';
                            $printed[$label] = true;
                        }
                    }
                    foreach ($per as $label => $n) {
                        if (!isset($printed[$label])) {
                            echo '<div class="flex justify-between gap-3"><dt class="text-text-muted">' . e($label) . '</dt><dd class="text-text-base font-medium">' . e(number_format((float)$n['amount'], 1)) . ' ' . e($n['unit']) . '</dd></div>';
                        }
                    }
                    ?>
                </dl>
            </section>
        <?php endif; ?>

        <section class="card">
            <h3 class="text-text-heading mb-4">Extras</h3>
            <?php if (!empty($recipe['estimated_price'])): ?>
                <p class="text-sm text-text-base">Estimated cost per serving: <span class="font-semibold">$<?= number_format((float)$recipe['estimated_price'], 2) ?></span></p>
            <?php else: ?>
                <p class="text-sm text-text-muted">Price estimate unavailable.</p>
            <?php endif; ?>
            <?php if (!empty($recipe['sourceUrl'])): ?>
                <a href="<?= e($recipe['sourceUrl']) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm mt-4 w-full">Open source ↗</a>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
