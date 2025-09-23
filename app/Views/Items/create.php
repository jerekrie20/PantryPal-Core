<?php
/**
 * Add New Pantry Item View
 */

// --- Templating Logic ---
ob_start();

// Include the reusable form components
require_once VIEW_PATH . '/components/form_elements.php';
?>

<div class="max-w-2xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-text-heading">Add a New Item</h1>
        <p class="text-text-muted mt-1">Fill out the details below to add an item to your pantry.</p>
    </div>

    <!-- Add Item Form -->
    <div class="card p-6 md:p-8">
        <form action="/items" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />

            <?php form_input('name', 'Item Name', 'text', [
                    'placeholder' => 'e.g., Organic Milk',
                    'required' => true,
                    'value' => $input['name'] ?? '',
                    'error' => $errors['name'] ?? null
            ]); ?>

            <!-- NEW: Brand (optional) -->
            <?php form_input('brand', 'Brand (optional)', 'text', [
                    'placeholder' => 'e.g., Häagen-Dazs',
                    'required' => false,
                    'value' => $input['brand'] ?? '',
                    'error' => $errors['brand'] ?? null
            ]); ?>

            <!-- NEW: Type selector (api_kind) -->
            <div>
                <label for="api_kind" class="block text-sm font-medium text-text-heading mb-1">Type</label>
                <select
                        id="api_kind"
                        name="api_kind"
                        class="w-full border border-border-default rounded px-3 py-2 bg-surface-default focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <?php $k = $input['api_kind'] ?? 'ingredient'; ?>
                    <option value="ingredient" <?= $k==='ingredient' ? 'selected' : '' ?>>Ingredient (generic)</option>
                    <option value="product"    <?= $k==='product'    ? 'selected' : '' ?>>Product (branded)</option>
                    <option value="manual"     <?= $k==='manual'     ? 'selected' : '' ?>>Manual (skip API)</option>
                </select>
                <?php if (!empty($errors['api_kind'])): ?>
                    <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['api_kind']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-text-muted mt-1">
                    Choose <strong>Ingredient</strong> for generic items (e.g., “apples”),
                    <strong>Product</strong> for specific brands (e.g., “Ritz Crackers”),
                    or <strong>Manual</strong> to save without calling an API.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php form_input('quantity', 'Quantity', 'number', [
                        'value' => $input['quantity'] ?? '1',
                        'required' => true,
                        'step' => '0.01', // Allows decimal quantities
                        'error' => $errors['quantity'] ?? null
                ]); ?>

                <div>
                    <label for="unit" class="block text-sm font-medium text-text-heading mb-1">Unit</label>
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
                    $selectedUnit = $input['unit'] ?? '';
                    ?>
                    <select id="unit" name="unit" class="w-full border border-border-default rounded px-3 py-2 bg-surface-default focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($units as $val => $label): ?>
                            <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($selectedUnit === $val ? 'selected' : ''); ?>><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                        <?php if ($selectedUnit && !array_key_exists($selectedUnit, $units)): ?>
                            <option value="<?php echo htmlspecialchars($selectedUnit); ?>" selected><?php echo htmlspecialchars($selectedUnit); ?> (custom)</option>
                        <?php endif; ?>
                    </select>
                    <?php if (!empty($errors['unit'])): ?>
                        <p class="text-red-600 text-sm mt-1"><?php echo htmlspecialchars($errors['unit']); ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-text-muted mt-1">Pick a unit or leave blank.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php form_input('purchase_date', 'Purchase Date', 'date', [
                        'required' => false,
                        'value' => $input['purchase_date'] ?? '',
                        'error' => $errors['purchase_date'] ?? null
                ]); ?>

                <?php form_input('expiration_date', 'Expiration Date', 'date', [
                        'required' => false,
                        'value' => $input['expiration_date'] ?? '',
                        'error' => $errors['expiration_date'] ?? null
                ]); ?>
            </div>

            <div class="pt-6 border-t border-border-default flex flex-col sm:flex-row-reverse gap-3">
                <?php form_button('Find & Add', 'cta', ['size' => 'md', 'fullWidth' => true, 'class' => 'sm:w-auto']); ?>
                <a href="/dashboard" class="btn btn-secondary btn-md w-full sm:w-auto flex justify-center">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
?>
