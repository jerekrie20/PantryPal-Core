<?php
// Ingredient detail page (uses global Users layout) — robust and aligned with reference.
// Expects $item structured by IngredientsController::show
ob_start();

// Safe escape helper (like show_ref.php)
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

// Determine hero image (placeholder if missing)
$imageSrc = null;
if (!empty($item['image']) && preg_match('#^https?://#i', $item['image'])) {
    $imageSrc = $item['image'];
} else {
    $placeholderText = !empty($item['name']) ? $item['name'] : 'Ingredient';
    $imageSrc = 'https://placehold.co/240x240/E8F5E9/36454F?text=' . urlencode($placeholderText);
}

?>

<main class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4"><?php echo e($title ?? 'Ingredient Details'); ?></h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <!-- Header Card -->
            <div class="card">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row items-start gap-4">
                        <img src="<?php echo e($imageSrc); ?>"
                             alt="Image of <?php echo e($item['name'] ?? 'Ingredient'); ?>"
                             class="w-24 h-24 sm:w-32 sm:h-32 object-cover rounded-lg bg-gray-100 flex-shrink-0">
                        <div class="min-w-0">
                            <h2 class="text-xl font-semibold"><?php echo e($item['name'] ?? 'Ingredient'); ?></h2>
                            <p class="text-sm text-gray-600"><?php echo e($item['category'] ?? ''); ?></p>
                            <?php if (!empty($item['brand'])): ?>
                                <p class="text-sm text-gray-700">Brand: <?php echo e($item['brand']); ?></p>
                            <?php endif; ?>
                            <span class="badge <?php echo e($item['badge_class'] ?? 'badge-neutral'); ?> mt-2 inline-block"><?php echo e($item['status'] ?? ''); ?></span>
                            <div class="mt-3 text-sm text-gray-700">
                                <p>
                                    Quantity: <?php echo e((string)($item['quantity'] ?? '')); ?> <?php echo e($item['unit'] ?? ''); ?></p>
                                <?php if (!empty($item['purchase_date'])): ?>
                                    <p>Purchased: <?php echo e($item['purchase_date']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($item['expiration_date'])): ?>
                                    <p>Expires: <?php echo e($item['expiration_date']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nutrition Card -->
            <div class="card">
                <div class="card-header">Nutrition</div>
                <div class="card-body">
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
                        $vitD = $byName['Vitamin D']['amount'] ?? null;
                        $vitDUnit = $byName['Vitamin D']['unit'] ?? 'mcg';
                        $calcium = $byName['Calcium']['amount'] ?? null;
                        $calciumUnit = $byName['Calcium']['unit'] ?? 'mg';
                        $iron = $byName['Iron']['amount'] ?? null;
                        $ironUnit = $byName['Iron']['unit'] ?? 'mg';
                        $potassium = $byName['Potassium']['amount'] ?? null;
                        $potassiumUnit = $byName['Potassium']['unit'] ?? 'mg';
                        $breakdown = $nutri['caloricBreakdown'] ?? null;
                        ?>

                        <?php if ($servingText): ?>
                            <p class="text-sm text-gray-600 mb-2">Serving: <?php echo e($servingText); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($nutrientsList) && is_array($nutrientsList)): ?>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                <div>
                                    <span class="font-medium">Calories:</span> <?php echo $cal !== null ? e((string)round($cal)) . ' ' . e($calUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Total Fat:</span> <?php echo $fat !== null ? e((string)round($fat, 1)) . ' ' . e($fatUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Saturated Fat:</span> <?php echo $satFat !== null ? e((string)round($satFat, 1)) . ' ' . e($satFatUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Trans Fat:</span> <?php echo $transFat !== null ? e((string)round($transFat, 1)) . ' ' . e($transFatUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Cholesterol:</span> <?php echo $chol !== null ? e((string)round($chol)) . ' ' . e($cholUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Sodium:</span> <?php echo $sodium !== null ? e((string)round($sodium)) . ' ' . e($sodiumUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Total Carbs:</span> <?php echo $carbs !== null ? e((string)round($carbs, 1)) . ' ' . e($carbsUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Fiber:</span> <?php echo $fiber !== null ? e((string)round($fiber, 1)) . ' ' . e($fiberUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Total Sugars:</span> <?php echo $sugar !== null ? e((string)round($sugar, 1)) . ' ' . e($sugarUnit) : '—'; ?>
                                </div>
                                <div>
                                    <span class="font-medium">Protein:</span> <?php echo $protein !== null ? e((string)round($protein, 1)) . ' ' . e($proteinUnit) : '—'; ?>
                                </div>
                            </dl>

                            <?php if ($breakdown && (isset($breakdown['percentProtein']) || isset($breakdown['percentFat']) || isset($breakdown['percentCarbs']))): ?>
                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                                    <div><span class="text-gray-600">Protein</span>
                                        <div class="font-medium"><?php echo isset($breakdown['percentProtein']) ? e((string)round($breakdown['percentProtein'])) . '%' : '—'; ?></div>
                                    </div>
                                    <div><span class="text-gray-600">Fat</span>
                                        <div class="font-medium"><?php echo isset($breakdown['percentFat']) ? e((string)round($breakdown['percentFat'])) . '%' : '—'; ?></div>
                                    </div>
                                    <div><span class="text-gray-600">Carbs</span>
                                        <div class="font-medium"><?php echo isset($breakdown['percentCarbs']) ? e((string)round($breakdown['percentCarbs'])) . '%' : '—'; ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php
                            // More Nutrients: list additional nutrients not shown above
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
                                // Sort by amount desc and cap to 10
                                usort($more, function ($a, $b) {
                                    return ($b['amount'] <=> $a['amount']);
                                });
                                $more = array_slice($more, 0, 10);
                            }
                            ?>
                            <?php if (!empty($more)): ?>
                                <div class="mt-4">
                                    <h3 class="font-semibold mb-2 text-sm text-text-muted">More Nutrients</h3>
                                    <ul class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                        <?php foreach ($more as $mn): ?>
                                            <li><?php echo e($mn['name']); ?>
                                                : <?php echo e((string)round((float)$mn['amount'], 2)); ?> <?php echo e($mn['unit'] ?? ''); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <p class="text-sm text-gray-600">Nutrition details are unavailable.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-600">Nutrition details are unavailable.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ingredient Info (optional metadata) -->
            <?php if (!empty($item['nutrition_raw']) && is_array($item['nutrition_raw'])): ?>
                <?php $meta = $item['nutrition_raw']; ?>
                <?php
                // Extract common FDC-like metadata if present
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
                    <div class="card">
                        <div class="card-header">Ingredient Info</div>
                        <div class="card-body text-sm text-gray-700">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dl class="space-y-3">
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Brand</dt>
                                            <dd class="font-medium"><?php echo !empty($item['brand']) ? e($item['brand']) : '—'; ?></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Category</dt>
                                            <dd class="font-medium"><?php echo !empty($item['category']) ? e($item['category']) : '—'; ?></dd>
                                        </div>
                                        <?php if ($servSize): ?>
                                            <div class="flex justify-between">
                                            <dt class="text-text-muted">Serving Size</dt>
                                            <dd class="font-medium"><?php echo e($servSize); ?></dd>
                                            </div><?php endif; ?>
                                    </dl>
                                </div>
                                <div>
                                    <dl class="space-y-3">
                                        <?php if ($fdcId): ?>
                                            <div class="flex justify-between">
                                            <dt class="text-text-muted">FDC ID</dt>
                                            <dd class="font-medium"><?php echo e((string)$fdcId); ?></dd>
                                            </div><?php endif; ?>
                                        <?php if ($dataType): ?>
                                            <div class="flex justify-between">
                                            <dt class="text-text-muted">Data Type</dt>
                                            <dd class="font-medium"><?php echo e($dataType); ?></dd>
                                            </div><?php endif; ?>
                                        <?php if ($foodCategory): ?>
                                            <div class="flex justify-between">
                                            <dt class="text-text-muted">Food Category</dt>
                                            <dd class="font-medium"><?php echo e($foodCategory); ?></dd>
                                            </div><?php endif; ?>
                                        <?php if ($pubDate): ?>
                                            <div class="flex justify-between">
                                            <dt class="text-text-muted">Publication Date</dt>
                                            <dd class="font-medium"><?php echo e($pubDate); ?></dd></div><?php endif; ?>
                                    </dl>
                                </div>
                            </div>
                            <?php if ($desc): ?><p class="mt-3 text-sm"><?php echo e($desc); ?></p><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Product Info (if associated product data exists) -->
            <?php if (!empty($item['product_raw']) && is_array($item['product_raw'])): ?>
                <?php
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
                $offStores = $p['stores'] ?? null;
                $offAllergens = !empty($p['allergens']) ? $p['allergens'] : (function_exists('pretty_tags') ? pretty_tags($p['allergens_tags'] ?? []) : null);
                $offTraces = !empty($p['traces']) ? $p['traces'] : (function_exists('pretty_tags') ? pretty_tags($p['traces_tags'] ?? []) : null);
                ?>
                <div class="card">
                    <div class="card-header">Product Info</div>
                    <div class="card-body text-sm text-gray-700">
                        <?php if ($isOFF): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dl class="space-y-3">
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Product Name</dt>
                                            <dd class="font-medium"><?php echo e($offName ?? ($item['product_title'] ?? ($item['name'] ?? ''))); ?></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Brand</dt>
                                            <dd class="font-medium"><?php echo e($brand ?? '—'); ?></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">UPC / Code</dt>
                                            <dd class="font-medium"><?php echo !empty($upc) ? e($upc) : '—'; ?></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Quantity</dt>
                                            <dd class="font-medium"><?php echo !empty($offQuantity) ? e($offQuantity) : '—'; ?></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Serving Size</dt>
                                            <dd class="font-medium"><?php echo !empty($offServing) ? e($offServing) : '—'; ?></dd>
                                        </div>
                                    </dl>
                                </div>
                                <div>
                                    <?php if (!empty($offLabels)): ?>
                                        <div class="mb-2">
                                        <div class="text-text-muted text-sm mb-1">Labels</div>
                                        <div class="text-sm font-medium"><?php echo e($offLabels); ?></div>
                                        </div><?php endif; ?>
                                    <?php if (!empty($offAllergens)): ?>
                                        <div class="mb-2">
                                        <div class="text-text-muted text-sm mb-1">Allergens</div>
                                        <div class="text-sm font-medium"><?php echo e($offAllergens); ?></div>
                                        </div><?php endif; ?>
                                    <?php if (!empty($offTraces)): ?>
                                        <div class="mb-2">
                                        <div class="text-text-muted text-sm mb-1">Traces</div>
                                        <div class="text-sm font-medium"><?php echo e($offTraces); ?></div>
                                        </div><?php endif; ?>
                                    <?php if (!empty($offCategories)): ?>
                                        <div class="mb-2">
                                        <div class="text-text-muted text-sm mb-1">Categories</div>
                                        <div class="text-sm font-medium"><?php echo e($offCategories); ?></div>
                                        </div><?php endif; ?>
                                    <?php if (!empty($offCountries)): ?>
                                        <div class="mb-2">
                                        <div class="text-text-muted text-sm mb-1">Countries</div>
                                        <div class="text-sm font-medium"><?php echo e($offCountries); ?></div>
                                        </div><?php endif; ?>
                                    <?php if (!empty($offPackaging)): ?>
                                        <div class="mb-2">
                                        <div class="text-text-muted text-sm mb-1">Packaging</div>
                                        <div class="text-sm font-medium"><?php echo e($offPackaging); ?></div>
                                        </div><?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dl class="space-y-3">
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Brand</dt>
                                            <dd class="font-medium"><?php echo e($brand ?? '—'); ?></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">UPC</dt>
                                            <dd class="font-medium"><?php echo !empty($upc) ? e($upc) : '—'; ?></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Aisle</dt>
                                            <dd class="font-medium"><?php echo !empty($p['aisle']) ? e($p['aisle']) : '—'; ?></dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-text-muted">Price</dt>
                                            <dd class="font-medium"><?php echo isset($p['price']) ? ('$' . e(number_format((float)$p['price'], 2))) : '—'; ?></dd>
                                        </div>
                                    </dl>
                                </div>
                                <div>
                                    <?php if (!empty($p['badges']) && is_array($p['badges'])): ?>
                                        <div class="mb-2">
                                            <div class="text-text-muted text-sm mb-1">Badges</div>
                                            <div class="flex flex-wrap gap-2"><?php foreach ($p['badges'] as $b): ?>
                                                    <span class="badge badge-neutral"><?php echo e($b); ?></span><?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>


        </div>

        <aside class="space-y-6">
            <div class="card">
                <div class="card-header">Actions</div>
                <div class="card-body flex flex-col sm:flex-row gap-2">
                    <a class="btn btn-cta btn-sm" href="/items/<?php echo (int)($item['id'] ?? 0); ?>/edit">Edit</a>
                    <form action="/items/<?php echo (int)($item['id'] ?? 0); ?>/delete" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete &quot;<?php echo htmlspecialchars($item['name'] ?? 'this item'); ?>&quot;? This action cannot be undone.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>

            <!-- Recipes using this ingredient -->
            <div class="card">
                <div class="card-header">Recipes with this Ingredient</div>
                <div class="card-body">
                    <?php if (!empty($recipesList)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach ($recipesList as $r): ?>
                                <?php
                                $img = (!empty($r['image']) && preg_match('#^https?://#i', $r['image'])) ? $r['image'] : ('https://placehold.co/300x180/E8F5E9/36454F?text=' . urlencode($r['title'] ?? 'Recipe'));
                                ?>
                                <div class="card overflow-hidden">
                                    <img src="<?php echo e($img); ?>"
                                         alt="Image of <?php echo e($r['title'] ?? 'Recipe'); ?>"
                                         class="w-full h-28 object-cover"/>
                                    <div class="p-3">
                                        <div class="text-sm font-semibold mb-1"><?php echo e($r['title'] ?? 'Recipe'); ?></div>
                                        <div class="flex flex-col gap-2">
                                            <?php if (!empty($r['db_id'])): ?>
                                                <a href="/recipes/view/<?php echo (int)$r['db_id']; ?>"
                                                   class="btn btn-cta btn-sm">Details</a>
                                            <?php endif; ?>
                                            <?php if (!empty($r['sourceUrl'])): ?>
                                                <a href="<?php echo e($r['sourceUrl']); ?>" target="_blank"
                                                   rel="noopener" class="btn btn-subtle btn-xs">Source</a>
                                            <?php endif; ?>
                                            <form action="/recipes/save" method="POST" class="inline">
                                                <input type="hidden" name="id"
                                                       value="<?php echo e((string)($r['id'] ?? '')); ?>"/>
                                                <input type="hidden" name="title"
                                                       value="<?php echo e($r['title'] ?? 'Recipe'); ?>"/>
                                                <input type="hidden" name="image"
                                                       value="<?php echo e($r['image'] ?? ''); ?>"/>
                                                <input type="hidden" name="sourceUrl"
                                                       value="<?php echo e($r['sourceUrl'] ?? ''); ?>"/>
                                                <input type="hidden" name="payload"
                                                       value='<?php echo e(json_encode($r)); ?>'/>
                                                <?php
                                                $prov = 'spoonacular';
                                                if (!empty($r['provider'])) {
                                                    $prov = (string)$r['provider'];
                                                } elseif (!empty($r['api_source'])) {
                                                    $prov = (string)$r['api_source'];
                                                } elseif (!isset($r['id']) || !is_numeric($r['id'])) {
                                                    $prov = 'suggestic';
                                                }
                                                ?>
                                                <input type="hidden" name="provider" value="<?php echo e($prov); ?>"/>

                                                <button type="submit" class="btn btn-secondary btn-sm">Save</button>

                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-text-muted">No local recipes found yet.</p>
                    <?php endif; ?>
                    <?php if (!empty($item['name'])): ?>
                        <a href="/recipes?q=<?php echo urlencode($item['name']); ?>&api=1"
                           class="btn btn-cta btn-sm mt-4">Search Online for &quot;<?php echo e($item['name']); ?>
                            &quot;</a>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
?>
