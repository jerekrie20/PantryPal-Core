<?php
/**
 * Recipes Search/Suggested View
 * Expects: $title, $query, $recipes (array), $error (string|null), $mode ('search'|'suggested'|'saved'|'ugc'|'browse_api'),
 *          $pantryKeywords (array|null), $pantrySelected (array|null), $pantryMode (string|null),
 *          $filters (array|null), $pagination (array|null)
 */

require_once VIEW_PATH . '/Components/ui_elements.php';
ob_start();

$hasApi = (getenv('SUGGESTIC_API_KEY') || !empty($_ENV['SUGGESTIC_API_KEY']))
       || (getenv('SPOONACULAR_API_KEY') || !empty($_ENV['SPOONACULAR_API_KEY']))
       || (getenv('FATSECRET_CLIENT_ID') || !empty($_ENV['FATSECRET_CLIENT_ID']));

// Tab detection
$_ugcMode      = (isset($_GET['ugc']) && (string)$_GET['ugc'] === '1') || (($mode ?? '') === 'ugc');
$_suggestMode  = (($mode ?? '') === 'suggested') || isset($_GET['browse']);
$_apiMode      = isset($_GET['api']) && !$_suggestMode && !$_ugcMode;
$_savedMode    = !$_ugcMode && !$_suggestMode && !$_apiMode;

$pantrySelectedList = isset($pantrySelected) && is_array($pantrySelected)
    ? $pantrySelected
    : (isset($_GET['pantry']) && is_array($_GET['pantry']) ? array_map('strval', $_GET['pantry']) : []);
$pmode = isset($pantryMode) ? $pantryMode : (isset($_GET['pantry_mode']) ? (string)$_GET['pantry_mode'] : 'all');
$pmode = $pmode === 'any' ? 'any' : 'all';
$pantryToggleOpen = !empty($pantrySelectedList) || !empty($_GET['pantry']);

$tabClasses = function (bool $active): string {
    return $active
        ? 'px-4 py-1.5 text-sm font-semibold rounded-md bg-bg-component text-text-heading shadow-sm'
        : 'px-4 py-1.5 text-sm font-semibold rounded-md text-text-muted hover:text-text-heading transition-colors';
};
?>

<?php ui_page_header(
    $title ?? 'Recipes',
    'Find something to cook, save what you love, and build a personal cookbook.',
    '<a href="/recipes/create" class="btn btn-cta btn-md">+ Add recipe</a>',
    'Recipes'
); ?>

<!-- Tabs -->
<div role="tablist" aria-label="Recipe collections" class="inline-flex bg-bg-subtle rounded-lg p-1 gap-1 mb-6">
    <a role="tab" aria-selected="<?= $_savedMode ? 'true' : 'false' ?>" href="/recipes" class="<?= $tabClasses($_savedMode) ?>">Saved</a>
    <a role="tab" aria-selected="<?= $_ugcMode ? 'true' : 'false' ?>" href="/recipes?ugc=1" class="<?= $tabClasses($_ugcMode) ?>">My recipes</a>
    <a role="tab" aria-selected="<?= ($_suggestMode || $_apiMode) ? 'true' : 'false' ?>" href="/recipes/suggested" class="<?= $tabClasses($_suggestMode || $_apiMode) ?>">Discover</a>
</div>

<!-- Search / filters card -->
<div class="card mb-6">
    <form action="/recipes" method="GET" class="space-y-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1 min-w-0">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                </svg>
                <input type="text" name="q" placeholder="Search recipes (e.g. chicken pasta)" value="<?= e($query ?? '') ?>" class="w-full pl-9" />
            </div>
            <button type="submit" name="api" value="1" class="btn btn-cta btn-md sm:w-auto w-full">Search the web</button>
            <button type="submit" name="ugc" value="1" class="btn btn-secondary btn-md sm:w-auto w-full">Search my recipes</button>
            <button type="button" id="toggle-pantry" class="btn btn-secondary btn-md sm:w-auto w-full">
                <?= $pantryToggleOpen ? 'Hide pantry' : 'Use my pantry' ?>
            </button>
        </div>

        <?php if (empty($mode) || $mode !== 'browse_api'): ?>
            <div class="flex items-center gap-3 text-sm">
                <label for="perPage" class="text-text-muted">Results per page</label>
                <select id="perPage" name="perPage" class="!py-1">
                    <?php $pp = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10; foreach ([10, 12, 18, 24, 30] as $n): ?>
                        <option value="<?= (int)$n ?>" <?= $pp === $n ? 'selected' : '' ?>><?= (int)$n ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Pantry selector -->
        <div id="pantry-area" class="<?= $pantryToggleOpen ? '' : 'hidden ' ?>rounded-lg p-4 bg-bg-subtle border border-border-default">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-text-heading font-semibold text-base">Pick from your pantry</h3>
                    <p class="text-xs text-text-muted">We'll find recipes that use what you select.</p>
                </div>
                <button type="button" id="close-pantry" class="btn btn-ghost btn-sm">Hide</button>
            </div>

            <?php if (!empty($pantryKeywords) && is_array($pantryKeywords)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mt-2">
                    <?php foreach ($pantryKeywords as $kw): if (!is_string($kw) || $kw === '') continue; ?>
                        <?php $isChecked = in_array($kw, $pantrySelectedList, true); ?>
                        <label class="flex items-center gap-2 text-sm cursor-pointer p-2 rounded hover:bg-bg-component">
                            <input type="checkbox" name="pantry[]" value="<?= e($kw) ?>" class="!w-4 !h-4" <?= $isChecked ? 'checked' : '' ?> />
                            <span class="truncate"><?= e(ucwords($kw)) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3 text-sm">
                        <span class="text-text-muted">Match</span>
                        <label class="inline-flex items-center gap-1.5">
                            <input type="radio" name="pantry_mode" value="all" class="!w-4 !h-4" <?= $pmode === 'all' ? 'checked' : '' ?> /> All
                        </label>
                        <label class="inline-flex items-center gap-1.5">
                            <input type="radio" name="pantry_mode" value="any" class="!w-4 !h-4" <?= $pmode === 'any' ? 'checked' : '' ?> /> Any
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="clear-pantry-selection" class="btn btn-ghost btn-sm">Clear</button>
                        <button type="submit" name="api" value="1" class="btn btn-cta btn-sm">Search with selected</button>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-sm text-text-muted">
                    Your pantry is empty. <a href="/items" class="font-semibold">Add items</a> to use this filter.
                </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($mode) && $mode === 'browse_api'): ?>
            <!-- Advanced filters -->
            <input type="hidden" name="browse" value="1" />
            <input type="hidden" name="api" value="1" />
            <details open class="rounded-lg border border-border-default">
                <summary class="cursor-pointer px-4 py-3 font-semibold text-sm flex items-center justify-between">
                    Advanced filters
                    <span class="text-text-muted text-xs">click to toggle</span>
                </summary>
                <div class="px-4 pb-4 pt-1 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Diet</label>
                        <select name="diet" class="w-full">
                            <?php $diet = $filters['diet'] ?? ''; ?>
                            <option value="">Any</option>
                            <?php foreach (['gluten free','ketogenic','vegetarian','vegan','pescetarian','paleo','primal','whole30'] as $d): ?>
                                <option value="<?= e($d) ?>" <?= $diet === $d ? 'selected' : '' ?>><?= e(ucwords($d)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Cuisine</label>
                        <select name="cuisine" class="w-full">
                            <?php $cuisine = $filters['cuisine'] ?? ''; ?>
                            <option value="">Any</option>
                            <?php foreach (['american','italian','mexican','indian','thai','chinese','japanese','french','mediterranean'] as $c): ?>
                                <option value="<?= e($c) ?>" <?= $cuisine === $c ? 'selected' : '' ?>><?= e(ucwords($c)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Course</label>
                        <select name="type" class="w-full">
                            <?php $type = $filters['type'] ?? ''; ?>
                            <option value="">Any</option>
                            <?php foreach (['main course','side dish','dessert','appetizer','salad','bread','breakfast','soup','beverage','snack'] as $t): ?>
                                <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e(ucwords($t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Intolerances</label>
                        <input type="text" name="intolerances" placeholder="comma-separated" value="<?= e(is_array($filters['intolerances'] ?? null) ? implode(',', $filters['intolerances']) : ($filters['intolerances'] ?? '')) ?>" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Max ready time (min)</label>
                        <input type="number" min="0" name="maxReadyTime" placeholder="30" value="<?= e((string)($filters['maxReadyTime'] ?? '')) ?>" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Sort by</label>
                        <select name="sort" class="w-full">
                            <?php $sort = $filters['sort'] ?? ''; ?>
                            <option value="">Default</option>
                            <?php foreach (['popularity','healthiness','time','price','random'] as $s): ?>
                                <option value="<?= e($s) ?>" <?= $sort === $s ? 'selected' : '' ?>><?= e(ucwords($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-text-muted mb-1">Per page</label>
                        <select name="perPage" class="w-full">
                            <?php $pp = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 12; foreach ([6, 12, 18, 24, 30] as $n): ?>
                                <option value="<?= (int)$n ?>" <?= $pp === $n ? 'selected' : '' ?>><?= (int)$n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sm:col-span-2 md:col-span-3">
                        <button type="submit" class="btn btn-cta btn-md">Apply filters</button>
                    </div>
                </div>
            </details>
        <?php endif; ?>

        <?php if (!empty($pantrySelectedList)): ?>
            <p class="text-xs text-text-muted">Using your pantry: <?= e(implode(', ', $pantrySelectedList)) ?></p>
        <?php elseif (!empty($mode) && $mode === 'suggested' && !empty($pantryKeywords) && is_array($pantryKeywords)): ?>
            <p class="text-xs text-text-muted">Using your pantry: <?= e(implode(', ', $pantryKeywords)) ?></p>
        <?php endif; ?>

        <?php if (!$hasApi): ?>
            <div class="alert-warning">
                <p class="text-sm font-semibold">No recipe API configured</p>
                <p class="text-sm">Set <code>SUGGESTIC_API_KEY</code> (preferred) or <code>SPOONACULAR_API_KEY</code> to enable live results.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-danger text-sm"><?= e($error) ?></div>
        <?php endif; ?>
    </form>
</div>

<?php
// Section heading by mode
$sectionHeading = null;
if (!empty($mode)) {
    $sectionHeading = match ($mode) {
        'saved'      => 'Saved recipes',
        'ugc'        => 'My recipes',
        'browse_api' => 'Discover',
        'suggested'  => 'Suggested for your pantry',
        'search'     => !empty($query) ? 'Results for "' . e($query) . '"' : null,
        default      => null,
    };
}
?>

<?php if (!empty($recipes) && is_array($recipes)): ?>
    <?php if ($sectionHeading): ?>
        <h2 class="text-text-heading mb-4"><?= $sectionHeading ?></h2>
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($recipes as $r): ?>
            <?php
            $img = (!empty($r['image']) && (preg_match('#^https?://#i', $r['image']) || str_starts_with($r['image'], '/')))
                ? $r['image']
                : ('https://placehold.co/400x240/FAF5EC/B45309?text=' . urlencode($r['title'] ?? 'Recipe'));
            $sessionUid = (int)($_SESSION['user_id'] ?? 0);
            $isAdmin    = !empty($_SESSION['is_admin']);
            $rOwner     = (int)($r['user_id'] ?? 0);
            $rManual    = ($r['api_source'] ?? null) === 'manual';
            $canEditCard = $rManual && ($isAdmin || ($sessionUid > 0 && $rOwner === $sessionUid));
            $prov = !empty($r['provider']) ? (string)$r['provider'] : (!empty($r['api_source']) ? (string)$r['api_source'] : 'fatsecret');
            ?>
            <article class="card-flush overflow-hidden flex flex-col">
                <div class="relative">
                    <img src="<?= e($img) ?>" alt="<?= e($r['title'] ?? 'Recipe') ?>" class="w-full h-44 object-cover" />
                    <?php if (!empty($r['usedIngredients'])): ?>
                        <span class="badge-success absolute top-3 left-3 shadow-sm">
                            <?= (int)count($r['usedIngredients']) ?> from your pantry
                        </span>
                    <?php endif; ?>
                </div>
                <div class="p-4 flex-1 flex flex-col">
                    <h3 class="font-semibold text-text-heading text-lg leading-snug line-clamp-2"><?= e($r['title'] ?? 'Recipe') ?></h3>

                    <?php if (!empty($r['usedIngredients'])): ?>
                        <p class="mt-2 text-xs text-text-muted line-clamp-1">
                            <span class="font-semibold text-text-base">Uses:</span> <?= e(implode(', ', array_slice($r['usedIngredients'], 0, 6))) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($r['missedIngredients'])): ?>
                        <p class="mt-1 text-xs text-text-muted line-clamp-1">
                            <span class="font-semibold text-text-base">Missing:</span> <?= e(implode(', ', array_slice($r['missedIngredients'], 0, 6))) ?>
                        </p>
                    <?php endif; ?>

                    <div class="mt-4 pt-4 border-t border-border-default flex flex-wrap gap-2 items-center">
                        <?php if (!empty($r['db_id'])): ?>
                            <a href="/recipes/view/<?= (int)$r['db_id'] ?>" class="btn btn-cta btn-sm">View</a>
                        <?php endif; ?>
                        <?php if ($canEditCard && !empty($r['db_id'])): ?>
                            <a href="/recipes/<?= (int)$r['db_id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a>
                        <?php endif; ?>
                        <?php if (!empty($r['sourceUrl'])): ?>
                            <a href="<?= e($r['sourceUrl']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Source ↗</a>
                        <?php endif; ?>
                        <form action="/recipes/save" method="POST" class="inline ml-auto">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= e((string)($r['id'] ?? '')) ?>" />
                            <input type="hidden" name="title" value="<?= e($r['title'] ?? 'Recipe') ?>" />
                            <input type="hidden" name="image" value="<?= e($r['image'] ?? '') ?>" />
                            <input type="hidden" name="sourceUrl" value="<?= e($r['sourceUrl'] ?? '') ?>" />
                            <input type="hidden" name="payload" value='<?= e(json_encode($r)) ?>' />
                            <input type="hidden" name="provider" value="<?= e($prov) ?>" />
                            <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($pagination)):
        $qs = $_GET; unset($qs['page']);
        $isSuggested = (!empty($mode) && $mode === 'suggested');
        $base = ($isSuggested ? '/recipes/suggested' : '/recipes') . '?' . http_build_query($qs);
        $cur = (int)($pagination['currentPage'] ?? 1);
        $totalPages = (int)($pagination['totalPages'] ?? 0);
        $hasNext = $totalPages > 0
            ? ($cur < $totalPages)
            : (array_key_exists('hasNext', (array)$pagination) ? (bool)$pagination['hasNext'] : true);
    ?>
        <nav class="mt-8 flex items-center justify-center gap-3" aria-label="Pagination">
            <?php if ($cur > 1): ?>
                <a class="btn btn-secondary btn-sm" href="<?= e($base . '&page=' . ($cur - 1)) ?>">← Prev</a>
            <?php else: ?>
                <span class="btn btn-secondary btn-sm" aria-disabled="true" style="opacity:.4; pointer-events:none;">← Prev</span>
            <?php endif; ?>
            <span class="text-sm text-text-muted">Page <?= (int)$cur ?><?= $totalPages ? ' of ' . (int)$totalPages : '' ?></span>
            <?php if ($hasNext): ?>
                <a class="btn btn-secondary btn-sm" href="<?= e($base . '&page=' . ($cur + 1)) ?>">Next →</a>
            <?php else: ?>
                <span class="btn btn-secondary btn-sm" aria-disabled="true" style="opacity:.4; pointer-events:none;">Next →</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <?php
    // Empty states
    if ($_ugcMode || ($mode ?? '') === 'ugc') {
        ui_empty_state(
            'You haven\'t created any recipes yet',
            'Build your own cookbook. Add your favourite family recipes — title, ingredients, steps.',
            'Create your first recipe',
            '/recipes/create',
            '📖'
        );
    } elseif (!empty($mode) && $mode === 'search' && !empty($query)) {
        ui_empty_state(
            'No recipes for "' . e($query) . '"',
            'Try a broader term or switch to "Use my pantry" to find recipes that match what you already have.',
            null,
            null,
            '🔍'
        );
    } elseif (!empty($mode) && $mode === 'suggested') {
        ui_empty_state(
            'No suggestions yet',
            'Add more items to your pantry, or try the search above.',
            'Open my pantry',
            '/items',
            '🥕'
        );
    } elseif (!empty($mode) && $mode === 'saved') {
        ui_empty_state(
            'You haven\'t saved any recipes yet',
            'Search the web or browse suggestions, then tap Save on anything that catches your eye.',
            'Discover recipes',
            '/recipes?api=1&q=popular',
            '🍳'
        );
    } elseif (!empty($mode) && $mode === 'browse_api') {
        ui_empty_state(
            'No recipes match those filters',
            'Try loosening one of the filters — diet, cuisine, or max ready time.',
            null,
            null,
            '🍽️'
        );
    } else {
        ui_empty_state(
            'Start by searching for a recipe',
            'Type something above, or use your pantry to find dishes that fit what you already have.',
            null,
            null,
            '🍳'
        );
    }
    ?>
<?php endif; ?>

<script>
(function(){
  var form = document.querySelector('form[action="/recipes"]');
  if (!form) return;
  var toggleBtn = document.getElementById('toggle-pantry');
  var closeBtn  = document.getElementById('close-pantry');
  var clearBtn  = document.getElementById('clear-pantry-selection');
  var area      = document.getElementById('pantry-area');

  function setOpen(open) {
    if (!area || !toggleBtn) return;
    area.classList.toggle('hidden', !open);
    toggleBtn.textContent = open ? 'Hide pantry' : 'Use my pantry';
  }

  if (toggleBtn && area) toggleBtn.addEventListener('click', function(){ setOpen(area.classList.contains('hidden')); });
  if (closeBtn) closeBtn.addEventListener('click', function(){ setOpen(false); });
  if (clearBtn) clearBtn.addEventListener('click', function(){
    form.querySelectorAll('input[name="pantry[]"]:checked').forEach(function(cb){ cb.checked = false; });
  });

  // When pantry items are selected, populate q with them on submit so the controller has a hint.
  form.addEventListener('submit', function(){
    var checked = form.querySelectorAll('input[name="pantry[]"]:checked');
    if (!checked.length) return;
    var qInput = form.querySelector('input[name="q"]');
    if (qInput) qInput.value = Array.from(checked).map(function(cb){ return cb.value.trim(); }).filter(Boolean).join(', ');
  });
})();
</script>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
