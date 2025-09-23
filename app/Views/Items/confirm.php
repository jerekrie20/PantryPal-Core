<?php
/**
 * Confirm Item Selection View
 * @var array $choices         The list of potential matches (local/provider).
 * @var array $original_input  The user's original form submission.
 */
ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-text-heading">Confirm Your Item</h1>
        <?php if (!empty($choices)): ?>
            <p class="text-text-muted mt-2">
                We found matches for "<strong><?= htmlspecialchars($original_input['name'] ?? '') ?></strong>"
                <?php if (!empty($original_input['brand'])): ?>
                    (brand: <strong><?= htmlspecialchars($original_input['brand']) ?></strong>)
                <?php endif; ?>.
                Please select the correct one.
            </p>
        <?php else: ?>
            <p class="text-text-muted mt-2">
                No matches found for "<strong><?= htmlspecialchars($original_input['name'] ?? '') ?></strong>"
                <?php if (!empty($original_input['brand'])): ?>
                    (brand: <strong><?= htmlspecialchars($original_input['brand']) ?></strong>)
                <?php endif; ?>.
                You can create a new item from your input below.
            </p>
        <?php endif; ?>
    </div>

    <?php if (empty($choices)): ?>
        <div class="card p-6 text-center">
            <p class="text-text-muted">No matches found. You can create a new item from your input below.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($choices as $choice): ?>
                <div class="card p-4">
                    <?php $confirm_action = $confirm_action ?? '/items/confirm'; ?>
                    <form action="<?= htmlspecialchars($confirm_action) ?>" method="POST" class="flex items-center justify-between gap-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                        <!-- Pass original user input through hidden fields -->
                        <?php foreach ($original_input as $key => $value): ?>
                            <input type="hidden" name="original_input[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars((string)$value) ?>">
                        <?php endforeach; ?>

                        <!-- Selected result identifiers -->
                        <input type="hidden" name="api_id" value="<?= htmlspecialchars((string)($choice['api_id'] ?? '')) ?>">
                        <input type="hidden" name="api_kind" value="<?= htmlspecialchars($choice['type'] ?? 'ingredient') ?>">

                        <!-- NEW: carry source/local ids if controller uses them -->
                        <?php if (!empty($choice['ingredient_id'])): ?>
                            <input type="hidden" name="ingredient_id" value="<?= (int)$choice['ingredient_id'] ?>">
                        <?php endif; ?>
                        <?php if (!empty($choice['product_id'])): ?>
                            <input type="hidden" name="product_id" value="<?= (int)$choice['product_id'] ?>">
                        <?php endif; ?>
                        <input type="hidden" name="picked_source" value="<?= htmlspecialchars($choice['source'] ?? 'provider') ?>">

                        <div class="flex items-center gap-4 min-w-0">
                            <?php if (!empty($choice['image_url'])): ?>
                                <img src="<?= htmlspecialchars($choice['image_url']) ?>"
                                     alt=""
                                     class="w-16 h-16 rounded-lg object-cover bg-bg-subtle flex-shrink-0">
                            <?php endif; ?>

                            <div class="min-w-0">
                                <p class="font-semibold text-text-base truncate">
                                    <?= htmlspecialchars($choice['name'] ?? '') ?>
                                </p>
                                <div class="mt-1 flex items-center gap-2 text-sm text-text-muted">
                                    <?php if (!empty($choice['brand'])): ?>
                                        <span>Brand: <?= htmlspecialchars($choice['brand']) ?></span>
                                        <span aria-hidden="true">•</span>
                                    <?php endif; ?>
                                    <?php if (!empty($choice['source'])): ?>
                                        <span>Source: <?= htmlspecialchars(ucfirst($choice['source'])) ?></span>
                                        <span aria-hidden="true">•</span>
                                    <?php endif; ?>
                                    <span>
                                        Type:
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-bg-subtle text-xs font-medium">
                                            <?= htmlspecialchars($choice['type'] ?? 'ingredient') ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-cta btn-md flex-shrink-0">Select</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- NEW: Manual create fallback -->
    <div class="text-center mt-8">
        <?php $confirm_action = $confirm_action ?? '/items/confirm'; ?>
        <form action="<?= htmlspecialchars($confirm_action) ?>" method="POST" class="inline">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            <?php foreach ($original_input as $key => $value): ?>
                <input type="hidden" name="original_input[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars((string)$value) ?>">
            <?php endforeach; ?>
            <input type="hidden" name="api_id" value="0">
            <input type="hidden" name="api_kind" value="manual">
            <input type="hidden" name="picked_source" value="manual">
            <button type="submit" class="btn btn-secondary btn-md">Create New from My Input</button>
        </form>
        <a href="/items/create" class="ml-4 text-sm font-medium">Go back</a>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
?>
