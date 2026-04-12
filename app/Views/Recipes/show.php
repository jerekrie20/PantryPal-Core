<?php
/**
 * Recipe Details View
 * Expects:
 * - $title string
 * - $recipe array with keys: db_id, title, image, sourceUrl, ingredients (array), steps (array), estimated_price (float|null), servings (int|null), api_source
 * - $isSaved bool
 */
ob_start();
if (!function_exists('e')) { function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); } }

$img = (!empty($recipe['image']) && (preg_match('#^https?://#i', $recipe['image']) || str_starts_with($recipe['image'], '/')))
    ? $recipe['image']
    : ('https://placehold.co/640x360/E8F5E9/36454F?text=' . urlencode($recipe['title'] ?? 'Recipe'));
?>

<section class="mb-6 flex flex-col sm:flex-row items-start justify-between gap-3">
  <div>
    <h1 class="text-3xl font-bold text-text-heading"><?php echo e($recipe['title'] ?? 'Recipe'); ?></h1>
    <?php if (!empty($recipe['servings'])): ?>
      <p class="text-text-muted mt-1">Servings: <?php echo (int)$recipe['servings']; ?></p>
    <?php endif; ?>
    <?php if (!empty($recipe['api_source'])): ?>
      <p class="text-xs text-text-muted mt-1">Source: <?php echo e($recipe['api_source']); ?></p>
    <?php endif; ?>
  </div>
  <div class="flex gap-2 flex-wrap">
    <?php
    // Show Edit/Delete if user owns this recipe (manual source) or is admin
    $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
    $isAdmin       = !empty($_SESSION['is_admin']);
    $recipeOwner   = (int)($recipe['user_id'] ?? 0);
    $isManual      = ($recipe['api_source'] ?? null) === 'manual';
    $canEdit       = $isManual && ($isAdmin || ($sessionUserId > 0 && $recipeOwner === $sessionUserId));
    $canDelete     = $isAdmin || ($sessionUserId > 0 && $recipeOwner === $sessionUserId);
    $dbId          = (int)($recipe['db_id'] ?? 0);
    ?>
    <?php if ($canEdit && $dbId > 0): ?>
      <a href="/recipes/<?php echo $dbId; ?>/edit" class="btn btn-subtle btn-md">Edit</a>
    <?php endif; ?>
    <?php if ($canDelete && $dbId > 0): ?>
      <form action="/recipes/<?php echo $dbId; ?>/delete" method="POST"
            onsubmit="return confirm('Permanently delete this recipe?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn btn-danger btn-md">Delete</button>
      </form>
    <?php endif; ?>
    <?php if (!empty($isSaved)): ?>
      <form action="/recipes/unsave" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="hidden" name="recipe_id" value="<?php echo (int)($recipe['db_id'] ?? 0); ?>" />
        <button type="submit" class="btn btn-secondary btn-md">Unsave</button>
      </form>
    <?php else: ?>
      <form action="/recipes/save" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="hidden" name="id" value="<?php echo e((string)($recipe['id'] ?? '')); ?>" />
        <input type="hidden" name="title" value="<?php echo e($recipe['title'] ?? 'Recipe'); ?>" />
        <input type="hidden" name="image" value="<?php echo e($recipe['image'] ?? ''); ?>" />
        <input type="hidden" name="sourceUrl" value="<?php echo e($recipe['sourceUrl'] ?? ''); ?>" />
        <input type="hidden" name="payload" value='<?php echo e(json_encode($recipe)); ?>' />
        <?php 
          $prov = 'fatsecret';
          if (!empty($recipe['api_source'])) { $prov = (string)$recipe['api_source']; }
          elseif (!empty($recipe['provider'])) { $prov = (string)$recipe['provider']; }
        ?>
        <input type="hidden" name="provider" value="<?php echo e($prov); ?>" />
        <button type="submit" class="btn btn-cta btn-md">Save</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="order-1 lg:order-1 lg:col-span-2 space-y-6">
    <div class="card overflow-hidden">
      <img src="<?php echo e($img); ?>" alt="Image of <?php echo e($recipe['title'] ?? 'Recipe'); ?>" class="w-full h-64 object-cover" />
    </div>

      <div class="card p-6">
          <h3 class="text-lg font-semibold mb-3">Ingredients</h3>
          <?php if (!empty($recipe['ingredients']) && is_array($recipe['ingredients'])): ?>
              <?php
              $pantrySet    = $pantryIngredients ?? [];
              $hasPantry    = !empty($pantrySet);
              $missingIngs  = []; // ingredients not found in pantry

              if ($hasPantry): ?>
                  <p class="text-xs text-text-muted mb-3">
                      <span class="inline-flex items-center gap-1">
                          <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                          You have this in your pantry
                      </span>
                  </p>
              <?php endif; ?>
              <ul class="space-y-1.5 text-sm">
                  <?php foreach ($recipe['ingredients'] as $ing):
                      $inPantry = false;
                      if ($hasPantry) {
                          $ingLower = strtolower((string)$ing);
                          foreach ($pantrySet as $pName) {
                              if ($pName !== '' && stripos($ingLower, $pName) !== false) {
                                  $inPantry = true;
                                  break;
                              }
                          }
                      }
                      if (!$inPantry) {
                          $missingIngs[] = (string)$ing;
                      }
                  ?>
                      <li class="flex items-start gap-2">
                          <?php if ($inPantry): ?>
                              <svg class="w-4 h-4 mt-0.5 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                          <?php else: ?>
                              <span class="w-4 h-4 mt-0.5 shrink-0 flex items-center justify-center">
                                  <span class="w-1.5 h-1.5 rounded-full bg-text-muted opacity-40"></span>
                              </span>
                          <?php endif; ?>
                          <span class="<?php echo $inPantry ? 'font-medium' : ''; ?>"><?php echo e($ing); ?></span>
                      </li>
                  <?php endforeach; ?>
              </ul>

              <?php if (!empty($missingIngs)): ?>
                  <form action="/shopping-list/add-from-recipe" method="POST" class="mt-4">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="recipe_id"    value="<?php echo (int)($recipe['db_id'] ?? 0); ?>">
                      <input type="hidden" name="recipe_title" value="<?php echo e($recipe['title'] ?? ''); ?>">
                      <?php foreach ($missingIngs as $mi): ?>
                          <input type="hidden" name="ingredients[]" value="<?php echo e($mi); ?>">
                      <?php endforeach; ?>
                      <button type="submit" class="btn btn-cta btn-sm w-full mt-1">
                          <svg class="w-4 h-4 mr-1.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 7h13L17 13M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                          </svg>
                          Add <?php echo count($missingIngs); ?> missing to shopping list
                      </button>
                  </form>
              <?php elseif ($hasPantry && empty($missingIngs)): ?>
                  <p class="mt-4 text-xs text-green-600 font-medium">You have all the ingredients!</p>
              <?php endif; ?>
          <?php else: ?>
              <p class="text-text-muted text-sm">Ingredients not listed.</p>
          <?php endif; ?>
      </div>

    <div class="card p-6">
      <h2 class="text-xl font-semibold mb-4">Instructions</h2>
      <?php if (!empty($recipe['steps']) && is_array($recipe['steps'])): ?>
        <ol class="list-decimal list-inside space-y-2 text-sm text-text-base">
          <?php foreach ($recipe['steps'] as $i => $s): ?>
            <li><?php echo e($s); ?></li>
          <?php endforeach; ?>
        </ol>
      <?php else: ?>
        <p class="text-text-muted text-sm">No instructions available.</p>
      <?php endif; ?>
    </div>

  </div>

  <aside class="order-2 lg:order-2 space-y-6">
      <?php if (!empty($recipe['nutrition_per_serving']) && is_array($recipe['nutrition_per_serving'])): ?>
          <div class="card p-6">
              <h3 class="text-lg font-semibold mb-3">Nutrition (per serving)</h3>
              <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-1 gap-x-4 gap-y-2 text-sm">
                  <?php
                  // Prioritize common nutrients order
                  $order = ['Calories','Protein','Carbohydrates','Net Carbs','Fat','Saturated Fat','Trans Fat','Sugar','Fiber','Sodium','Cholesterol','Potassium','Calcium','Iron','Vitamin C','Vitamin A'];
                  $per = $recipe['nutrition_per_serving'];
                  $printed = [];
                  foreach ($order as $label) {
                      if (isset($per[$label])) {
                          $n = $per[$label];
                          echo '<div class="flex justify-between"><dt class="text-text-muted">'.e($label).'</dt><dd class="font-medium">'.e(number_format((float)$n['amount'], ($label==='Calories'?0:2))).' '.e($n['unit']).'</dd></div>';
                          $printed[$label] = true;
                      }
                  }
                  // Print any remaining nutrients not in the common order
                  foreach ($per as $label => $n) {
                      if (!isset($printed[$label])) {
                          echo '<div class="flex justify-between"><dt class="text-text-muted">'.e($label).'</dt><dd class="font-medium">'.e(number_format((float)$n['amount'], 2)).' '.e($n['unit']).'</dd></div>';
                      }
                  }
                  ?>
              </dl>
          </div>
      <?php endif; ?>


    <div class="card p-6">
      <h3 class="text-lg font-semibold mb-3">Extras</h3>
      <?php if (!empty($recipe['estimated_price'])): ?>
        <p class="text-sm">Estimated Cost per serving: <strong>$<?php echo number_format((float)$recipe['estimated_price'], 2); ?></strong></p>
      <?php else: ?>
        <p class="text-sm text-text-muted">Price estimate unavailable.</p>
      <?php endif; ?>
      <?php if (!empty($recipe['sourceUrl'])): ?>
        <a href="<?php echo e($recipe['sourceUrl']); ?>" target="_blank" rel="noopener" class="btn btn-subtle btn-sm mt-3">Open Source Site</a>
      <?php endif; ?>
    </div>
  </aside>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
