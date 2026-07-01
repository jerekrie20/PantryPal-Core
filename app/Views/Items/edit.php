<?php
/**
 * Edit Pantry Item View
 * Expects:
 * - $title string
 * - $item array with keys: id, quantity, unit, purchase_date, expiration_date, entered_name, entered_brand
 * - $display array with keys: name, category, image (for context header)
 * - $errors array
 */
require_once VIEW_PATH . '/Components/ui_elements.php';
require_once VIEW_PATH . '/Components/form_elements.php';

ob_start();

$item    = $item    ?? [];
$display = $display ?? [];
$errors  = $errors  ?? [];

if (!function_exists('e')) { function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); } }

$img = !empty($display['image']) && preg_match('#^https?://#i', $display['image'])
    ? $display['image']
    : ('https://placehold.co/120x120/FAF5EC/B45309?text=' . urlencode($display['name'] ?? 'Item'));
?>

<div class="max-w-2xl mx-auto">

    <!-- Context header -->
    <div class="card-flush p-4 mb-6 flex items-center gap-4">
        <img src="<?= e($img) ?>" alt="" class="w-16 h-16 rounded-lg object-cover bg-bg-subtle shrink-0">
        <div class="min-w-0">
            <p class="eyebrow">Editing</p>
            <h1 class="text-text-heading text-2xl truncate"><?= e($display['name'] ?? 'Item') ?></h1>
            <?php if (!empty($display['category'])): ?>
                <p class="text-sm text-text-muted truncate"><?= e($display['category']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <form action="/items/<?= (int)$item['id'] ?>" method="POST" class="space-y-6">
            <?php echo csrf_field(); ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert-danger text-sm"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php form_input('quantity', 'Quantity', 'number', [
                    'value' => $item['quantity'] ?? '1',
                    'step'  => '0.01',
                    'error' => $errors['quantity'] ?? null,
                ]); ?>

                <div>
                    <label for="unit" class="block text-sm font-medium mb-1">Unit</label>
                    <?php
                    $units = [
                        '' => 'Select unit',
                        'pcs' => 'pcs', 'g' => 'g', 'kg' => 'kg', 'mg' => 'mg',
                        'lb' => 'lb', 'oz' => 'oz', 'ml' => 'ml', 'l' => 'L',
                        'cup' => 'cup', 'tbsp' => 'tbsp', 'tsp' => 'tsp', 'pinch' => 'pinch',
                    ];
                    $selectedUnit = $item['unit'] ?? '';
                    ?>
                    <select id="unit" name="unit" class="w-full">
                        <?php foreach ($units as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= $selectedUnit === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                        <?php if ($selectedUnit && !array_key_exists($selectedUnit, $units)): ?>
                            <option value="<?= e($selectedUnit) ?>" selected><?= e($selectedUnit) ?> (custom)</option>
                        <?php endif; ?>
                    </select>
                    <?php if (!empty($errors['unit'])): ?>
                        <p class="text-danger text-sm mt-1"><?= e($errors['unit']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php form_input('purchase_date', 'Purchase date', 'date', [
                    'value' => $item['purchase_date'] ?? '',
                    'error' => $errors['purchase_date'] ?? null,
                ]); ?>
                <?php form_input('expiration_date', 'Expiration date', 'date', [
                    'value' => $item['expiration_date'] ?? '',
                    'error' => $errors['expiration_date'] ?? null,
                ]); ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php form_input('entered_name', 'Entered name', 'text', [
                    'value' => $item['entered_name'] ?? '',
                    'error' => $errors['entered_name'] ?? null,
                ]); ?>
                <?php form_input('entered_brand', 'Entered brand', 'text', [
                    'value' => $item['entered_brand'] ?? '',
                    'error' => $errors['entered_brand'] ?? null,
                ]); ?>
            </div>

            <!-- Carry display context through on failed validation -->
            <input type="hidden" name="display_name"     value="<?= e($display['name'] ?? 'Item') ?>">
            <input type="hidden" name="display_category" value="<?= e($display['category'] ?? '') ?>">
            <input type="hidden" name="display_image"    value="<?= e($display['image'] ?? '') ?>">

            <div class="pt-6 border-t border-border-default flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="/items/view/<?= (int)$item['id'] ?>" class="btn btn-ghost btn-md w-full sm:w-auto">Cancel</a>
                <button type="submit" class="btn btn-cta btn-md w-full sm:w-auto">Save changes</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
