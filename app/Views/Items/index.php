<?php
/**
 * Items Index View
 *
 * Expects from controller:
 * - $title string
 * - $ingredients array  Display items from PantryItemAssembler::summary
 * - $products array     Display items from PantryItemAssembler::summary
 * - $pagination array   (currentPage, totalPages, totalItems, itemsPerPage)
 */

require_once VIEW_PATH . '/Components/ui_elements.php';

ob_start();

$ingredients = $ingredients ?? [];
$products    = $products ?? [];

$ingredientsCount = count($ingredients);
$productsCount = count($products);
$totalCount = $ingredientsCount + $productsCount;
?>

<?php ui_page_header(
    $title ?? 'My Pantry',
    $totalCount > 0
        ? 'Browse your items by ingredients or products. Filter by name to find something specific.'
        : 'Add your first item to start tracking expirations and cooking smarter.',
    '<a href="/items/create" class="btn btn-cta btn-md">Add new item</a>',
    'Inventory'
); ?>

<?php if ($totalCount === 0): ?>
    <?php ui_empty_state(
        'Your pantry is empty',
        'Scan a barcode or type in your first ingredient or product. We\'ll auto-fill the details when we recognize it.',
        'Add your first item',
        '/items/create',
        '🛒'
    ); ?>
<?php else: ?>

    <!-- Segmented tabs + filter -->
    <div class="card-flush p-4 mb-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
        <div role="tablist" aria-label="Item types" class="inline-flex bg-bg-subtle rounded-lg p-1 gap-1">
            <button id="tab-ingredients" data-tab="ingredients" role="tab" aria-selected="true" aria-controls="panel-ingredients"
                    class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors">
                Ingredients
                <span class="ml-1.5 inline-flex items-center justify-center text-xs px-1.5 py-0.5 rounded-full bg-bg-component text-text-muted"><?= (int)$ingredientsCount ?></span>
            </button>
            <button id="tab-products" data-tab="products" role="tab" aria-selected="false" aria-controls="panel-products"
                    class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors">
                Products
                <span class="ml-1.5 inline-flex items-center justify-center text-xs px-1.5 py-0.5 rounded-full bg-bg-component text-text-muted"><?= (int)$productsCount ?></span>
            </button>
        </div>

        <div class="relative w-full sm:w-72">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
            </svg>
            <input id="pantry-filter" type="search" placeholder="Filter items…" class="w-full pl-9" autocomplete="off" />
        </div>
    </div>

    <!-- Ingredients panel -->
    <div id="panel-ingredients" role="tabpanel" aria-labelledby="tab-ingredients">
        <?php if (empty($ingredients)): ?>
            <?php ui_empty_state(
                'No ingredients yet',
                'Generic items like apples, flour, or chicken — track them as ingredients to power recipe matching.',
                'Add an ingredient',
                '/items/create',
                '🥕'
            ); ?>
        <?php else: ?>
            <div class="card-flush">
                <ul class="divide-y divide-border-default" data-list="ingredients">
                    <?php foreach ($ingredients as $item): ?>
                        <?php include VIEW_PATH . '/Components/pantry_item.php'; ?>
                    <?php endforeach; ?>
                </ul>
                <p data-empty="ingredients" class="hidden p-6 text-center text-text-muted text-sm">No ingredients match that filter.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Products panel -->
    <div id="panel-products" role="tabpanel" aria-labelledby="tab-products" class="hidden">
        <?php if (empty($products)): ?>
            <?php ui_empty_state(
                'No products yet',
                'Specific branded items like Ritz Crackers or Häagen-Dazs — scan a barcode to add one in seconds.',
                'Scan or add a product',
                '/items/create',
                '🛒'
            ); ?>
        <?php else: ?>
            <div class="card-flush">
                <ul class="divide-y divide-border-default" data-list="products">
                    <?php foreach ($products as $item): ?>
                        <?php include VIEW_PATH . '/Components/pantry_item.php'; ?>
                    <?php endforeach; ?>
                </ul>
                <p data-empty="products" class="hidden p-6 text-center text-text-muted text-sm">No products match that filter.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    (function(){
        function setActive(btn, active){
            if (active) {
                btn.classList.add('bg-bg-component', 'text-text-heading', 'shadow-sm');
                btn.classList.remove('text-text-muted');
            } else {
                btn.classList.remove('bg-bg-component', 'text-text-heading', 'shadow-sm');
                btn.classList.add('text-text-muted');
            }
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        }
        function showTab(kind){
            var isProducts = kind === 'products';
            var ingBtn = document.getElementById('tab-ingredients');
            var prodBtn = document.getElementById('tab-products');
            var ingPanel = document.getElementById('panel-ingredients');
            var prodPanel = document.getElementById('panel-products');
            setActive(ingBtn, !isProducts);
            setActive(prodBtn, isProducts);
            ingPanel.classList.toggle('hidden', isProducts);
            prodPanel.classList.toggle('hidden', !isProducts);
            var hash = isProducts ? '#products' : '#ingredients';
            if (location.hash !== hash) history.replaceState(null, '', hash);
        }
        document.getElementById('tab-ingredients').addEventListener('click', function(){ showTab('ingredients'); });
        document.getElementById('tab-products').addEventListener('click', function(){ showTab('products'); });
        showTab((location.hash || '').toLowerCase() === '#products' ? 'products' : 'ingredients');

        // Filter
        var input = document.getElementById('pantry-filter');
        if (input) {
            input.addEventListener('input', function(){
                var q = (input.value || '').trim().toLowerCase();
                ['ingredients', 'products'].forEach(function(kind){
                    var list = document.querySelector('[data-list="' + kind + '"]');
                    var empty = document.querySelector('[data-empty="' + kind + '"]');
                    if (!list) return;
                    var rows = list.querySelectorAll('li');
                    var visible = 0;
                    rows.forEach(function(li){
                        var text = (li.textContent || '').toLowerCase();
                        var match = q === '' || text.indexOf(q) !== -1;
                        li.style.display = match ? '' : 'none';
                        if (match) visible++;
                    });
                    if (empty) empty.classList.toggle('hidden', visible !== 0);
                });
            });
        }
    })();
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
