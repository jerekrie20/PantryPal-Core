<?php
/**
 * Items Index View
 *
 * Expects from controller:
 * - $title string
 * - $items array (raw rows from items table with keys: id, ingredient_id, product_id, expiration_date, entered_name, etc.)
 * - $pagination array (currentPage, totalPages, totalItems, itemsPerPage)
 */

ob_start();

// Helper: safe escape
if (!function_exists('e')) {
    function e($v): string {
        if ($v === null) return '';
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

// Build display items separated by kind (enrich with category/name/image like Dashboard)
$ingredients = [];
$products    = [];

// Helper: collapse category which could be string/JSON/array
if (!function_exists('stringify_category')) {
    function stringify_category($cat): ?string {
        if ($cat === null || $cat === '') return null;
        if (is_string($cat)) {
            $trim = ltrim($cat);
            if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                $decoded = json_decode($cat, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return stringify_category($decoded);
                }
            }
            return $cat;
        }
        if (is_array($cat)) {
            if (isset($cat['categoryPath']) && is_array($cat['categoryPath'])) {
                return implode(' › ', array_filter($cat['categoryPath'], 'is_string'));
            }
            $vals = [];
            foreach ($cat as $v) if (is_string($v)) $vals[] = $v;
            return $vals ? implode(' › ', $vals) : null;
        }
        return null;
    }
}

// No extra model lookups; prefer joined fields from Items::findAll
try {
    $today = new \DateTimeImmutable('today');
} catch (\Exception $e) {
    $today = null;
}

if (!empty($items) && is_array($items)) {
    foreach ($items as $row) {
        $id = (int)($row['id'] ?? 0);
        if ($id <= 0) continue;

        // Status and badge (lightweight, similar to Dashboard)
        $status = 'In Stock';
        $badge  = 'badge-success';
        $expiredFlag = false;
        if (!empty($row['expiration_date']) && $today) {
            try {
                $exp      = new \DateTimeImmutable($row['expiration_date']);
                $diffDays = (int)$today->diff($exp)->format('%r%a');
                if ($diffDays < 0) {
                    $status = 'Expired ' . (abs($diffDays) === 1 ? '1 day ago' : abs($diffDays) . ' days ago');
                    $badge  = 'badge-danger';
                    $expiredFlag = true;
                } elseif ($diffDays === 0) {
                    $status = 'Expires today';
                    $badge  = 'badge-warning';
                } elseif ($diffDays <= 3) {
                    $status = 'Expires in ' . ($diffDays === 1 ? '1 day' : $diffDays . ' days');
                    $badge  = 'badge-warning';
                } else {
                    $status = 'Expires in ' . $diffDays . ' days';
                    $badge  = 'badge-neutral';
                }
            } catch (\Exception $e) {
                // keep defaults
            }
        }

        $isIngredient = !empty($row['ingredient_id']);
        $isProduct    = !$isIngredient && !empty($row['product_id']);

        $name = $row['entered_name'] ?? null;
        $category = null;
        $image = null;

        if ($isIngredient) {
            $name = $row['ingredient_name'] ?? $name;
            $category = stringify_category($row['ingredient_category'] ?? null);
            $image = $row['ingredient_image_url'] ?? null;
        } elseif ($isProduct) {
            $name = $row['product_title'] ?? $name;
            $category = stringify_category($row['product_category'] ?? null);
            $image = $row['product_image_url'] ?? null;
        }

        if (!$name) {
            $name = $isIngredient ? ('Ingredient #' . $id) : ($isProduct ? ('Product #' . $id) : ('Item #' . $id));
        }

        $display = [
            'id'          => $id,
            'name'        => $name,
            'status'      => $status,
            'category'    => $category ?? 'Uncategorized',
            'badge_class' => $badge,
            'image'       => $image, // placeholder handled in component
            'expired'     => $expiredFlag,
            'url'         => $isIngredient ? ('/ingredients/view/' . $id) : ($isProduct ? ('/products/view/' . $id) : ('/items/view/' . $id)),
        ];

        if ($isIngredient) {
            $ingredients[] = $display;
        } elseif ($isProduct) {
            $products[] = $display;
        } else {
            $ingredients[] = $display;
        }
    }
}

$ingredientsCount = count($ingredients);
$productsCount = count($products);

// Determine default tab based on URL hash via JS; default to ingredients
?>

<section class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-text-heading"><?php echo e($title ?? 'My Pantry'); ?></h1>
        <p class="text-text-muted mt-1">Browse your items by Ingredients or Products.</p>
    </div>
    <a href="/items/create" class="btn btn-cta btn-md mt-4 sm:mt-0">Add New Item</a>
</section>

<!-- Tabs -->
<div class="flex border-b border-border-default mb-4" role="tablist" aria-label="Item Types">
    <button id="tab-ingredients" class="px-4 py-2 -mb-px border-b-2 border-transparent hover:border-border-muted text-sm font-semibold rounded-t-md transition-colors" data-tab="ingredients" role="tab" aria-controls="panel-ingredients" aria-selected="true">
        Ingredients <span class="ml-2 inline-block text-xs px-2 py-0.5 rounded-full bg-gray-200 text-gray-700"><?php echo (int)$ingredientsCount; ?></span>
    </button>
    <button id="tab-products" class="ml-2 px-4 py-2 -mb-px border-b-2 border-transparent hover:border-border-muted text-sm font-semibold rounded-t-md transition-colors" data-tab="products" role="tab" aria-controls="panel-products" aria-selected="false">
        Products <span class="ml-2 inline-block text-xs px-2 py-0.5 rounded-full bg-gray-200 text-gray-700"><?php echo (int)$productsCount; ?></span>
    </button>
</div>

<!-- Panels -->
<div id="panel-ingredients" role="tabpanel" aria-labelledby="tab-ingredients">
    <div class="bg-bg-component rounded-xl shadow-md">
        <ul class="divide-y divide-border-default">
            <?php if (empty($ingredients)): ?>
                <li class="p-8 text-center text-text-muted">No ingredients yet. Add one to get started!</li>
            <?php else: ?>
                <?php foreach ($ingredients as $item): ?>
                    <?php include VIEW_PATH . '/Components/pantry_item.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div id="panel-products" role="tabpanel" aria-labelledby="tab-products" class="hidden">
    <div class="bg-bg-component rounded-xl shadow-md">
        <ul class="divide-y divide-border-default">
            <?php if (empty($products)): ?>
                <li class="p-8 text-center text-text-muted">No products yet. Add one to get started!</li>
            <?php else: ?>
                <?php foreach ($products as $item): ?>
                    <?php include VIEW_PATH . '/Components/pantry_item.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Simple tabs script -->
<script>
(function(){
  function setActive(btn, active){
    var add = function(el, cls){ cls.split(' ').forEach(function(c){ if(c) el.classList.add(c); }); };
    var rem = function(el, cls){ cls.split(' ').forEach(function(c){ if(c) el.classList.remove(c); }); };
    if(active){
      add(btn, 'border-primary-600 text-primary-700 bg-white');
      rem(btn, 'border-transparent text-gray-600');
    } else {
      add(btn, 'border-transparent text-gray-600');
      rem(btn, 'border-primary-600 text-primary-700 bg-white');
    }
  }
  function showTab(kind){
    var ingBtn = document.getElementById('tab-ingredients');
    var prodBtn = document.getElementById('tab-products');
    var ing = document.getElementById('panel-ingredients');
    var prod = document.getElementById('panel-products');
    var isIng = (kind === 'products') ? false : true;

    if(isIng){
      ing.classList.remove('hidden');
      prod.classList.add('hidden');
      setActive(ingBtn, true);
      setActive(prodBtn, false);
      ingBtn.setAttribute('aria-selected','true');
      prodBtn.setAttribute('aria-selected','false');
      if(location.hash !== '#ingredients') history.replaceState(null, '', '#ingredients');
    } else {
      prod.classList.remove('hidden');
      ing.classList.add('hidden');
      setActive(prodBtn, true);
      setActive(ingBtn, false);
      prodBtn.setAttribute('aria-selected','true');
      ingBtn.setAttribute('aria-selected','false');
      if(location.hash !== '#products') history.replaceState(null, '', '#products');
    }
  }
  document.getElementById('tab-ingredients').addEventListener('click', function(){ showTab('ingredients'); });
  document.getElementById('tab-products').addEventListener('click', function(){ showTab('products'); });
  // on load
  var hash = (location.hash || '#ingredients').toLowerCase();
  showTab(hash === '#products' ? 'products' : 'ingredients');
})();
</script>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/layouts/Users/layout.php';
