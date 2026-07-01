<?php
// Ingredient detail page (uses global Users layout).
// Expects $item structured by IngredientsController::show.
require_once VIEW_PATH . '/Components/ui_elements.php';
ob_start();

if (!function_exists('e')) {
    function e($v): string
    {
        if (is_array($v)) {
            $parts = [];
            foreach ($v as $k => $val) {
                if (is_array($val)) {
                    $parts[] = is_string($k) ? ($k . ': ' . json_encode($val)) : json_encode($val);
                } else {
                    $parts[] = is_string($k) ? ($k . ': ' . (string)($val ?? '')) : (string)($val ?? '');
                }
            }
            $v = implode(', ', $parts);
        } elseif ($v === null) {
            $v = '';
        }
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$imageSrc = !empty($item['image']) && preg_match('#^https?://#i', $item['image'])
    ? $item['image']
    : ('https://placehold.co/240x240/FAF5EC/B45309?text=' . urlencode($item['name'] ?? 'Ingredient'));

$itemId = (int)($item['id'] ?? 0);
$csrf   = $_SESSION['csrf_token'] ?? '';

$actions = '<a class="btn btn-cta btn-md" href="/items/' . $itemId . '/edit">Edit</a>'
    . '<form action="/items/' . $itemId . '/delete" method="POST" class="inline"'
    . ' onsubmit="return confirm(&quot;Delete &quot; + ' . json_encode($item['name'] ?? 'this item') . ' + &quot;? This cannot be undone.&quot;);">'
    . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '" />'
    . '<button type="submit" class="btn btn-danger btn-md">Delete</button></form>';
?>

<?php ui_page_header(
    $item['name'] ?? 'Ingredient',
    $item['category'] ?? null,
    $actions,
    'Ingredient'
); ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

        <!-- Header card -->
        <section class="card">
            <div class="flex flex-col sm:flex-row items-start gap-5">
                <img src="<?= e($imageSrc) ?>" alt="" class="w-28 h-28 sm:w-32 sm:h-32 object-cover rounded-lg bg-bg-subtle shrink-0">
                <div class="min-w-0 flex-1">
                    <h2 class="text-text-heading text-xl truncate"><?= e($item['name'] ?? 'Ingredient') ?></h2>
                    <?php if (!empty($item['brand'])): ?>
                        <p class="text-sm text-text-muted">Brand: <span class="text-text-base font-medium"><?= e($item['brand']) ?></span></p>
                    <?php endif; ?>
                    <span class="badge <?= e($item['badge_class'] ?? 'badge-neutral') ?> mt-3 inline-block"><?= e($item['status'] ?? '') ?></span>
                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div>
                            <dt class="text-text-muted text-xs uppercase tracking-wide">Quantity</dt>
                            <dd class="text-text-base font-medium mt-1"><?= e((string)($item['quantity'] ?? '—')) ?> <?= e($item['unit'] ?? '') ?></dd>
                        </div>
                        <?php if (!empty($item['purchase_date'])): ?>
                            <div>
                                <dt class="text-text-muted text-xs uppercase tracking-wide">Purchased</dt>
                                <dd class="text-text-base font-medium mt-1"><?= e($item['purchase_date']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($item['expiration_date'])): ?>
                            <div>
                                <dt class="text-text-muted text-xs uppercase tracking-wide">Expires</dt>
                                <dd class="text-text-base font-medium mt-1"><?= e($item['expiration_date']) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </section>

        <!-- Nutrition card -->
        <section class="card">
            <h3 class="text-text-heading mb-4">Nutrition</h3>
            <?php if (!empty($item['nutrition']) && is_array($item['nutrition'])): ?>
                <?php
                $nutri = $item['nutrition'];
                $nutrientsList = $nutri['nutrients'] ?? (isset($nutri[0]) ? $nutri : []);
                $byName = [];
                if (is_array($nutrientsList)) {
                    foreach ($nutrientsList as $n) {
                        if (isset($n['name'])) $byName[$n['name']] = $n;
                    }
                }
                $servingText = null;
                if (!empty($nutri['servings']) && is_array($nutri['servings'])) {
                    $srv = $nutri['servings'];
                    if (!empty($srv['original'])) $servingText = $srv['original'];
                    elseif (isset($srv['number'], $srv['size'], $srv['unit'])) $servingText = $srv['number'] . ' x ' . $srv['size'] . ' ' . $srv['unit'];
                    elseif (isset($srv['size'], $srv['unit'])) $servingText = $srv['size'] . ' ' . $srv['unit'];
                }
                if (!$servingText && isset($nutri['serving_size'])) $servingText = $nutri['serving_size'];
                if (!$servingText && isset($nutri['servingSize'])) $servingText = $nutri['servingSize'];
                if (!$servingText && isset($nutri['weightPerServing']['amount'], $nutri['weightPerServing']['unit'])) {
                    $servingText = $nutri['weightPerServing']['amount'] . ' ' . $nutri['weightPerServing']['unit'];
                }

                $cal = $byName['Calories']['amount'] ?? null;
                $calUnit = $byName['Calories']['unit'] ?? 'kcal';
                $carbs = $byName['Carbohydrates']['amount'] ?? ($byName['Carbohydrates, by difference']['amount'] ?? null);
                $carbsUnit = $byName['Carbohydrates']['unit'] ?? ($byName['Carbohydrates, by difference']['unit'] ?? 'g');
                $protein = $byName['Protein']['amount'] ?? null;
                $proteinUnit = $byName['Protein']['unit'] ?? 'g';
                $fat = $byName['Fat']['amount'] ?? ($byName['Total lipid (fat)']['amount'] ?? null);
                $fatUnit = $byName['Fat']['unit'] ?? ($byName['Total lipid (fat)']['unit'] ?? 'g');
                $satFat = $byName['Saturated Fat']['amount'] ?? null;
                $satFatUnit = $byName['Saturated Fat']['unit'] ?? 'g';
                $transFat = $byName['Trans Fat']['amount'] ?? null;
                $transFatUnit = $byName['Trans Fat']['unit'] ?? 'g';
                $chol = $byName['Cholesterol']['amount'] ?? null;
                $cholUnit = $byName['Cholesterol']['unit'] ?? 'mg';
                $sodium = $byName['Sodium']['amount'] ?? null;
                $sodiumUnit = $byName['Sodium']['unit'] ?? 'mg';
                $fiber = $byName['Fiber']['amount'] ?? ($byName['Dietary Fiber']['amount'] ?? null);
                $fiberUnit = $byName['Fiber']['unit'] ?? ($byName['Dietary Fiber']['unit'] ?? 'g');
                $sugar = $byName['Sugar']['amount'] ?? ($byName['Sugars, total']['amount'] ?? null);
                $sugarUnit = $byName['Sugar']['unit'] ?? ($byName['Sugars, total']['unit'] ?? 'g');
                $breakdown = $nutri['caloricBreakdown'] ?? null;
                ?>

                <?php if ($servingText): ?>
                    <p class="text-sm text-text-muted mb-3">Per serving: <span class="text-text-base font-medium"><?= e($servingText) ?></span></p>
                <?php endif; ?>

                <?php if (!empty($nutrientsList) && is_array($nutrientsList)): ?>
                    <dl class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 text-sm">
                        <div><dt class="text-text-muted text-xs">Calories</dt>      <dd class="font-medium"><?= $cal !== null ? e((string)round($cal)) . ' ' . e($calUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Total fat</dt>     <dd class="font-medium"><?= $fat !== null ? e((string)round($fat, 1)) . ' ' . e($fatUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Saturated</dt>     <dd class="font-medium"><?= $satFat !== null ? e((string)round($satFat, 1)) . ' ' . e($satFatUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Trans</dt>         <dd class="font-medium"><?= $transFat !== null ? e((string)round($transFat, 1)) . ' ' . e($transFatUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Cholesterol</dt>   <dd class="font-medium"><?= $chol !== null ? e((string)round($chol)) . ' ' . e($cholUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Sodium</dt>        <dd class="font-medium"><?= $sodium !== null ? e((string)round($sodium)) . ' ' . e($sodiumUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Carbs</dt>         <dd class="font-medium"><?= $carbs !== null ? e((string)round($carbs, 1)) . ' ' . e($carbsUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Fiber</dt>         <dd class="font-medium"><?= $fiber !== null ? e((string)round($fiber, 1)) . ' ' . e($fiberUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Sugars</dt>        <dd class="font-medium"><?= $sugar !== null ? e((string)round($sugar, 1)) . ' ' . e($sugarUnit) : '—' ?></dd></div>
                        <div><dt class="text-text-muted text-xs">Protein</dt>       <dd class="font-medium"><?= $protein !== null ? e((string)round($protein, 1)) . ' ' . e($proteinUnit) : '—' ?></dd></div>
                    </dl>

                    <?php if ($breakdown && (isset($breakdown['percentProtein']) || isset($breakdown['percentFat']) || isset($breakdown['percentCarbs']))): ?>
                        <div class="mt-5 pt-5 border-t border-border-default grid grid-cols-3 gap-3 text-sm">
                            <div class="text-center">
                                <p class="font-display text-2xl text-text-heading"><?= isset($breakdown['percentProtein']) ? e((string)round($breakdown['percentProtein'])) . '%' : '—' ?></p>
                                <p class="text-xs text-text-muted">Protein</p>
                            </div>
                            <div class="text-center">
                                <p class="font-display text-2xl text-text-heading"><?= isset($breakdown['percentFat']) ? e((string)round($breakdown['percentFat'])) . '%' : '—' ?></p>
                                <p class="text-xs text-text-muted">Fat</p>
                            </div>
                            <div class="text-center">
                                <p class="font-display text-2xl text-text-heading"><?= isset($breakdown['percentCarbs']) ? e((string)round($breakdown['percentCarbs'])) . '%' : '—' ?></p>
                                <p class="text-xs text-text-muted">Carbs</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    $exclude = [
                        'Calories', 'Carbohydrates', 'Carbohydrates, by difference', 'Protein', 'Fat', 'Total lipid (fat)',
                        'Saturated Fat', 'Trans Fat', 'Cholesterol', 'Sodium', 'Fiber', 'Dietary Fiber', 'Sugar', 'Sugars, total',
                        'Calcium', 'Iron', 'Potassium'
                    ];
                    $more = [];
                    if (is_array($nutrientsList)) {
                        foreach ($nutrientsList as $n) {
                            $nm = $n['name'] ?? null;
                            $amt = $n['amount'] ?? null;
                            $unitN = $n['unit'] ?? null;
                            if (!$nm || $amt === null) continue;
                            if (in_array($nm, $exclude, true)) continue;
                            $more[] = ['name' => $nm, 'amount' => $amt, 'unit' => $unitN];
                        }
                    }
                    if (!empty($more)) {
                        usort($more, fn($a, $b) => ($b['amount'] <=> $a['amount']));
                        $more = array_slice($more, 0, 10);
                    }
                    ?>
                    <?php if (!empty($more)): ?>
                        <details class="mt-5 pt-5 border-t border-border-default">
                            <summary class="cursor-pointer text-sm font-semibold text-text-heading">More nutrients</summary>
                            <ul class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm text-text-muted">
                                <?php foreach ($more as $mn): ?>
                                    <li><span class="text-text-base"><?= e($mn['name']) ?>:</span> <?= e((string)round((float)$mn['amount'], 2)) ?> <?= e($mn['unit'] ?? '') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-sm text-text-muted">Nutrition details are unavailable.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-sm text-text-muted">Nutrition details are unavailable.</p>
            <?php endif; ?>
        </section>

        <!-- Ingredient Info -->
        <?php if (!empty($item['nutrition_raw']) && is_array($item['nutrition_raw'])):
            $meta = $item['nutrition_raw'];
            $fdcId = $meta['fdcId'] ?? null;
            $dataType = $meta['dataType'] ?? null;
            $desc = $meta['description'] ?? ($meta['description_en'] ?? null);
            $pubDate = $meta['publicationDate'] ?? ($meta['publishedDate'] ?? null);
            $foodCategory = $meta['foodCategory'] ?? ($meta['category'] ?? null);
            $servSize = null;
            if (isset($meta['servingSize'], $meta['servingSizeUnit'])) {
                $servSize = $meta['servingSize'] . ' ' . $meta['servingSizeUnit'];
            } elseif (isset($meta['householdServingFullText'])) {
                $servSize = $meta['householdServingFullText'];
            } elseif (isset($meta['servings']['original'])) {
                $servSize = $meta['servings']['original'];
            }
        ?>
            <?php if ($fdcId || $dataType || $desc || $foodCategory || $pubDate || $servSize || !empty($item['brand'])): ?>
                <section class="card">
                    <h3 class="text-text-heading mb-4">Ingredient info</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-muted">Brand</dt>
                            <dd class="text-text-base font-medium text-right truncate"><?= !empty($item['brand']) ? e($item['brand']) : '—' ?></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-muted">Category</dt>
                            <dd class="text-text-base font-medium text-right truncate"><?= !empty($item['category']) ? e($item['category']) : '—' ?></dd>
                        </div>
                        <?php if ($servSize): ?>
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-muted">Serving size</dt>
                                <dd class="text-text-base font-medium text-right"><?= e($servSize) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($fdcId): ?>
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-muted">FDC ID</dt>
                                <dd class="text-text-base font-medium text-right"><?= e((string)$fdcId) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($dataType): ?>
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-muted">Data type</dt>
                                <dd class="text-text-base font-medium text-right"><?= e($dataType) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($foodCategory): ?>
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-muted">Food category</dt>
                                <dd class="text-text-base font-medium text-right"><?= e($foodCategory) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($pubDate): ?>
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-muted">Published</dt>
                                <dd class="text-text-base font-medium text-right"><?= e($pubDate) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                    <?php if ($desc): ?>
                        <p class="mt-4 text-sm text-text-base"><?= e($desc) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Product Info -->
        <?php if (!empty($item['product_raw']) && is_array($item['product_raw'])):
            $p = $item['product_raw'];
            $isOFF = isset($p['code']) || isset($p['_id']) || isset($p['nutriments']);
            $brand = $item['brand'] ?? ($p['brand'] ?? ($p['brands'] ?? null));
            $brand = is_string($brand) ? $brand : (is_array($brand) ? implode(', ', $brand) : null);
            $upc = $p['upc'] ?? ($p['code'] ?? null);
            $offName = $p['product_name_en'] ?? $p['product_name'] ?? null;
            $offQuantity = $p['quantity'] ?? (isset($p['product_quantity']) ? ($p['product_quantity'] . ' ' . ($p['product_quantity_unit'] ?? '')) : null);
            $offServing = $p['serving_size'] ?? (isset($p['serving_quantity'], $p['serving_quantity_unit']) ? ($p['serving_quantity'] . ' ' . $p['serving_quantity_unit']) : null);
            $offLabels = function_exists('pretty_tags') ? pretty_tags($p['labels_tags'] ?? []) : null;
            $offCategories = function_exists('pretty_tags') ? pretty_tags($p['categories_tags'] ?? []) : null;
            $offCountries = function_exists('pretty_tags') ? pretty_tags($p['countries_tags'] ?? []) : null;
            $offPackaging = function_exists('pretty_tags') ? pretty_tags($p['packaging_tags'] ?? []) : null;
            $offAllergens = !empty($p['allergens']) ? $p['allergens'] : (function_exists('pretty_tags') ? pretty_tags($p['allergens_tags'] ?? []) : null);
            $offTraces = !empty($p['traces']) ? $p['traces'] : (function_exists('pretty_tags') ? pretty_tags($p['traces_tags'] ?? []) : null);
        ?>
            <section class="card">
                <h3 class="text-text-heading mb-4">Product info</h3>
                <?php if ($isOFF): ?>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">Product name</dt><dd class="text-text-base font-medium text-right truncate"><?= e($offName ?? ($item['product_title'] ?? ($item['name'] ?? ''))) ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">Brand</dt><dd class="text-text-base font-medium text-right truncate"><?= e($brand ?? '—') ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">UPC / Code</dt><dd class="text-text-base font-medium text-right font-mono text-xs"><?= !empty($upc) ? e($upc) : '—' ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">Quantity</dt><dd class="text-text-base font-medium text-right"><?= !empty($offQuantity) ? e($offQuantity) : '—' ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">Serving size</dt><dd class="text-text-base font-medium text-right"><?= !empty($offServing) ? e($offServing) : '—' ?></dd></div>
                    </dl>
                    <?php foreach ([
                        'Labels' => $offLabels, 'Allergens' => $offAllergens, 'Traces' => $offTraces,
                        'Categories' => $offCategories, 'Countries' => $offCountries, 'Packaging' => $offPackaging,
                    ] as $label => $val): if (!empty($val)): ?>
                        <div class="mt-3 pt-3 border-t border-border-default">
                            <p class="text-xs text-text-muted uppercase tracking-wide mb-1"><?= e($label) ?></p>
                            <p class="text-sm text-text-base"><?= e($val) ?></p>
                        </div>
                    <?php endif; endforeach; ?>
                <?php else: ?>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">Brand</dt><dd class="text-text-base font-medium text-right truncate"><?= e($brand ?? '—') ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">UPC</dt><dd class="text-text-base font-medium text-right font-mono text-xs"><?= !empty($upc) ? e($upc) : '—' ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">Aisle</dt><dd class="text-text-base font-medium text-right"><?= !empty($p['aisle']) ? e($p['aisle']) : '—' ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">Price</dt><dd class="text-text-base font-medium text-right"><?= isset($p['price']) ? '$' . e(number_format((float)$p['price'], 2)) : '—' ?></dd></div>
                    </dl>
                    <?php if (!empty($p['badges']) && is_array($p['badges'])): ?>
                        <div class="mt-3 pt-3 border-t border-border-default">
                            <p class="text-xs text-text-muted uppercase tracking-wide mb-2">Badges</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($p['badges'] as $b): ?>
                                    <span class="badge-neutral"><?= e($b) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="space-y-6">
        <section class="card">
            <h3 class="text-text-heading mb-4">Recipes with this</h3>
            <?php if (!empty($recipesList)): ?>
                <div class="space-y-3">
                    <?php foreach ($recipesList as $r):
                        $img = (!empty($r['image']) && preg_match('#^https?://#i', $r['image']))
                            ? $r['image']
                            : ('https://placehold.co/300x180/FAF5EC/B45309?text=' . urlencode($r['title'] ?? 'Recipe'));
                    ?>
                        <article class="card-flush overflow-hidden">
                            <img src="<?= e($img) ?>" alt="" class="w-full h-24 object-cover" />
                            <div class="p-3">
                                <p class="text-sm font-semibold text-text-heading mb-2 line-clamp-2"><?= e($r['title'] ?? 'Recipe') ?></p>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php if (!empty($r['db_id'])): ?>
                                        <a href="/recipes/view/<?= (int)$r['db_id'] ?>" class="btn btn-cta btn-sm">View</a>
                                    <?php endif; ?>
                                    <?php if (!empty($r['sourceUrl'])): ?>
                                        <a href="<?= e($r['sourceUrl']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Source</a>
                                    <?php endif; ?>
                                    <form action="/recipes/save" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>" />
                                        <input type="hidden" name="id"        value="<?= e((string)($r['id'] ?? '')) ?>" />
                                        <input type="hidden" name="title"     value="<?= e($r['title'] ?? 'Recipe') ?>" />
                                        <input type="hidden" name="image"     value="<?= e($r['image'] ?? '') ?>" />
                                        <input type="hidden" name="sourceUrl" value="<?= e($r['sourceUrl'] ?? '') ?>" />
                                        <input type="hidden" name="payload"   value='<?= e(json_encode($r)) ?>' />
                                        <?php $prov = !empty($r['provider']) ? (string)$r['provider'] : (!empty($r['api_source']) ? (string)$r['api_source'] : 'fatsecret'); ?>
                                        <input type="hidden" name="provider"  value="<?= e($prov) ?>" />
                                        <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-text-muted">No recipes match yet.</p>
            <?php endif; ?>
            <?php if (!empty($item['name'])): ?>
                <a href="/recipes?q=<?= urlencode($item['name']) ?>&api=1" class="btn btn-secondary btn-sm mt-4 w-full">Search the web for "<?= e($item['name']) ?>"</a>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
