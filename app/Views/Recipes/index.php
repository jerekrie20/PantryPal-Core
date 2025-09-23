<?php
/**
 * Recipes Search/Suggested View
 * Expects: $title, $query, $recipes (array), $error (string|null), $mode ('search'|'suggested'), optional $pantryKeywords
 */

ob_start();

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

$hasApi = (getenv('SUGGESTIC_API_KEY') || (!empty($_ENV['SUGGESTIC_API_KEY']))) || (getenv('SPOONACULAR_API_KEY') || (!empty($_ENV['SPOONACULAR_API_KEY'])));
?>

<section class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-text-heading"><?php echo e($title ?? 'Recipes'); ?></h1>
        <p class="text-text-muted mt-1">Find recipes by searching the web or using your pantry.</p>
    </div>
</section>

<div class="card p-4 md:p-6 mb-6">
    <form action="/recipes" method="GET" class="flex flex-col gap-3">
        <div class="flex flex-col sm:flex-row gap-3 sm:items-stretch">
            <input type="text" name="q" placeholder="Search recipes (e.g., chicken pasta)" value="<?php echo e($query ?? ''); ?>"
                   class="flex-1 min-w-0 border border-border-default rounded px-3 py-2 bg-surface-default" />
            <button type="submit" name="api" value="1" class="btn btn-cta btn-md sm:w-auto w-full">Web Search</button>
            <a href="/recipes/suggested" class="btn btn-secondary btn-md sm:w-auto w-full">Use My Pantry</a>
        </div>
        <?php if (empty($mode) || $mode !== 'browse_api'): ?>
            <div class="flex items-center gap-3">
                <label class="text-sm">Results</label>
                <select name="perPage" class="border border-border-default rounded px-2 py-1 bg-surface-default">
                    <?php $pp = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10; foreach ([10,12,18,24,30] as $n): ?>
                        <option value="<?php echo (int)$n; ?>" <?php echo ($pp===$n?'selected':''); ?>><?php echo (int)$n; ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="text-xs text-text-muted">Use pages to see more results.</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($mode) && $mode === 'browse_api'): ?>
            <input type="hidden" name="browse" value="1" />
            <input type="hidden" name="api" value="1" />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <select name="diet" class="border border-border-default rounded px-3 py-2 bg-surface-default">
                    <?php $diet = $filters['diet'] ?? ''; ?>
                    <option value="">Any Diet</option>
                    <?php foreach (['gluten free','ketogenic','vegetarian','vegan','pescetarian','paleo','primal','whole30'] as $d): ?>
                        <option value="<?php echo e($d); ?>" <?php echo ($diet===$d?'selected':''); ?>><?php echo e(ucwords($d)); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="cuisine" class="border border-border-default rounded px-3 py-2 bg-surface-default">
                    <?php $cuisine = $filters['cuisine'] ?? ''; ?>
                    <option value="">Any Cuisine</option>
                    <?php foreach (['american','italian','mexican','indian','thai','chinese','japanese','french','mediterranean'] as $c): ?>
                        <option value="<?php echo e($c); ?>" <?php echo ($cuisine===$c?'selected':''); ?>><?php echo e(ucwords($c)); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="type" class="border border-border-default rounded px-3 py-2 bg-surface-default">
                    <?php $type = $filters['type'] ?? ''; ?>
                    <option value="">Any Type</option>
                    <?php foreach (['main course','side dish','dessert','appetizer','salad','bread','breakfast','soup','beverage','snack'] as $t): ?>
                        <option value="<?php echo e($t); ?>" <?php echo ($type===$t?'selected':''); ?>><?php echo e(ucwords($t)); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="intolerances" placeholder="Intolerances (comma-separated)" value="<?php echo e(is_array($filters['intolerances'] ?? null) ? implode(',', $filters['intolerances']) : ($filters['intolerances'] ?? '')); ?>" class="border border-border-default rounded px-3 py-2 bg-surface-default" />
                <input type="number" min="0" name="maxReadyTime" placeholder="Max Ready Time (min)" value="<?php echo e((string)($filters['maxReadyTime'] ?? '')); ?>" class="border border-border-default rounded px-3 py-2 bg-surface-default" />
                <select name="sort" class="border border-border-default rounded px-3 py-2 bg-surface-default">
                    <?php $sort = $filters['sort'] ?? ''; ?>
                    <option value="">Sort: Default</option>
                    <?php foreach (['popularity','healthiness','time','price','random'] as $s): ?>
                        <option value="<?php echo e($s); ?>" <?php echo ($sort===$s?'selected':''); ?>><?php echo e(ucwords($s)); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="flex items-center gap-3">
                    <label class="text-sm">Per Page</label>
                    <select name="perPage" class="border border-border-default rounded px-3 py-1 bg-surface-default">
                        <?php $pp = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 12; foreach ([6,12,18,24,30] as $n): ?>
                            <option value="<?php echo (int)$n; ?>" <?php echo ($pp===$n?'selected':''); ?>><?php echo (int)$n; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="btn btn-cta btn-md">Apply Filters</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($mode) && $mode === 'suggested' && !empty($pantryKeywords) && is_array($pantryKeywords)): ?>
            <p class="text-xs text-text-muted">Using your pantry: <?php echo e(implode(', ', $pantryKeywords)); ?></p>
        <?php endif; ?>

        <?php if (!$hasApi): ?>
            <div class="mt-3 p-3 rounded bg-amber-50 text-amber-800 text-sm">
                No recipe API configured. Set SUGGESTIC_API_KEY (preferred) or SPOONACULAR_API_KEY to enable live results.
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="mt-3 p-3 rounded bg-red-50 text-red-700 text-sm"><?php echo e($error); ?></div>
        <?php endif; ?>
    </form>
</div>

<?php if (!empty($recipes) && is_array($recipes)): ?>
    <?php if (!empty($mode) && $mode === 'saved'): ?>
        <h2 class="text-xl font-semibold mb-3">Your Saved Recipes</h2>
    <?php elseif (!empty($mode) && $mode === 'browse_api'): ?>
        <h2 class="text-xl font-semibold mb-3">Browsing Live Results</h2>
    <?php endif; ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($recipes as $r): ?>
            <?php
            $img = (!empty($r['image']) && preg_match('#^https?://#i', $r['image']))
                ? $r['image']
                : ('https://placehold.co/400x240/E8F5E9/36454F?text=' . urlencode($r['title'] ?? 'Recipe'));
            ?>
            <div class="card overflow-hidden">
                <img src="<?php echo e($img); ?>" alt="Image of <?php echo e($r['title'] ?? 'Recipe'); ?>" class="w-full h-40 object-cover" />
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-1"><?php echo e($r['title'] ?? 'Recipe'); ?></h3>
                    <?php if (!empty($r['usedIngredients'])): ?>
                        <div class="mb-2 text-xs text-text-muted">Uses: <?php echo e(implode(', ', array_slice($r['usedIngredients'], 0, 6))); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($r['missedIngredients'])): ?>
                        <div class="mb-2 text-xs text-text-muted">Missing: <?php echo e(implode(', ', array_slice($r['missedIngredients'], 0, 6))); ?></div>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <?php if (!empty($r['db_id'])): ?>
                            <a href="/recipes/view/<?php echo (int)$r['db_id']; ?>" class="btn btn-cta btn-sm">Details</a>
                        <?php endif; ?>
                        <?php if (!empty($r['sourceUrl'])): ?>
                            <a href="<?php echo e($r['sourceUrl']); ?>" target="_blank" rel="noopener" class="btn btn-subtle btn-sm">Source</a>
                        <?php endif; ?>
                        <form action="/recipes/save" method="POST" class="inline">
                            <input type="hidden" name="id" value="<?php echo e((string)($r['id'] ?? '')); ?>" />
                            <input type="hidden" name="title" value="<?php echo e($r['title'] ?? 'Recipe'); ?>" />
                            <input type="hidden" name="image" value="<?php echo e($r['image'] ?? ''); ?>" />
                            <input type="hidden" name="sourceUrl" value="<?php echo e($r['sourceUrl'] ?? ''); ?>" />
                            <input type="hidden" name="payload" value='<?php echo e(json_encode($r)); ?>' />
                            <?php 
                                $prov = 'spoonacular';
                                if (!empty($r['provider'])) { $prov = (string)$r['provider']; }
                                elseif (!empty($r['api_source'])) { $prov = (string)$r['api_source']; }
                                elseif (!isset($r['id']) || !is_numeric($r['id'])) { $prov = 'suggestic'; }
                            ?>
                            <input type="hidden" name="provider" value="<?php echo e($prov); ?>" />
                            <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($pagination)): ?>
        <?php
        // Build base query string preserving filters
        $qs = $_GET; unset($qs['page']);
        $isSuggested = (!empty($mode) && $mode === 'suggested');
        $base = ($isSuggested ? '/recipes/suggested' : '/recipes') . '?' . http_build_query($qs);
        $cur = (int)($pagination['currentPage'] ?? 1);
        $totalPages = (int)($pagination['totalPages'] ?? 0);
        // Determine if we should show Next: if totalPages known, use it; else prefer provider hint hasNext; else assume more
        $hasNext = false;
        if ($totalPages > 0) {
            $hasNext = ($cur < $totalPages);
        } elseif (array_key_exists('hasNext', (array)$pagination)) {
            $hasNext = (bool)$pagination['hasNext'];
        } else {
            $hasNext = true;
        }
        ?>
        <div class="mt-6 flex items-center justify-center gap-2">
            <?php if ($cur > 1): ?>
                <a class="btn btn-subtle btn-sm" href="<?php echo e($base . '&page=' . ($cur-1)); ?>">Prev</a>
            <?php endif; ?>
            <span class="text-sm text-text-muted">Page <?php echo (int)$cur; ?><?php echo ($totalPages ? (' of ' . (int)$totalPages) : ''); ?></span>
            <?php if ($hasNext): ?>
                <a class="btn btn-subtle btn-sm" href="<?php echo e($base . '&page=' . ($cur+1)); ?>">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="text-center text-text-muted mt-8">
        <?php if (!empty($mode) && $mode === 'search' && !empty($query)): ?>
            No recipes found for "<?php echo e($query); ?>".
        <?php elseif (!empty($mode) && $mode === 'suggested'): ?>
            No suggestions yet. Try adding more items or using the search above.
        <?php elseif (!empty($mode) && $mode === 'saved'): ?>
            You haven't saved any recipes yet. Try a search, use your pantry, or click "Web Search".
        <?php elseif (!empty($mode) && $mode === 'browse_api'): ?>
            No recipes returned by API for these filters.
        <?php else: ?>
            Start by searching for a recipe or using your pantry.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/layouts/Users/layout.php';
