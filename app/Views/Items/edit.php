<?php
/**
 * Edit Pantry Item View
 * Expects:
 * - $title string
 * - $item array with keys: id, quantity, unit, purchase_date, expiration_date, entered_name, entered_brand
 * - $display array with keys: name, category, image (for context header)
 * - $errors array
 */

ob_start();

// Ensure variables exist
$item = $item ?? [];
$display = $display ?? [];
$errors = $errors ?? [];

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

$img = !empty($display['image']) && preg_match('#^https?://#i', $display['image'])
    ? $display['image']
    : ('https://placehold.co/120x120/E8F5E9/36454F?text=' . urlencode($display['name'] ?? 'Item'));
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-start gap-4 mb-6">
        <img src="<?php echo e($img); ?>" alt="Image of <?php echo e($display['name'] ?? 'Item'); ?>" class="w-16 h-16 rounded-lg object-cover bg-bg-subtle">
        <div>
            <h1 class="text-2xl font-bold"><?php echo e($display['name'] ?? 'Item'); ?></h1>
            <?php if (!empty($display['category'])): ?>
                <div class="text-sm text-text-muted"><?php echo e($display['category']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card p-6">
        <form action="/items/<?php echo (int)$item['id']; ?>" method="POST" class="space-y-6">
            <?php if (!empty($errors['general'])): ?>
                <div class="p-3 rounded bg-red-50 text-red-700 text-sm"><?php echo e($errors['general']); ?></div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="quantity" class="block text-sm font-medium mb-1">Quantity</label>
                    <input id="quantity" name="quantity" type="number" step="0.01" value="<?php echo e($item['quantity'] ?? '1'); ?>"
                           class="w-full border border-border-default rounded px-3 py-2 bg-surface-default">
                    <?php if (!empty($errors['quantity'])): ?><p class="text-red-600 text-sm mt-1"><?php echo e($errors['quantity']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="unit" class="block text-sm font-medium mb-1">Unit</label>
                    <?php
                    $units = [
                        '' => 'Select unit (optional)',
                        'pcs' => 'pcs',
                        'g' => 'g',
                        'kg' => 'kg',
                        'mg' => 'mg',
                        'lb' => 'lb',
                        'oz' => 'oz',
                        'ml' => 'ml',
                        'l' => 'L',
                        'cup' => 'cup',
                        'tbsp' => 'tbsp',
                        'tsp' => 'tsp',
                        'pinch' => 'pinch',
                    ];
                    $selectedUnit = $item['unit'] ?? '';
                    ?>
                    <select id="unit" name="unit" class="w-full border border-border-default rounded px-3 py-2 bg-surface-default">
                        <?php foreach ($units as $val => $label): ?>
                            <option value="<?php echo e($val); ?>" <?php echo ($selectedUnit === $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                        <?php if ($selectedUnit && !array_key_exists($selectedUnit, $units)): ?>
                            <option value="<?php echo e($selectedUnit); ?>" selected><?php echo e($selectedUnit); ?> (custom)</option>
                        <?php endif; ?>
                    </select>
                    <?php if (!empty($errors['unit'])): ?><p class="text-red-600 text-sm mt-1"><?php echo e($errors['unit']); ?></p><?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="purchase_date" class="block text-sm font-medium mb-1">Purchase Date</label>
                    <input id="purchase_date" name="purchase_date" type="date" value="<?php echo e($item['purchase_date'] ?? ''); ?>"
                           class="w-full border border-border-default rounded px-3 py-2 bg-surface-default">
                </div>
                <div>
                    <label for="expiration_date" class="block text-sm font-medium mb-1">Expiration Date</label>
                    <input id="expiration_date" name="expiration_date" type="date" value="<?php echo e($item['expiration_date'] ?? ''); ?>"
                           class="w-full border border-border-default rounded px-3 py-2 bg-surface-default">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="entered_name" class="block text-sm font-medium mb-1">Entered Name</label>
                    <input id="entered_name" name="entered_name" type="text" value="<?php echo e($item['entered_name'] ?? ''); ?>"
                           class="w-full border border-border-default rounded px-3 py-2 bg-surface-default">
                </div>
                <div>
                    <label for="entered_brand" class="block text-sm font-medium mb-1">Entered Brand</label>
                    <input id="entered_brand" name="entered_brand" type="text" value="<?php echo e($item['entered_brand'] ?? ''); ?>"
                           class="w-full border border-border-default rounded px-3 py-2 bg-surface-default">
                </div>
            </div>

            <!-- carry display context back on failed validation -->
            <input type="hidden" name="display_name" value="<?php echo e($display['name'] ?? 'Item'); ?>">
            <input type="hidden" name="display_category" value="<?php echo e($display['category'] ?? ''); ?>">
            <input type="hidden" name="display_image" value="<?php echo e($display['image'] ?? ''); ?>">

            <div class="pt-6 border-t border-border-default flex flex-col sm:flex-row-reverse gap-3">
                <button type="submit" class="btn btn-cta btn-md sm:w-auto w-full">Save Changes</button>
                <a href="/items/view/<?php echo (int)$item['id']; ?>" class="btn btn-secondary btn-md sm:w-auto w-full">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
?>