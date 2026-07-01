<?php
/**
 * Confirm Item Selection View
 * @var array $choices         The list of potential matches (local/provider).
 * @var array $original_input  The user's original form submission.
 */
require_once VIEW_PATH . '/Components/ui_elements.php';
ob_start();

if (!function_exists('e')) { function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); } }

$confirm_action = $confirm_action ?? '/items/confirm';
$searchedName  = $original_input['name']  ?? '';
$searchedBrand = $original_input['brand'] ?? '';
$csrf = $_SESSION['csrf_token'] ?? '';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8 text-center">
        <p class="eyebrow mb-2">Confirm your item</p>
        <?php if (!empty($choices)): ?>
            <h1 class="text-text-heading">Which one is yours?</h1>
            <p class="text-text-muted mt-3">
                Matches found for "<strong><?= e($searchedName) ?></strong>"<?= $searchedBrand ? ' (brand: <strong>' . e($searchedBrand) . '</strong>)' : '' ?>. Pick the best fit.
            </p>
        <?php else: ?>
            <h1 class="text-text-heading">No matches found.</h1>
            <p class="text-text-muted mt-3">
                We couldn't find "<strong><?= e($searchedName) ?></strong>"<?= $searchedBrand ? ' (brand: <strong>' . e($searchedBrand) . '</strong>)' : '' ?> in any database. You can still save it manually below.
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($choices)): ?>
        <div class="space-y-3">
            <?php foreach ($choices as $choice): ?>
                <form action="<?= e($confirm_action) ?>" method="POST" class="card-flush p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4 hover:border-border-strong transition">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>" />
                    <?php foreach ($original_input as $key => $value): ?>
                        <input type="hidden" name="original_input[<?= e($key) ?>]" value="<?= e((string)$value) ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="api_id"        value="<?= e((string)($choice['api_id'] ?? '')) ?>">
                    <input type="hidden" name="api_kind"      value="<?= e($choice['type'] ?? 'ingredient') ?>">
                    <?php if (!empty($choice['ingredient_id'])): ?>
                        <input type="hidden" name="ingredient_id" value="<?= (int)$choice['ingredient_id'] ?>">
                    <?php endif; ?>
                    <?php if (!empty($choice['product_id'])): ?>
                        <input type="hidden" name="product_id" value="<?= (int)$choice['product_id'] ?>">
                    <?php endif; ?>
                    <input type="hidden" name="picked_source" value="<?= e($choice['source'] ?? 'provider') ?>">

                    <div class="flex items-center gap-4 min-w-0 flex-1">
                        <?php if (!empty($choice['image_url'])): ?>
                            <img src="<?= e($choice['image_url']) ?>" alt="" class="w-16 h-16 rounded-lg object-cover bg-bg-subtle shrink-0">
                        <?php else: ?>
                            <div class="w-16 h-16 rounded-lg bg-bg-subtle shrink-0 flex items-center justify-center text-2xl" aria-hidden="true">🥕</div>
                        <?php endif; ?>

                        <div class="min-w-0">
                            <p class="font-semibold text-text-heading truncate"><?= e($choice['name'] ?? '') ?></p>
                            <div class="mt-1 flex items-center flex-wrap gap-x-3 gap-y-1 text-xs text-text-muted">
                                <?php if (!empty($choice['brand'])): ?>
                                    <span>Brand: <span class="text-text-base font-medium"><?= e($choice['brand']) ?></span></span>
                                <?php endif; ?>
                                <?php if (!empty($choice['source'])): ?>
                                    <span>Source: <span class="text-text-base font-medium"><?= e(ucfirst($choice['source'])) ?></span></span>
                                <?php endif; ?>
                                <span class="badge-neutral"><?= e($choice['type'] ?? 'ingredient') ?></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-cta btn-sm shrink-0 w-full sm:w-auto">Select</button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Manual create fallback -->
    <div class="card-flush p-4 mt-6 flex flex-col sm:flex-row items-center justify-between gap-3 bg-bg-subtle border-dashed">
        <div>
            <p class="font-semibold text-text-heading"><?= !empty($choices) ? 'None of these match?' : 'Save it anyway' ?></p>
            <p class="text-sm text-text-muted">Skip the lookup and save using exactly what you typed.</p>
        </div>
        <form action="<?= e($confirm_action) ?>" method="POST" class="inline">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>" />
            <?php foreach ($original_input as $key => $value): ?>
                <input type="hidden" name="original_input[<?= e($key) ?>]" value="<?= e((string)$value) ?>">
            <?php endforeach; ?>
            <input type="hidden" name="api_id"        value="0">
            <input type="hidden" name="api_kind"      value="manual">
            <input type="hidden" name="picked_source" value="manual">
            <button type="submit" class="btn btn-secondary btn-md w-full sm:w-auto">Create from my input</button>
        </form>
    </div>

    <div class="text-center mt-6">
        <a href="/items/create" class="text-sm text-text-muted hover:text-text-heading">← Go back</a>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
