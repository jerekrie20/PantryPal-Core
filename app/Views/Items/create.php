<?php
/**
 * Add New Pantry Item View
 */

require_once VIEW_PATH . '/Components/ui_elements.php';
require_once VIEW_PATH . '/Components/form_elements.php';

ob_start();

$selectedKind = $input['api_kind'] ?? 'ingredient';
?>

    <div class="max-w-2xl mx-auto">
        <?php ui_page_header(
                'Add to pantry',
                'Tell us what you bought — we\'ll auto-fill nutrition, category, and expiration when we recognize it.',
                null,
                'New item'
        ); ?>

        <div class="card p-6 md:p-8">
            <form action="/items" method="POST" class="space-y-6">
                <?php echo csrf_field(); ?>

                <?php form_input('name', 'Item name', 'text', [
                        'placeholder' => 'e.g. Organic milk',
                        'required' => true,
                        'value' => $input['name'] ?? '',
                        'error' => $errors['name'] ?? null,
                ]); ?>

                <!-- Type — segmented control -->
                <div>
                    <span id="api-kind-label" class="block text-sm font-medium mb-2">Type</span>
                    <div role="radiogroup" aria-labelledby="api-kind-label"
                         class="inline-flex w-full sm:w-auto bg-bg-subtle rounded-lg p-1 gap-1">
                        <?php
                        $kinds = [
                                ['key' => 'ingredient', 'label' => 'Ingredient', 'hint' => 'Generic (apples, flour)'],
                                ['key' => 'product', 'label' => 'Product', 'hint' => 'Branded (Ritz crackers)'],
                                ['key' => 'manual', 'label' => 'Manual', 'hint' => 'Skip the lookup'],
                        ];
                        foreach ($kinds as $k):
                            $checked = $selectedKind === $k['key'];
                            ?>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="api_kind" value="<?= htmlspecialchars($k['key']) ?>"
                                       class="sr-only peer" <?= $checked ? 'checked' : '' ?> />
                                <span class="block text-center text-sm font-semibold px-3 py-1.5 rounded-md transition-colors text-text-muted peer-checked:bg-bg-component peer-checked:text-text-heading peer-checked:shadow-sm">
                                <?= htmlspecialchars($k['label']) ?>
                            </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($errors['api_kind'])): ?>
                        <p class="text-danger text-sm mt-1"><?= htmlspecialchars($errors['api_kind']) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-text-muted mt-2" data-kind-hint>
                        <?php
                        $hintMap = [
                                'ingredient' => 'Best for generic items. We\'ll pull category and a stock image.',
                                'product' => 'Best for branded items. Add the brand below for accurate matching.',
                                'manual' => 'Save without calling any external API. Faster, no auto-fill.',
                        ];
                        echo htmlspecialchars($hintMap[$selectedKind] ?? $hintMap['ingredient']);
                        ?>
                    </p>
                </div>

                <?php form_input('brand', 'Brand', 'text', [
                        'placeholder' => 'Optional · e.g. Häagen-Dazs',
                        'required' => false,
                        'value' => $input['brand'] ?? '',
                        'error' => $errors['brand'] ?? null,
                ]); ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <?php form_input('quantity', 'Quantity', 'number', [
                            'value' => $input['quantity'] ?? '1',
                            'required' => true,
                            'step' => '0.01',
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
                        $selectedUnit = $input['unit'] ?? '';
                        ?>
                        <select id="unit" name="unit" class="w-full" required>
                            <?php foreach ($units as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>" <?= ($selectedUnit === $val ? 'selected' : '') ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                            <?php if ($selectedUnit && !array_key_exists($selectedUnit, $units)): ?>
                                <option value="<?= htmlspecialchars($selectedUnit) ?>"
                                        selected><?= htmlspecialchars($selectedUnit) ?> (custom)
                                </option>
                            <?php endif; ?>
                        </select>
                        <?php if (!empty($errors['unit'])): ?>
                            <p class="text-danger text-sm mt-1"><?= htmlspecialchars($errors['unit']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <?php form_input('purchase_date', 'Purchase date', 'date', [
                            'value' => $input['purchase_date'] ?? '',
                            'error' => $errors['purchase_date'] ?? null,
                            'required' => true,
                    ]); ?>
                    <?php form_input('expiration_date', 'Expiration date', 'date', [
                            'value' => $input['expiration_date'] ?? '',
                            'error' => $errors['expiration_date'] ?? null,
                            'required' => true,
                    ]); ?>
                </div>

                <div class="pt-6 border-t border-border-default flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <a href="/dashboard" class="btn btn-ghost btn-md w-full sm:w-auto">Cancel</a>
                    <button type="submit" class="btn btn-cta btn-md w-full sm:w-auto">Find &amp; add</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var hints = {
                ingredient: 'Best for generic items. We\'ll pull category and a stock image.',
                product: 'Best for branded items. Add the brand below for accurate matching.',
                manual: 'Save without calling any external API. Faster, no auto-fill.'
            };
            var hintEl = document.querySelector('[data-kind-hint]');
            document.querySelectorAll('input[name="api_kind"]').forEach(function (r) {
                r.addEventListener('change', function () {
                    if (hintEl && hints[r.value]) hintEl.textContent = hints[r.value];
                });
            });
        })();
    </script>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
