<?php
/**
 * Shopping List View
 * Expects:
 * - $groups  array  Items keyed by recipe_title ('' = manually added)
 * - $total   int    Total item count
 * - $flash   array|null  ['type' => 'success|error|info', 'message' => string]
 */
ob_start();
if (!function_exists('e')) { function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); } }
$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<!-- Flash message -->
<?php if (!empty($flash)): ?>
    <?php
    $fType = $flash['type'] ?? 'info';
    $fClass = match($fType) {
        'success' => 'bg-green-50 border-green-300 text-green-800',
        'error'   => 'bg-red-50 border-red-300 text-red-800',
        default   => 'bg-blue-50 border-blue-300 text-blue-800',
    };
    ?>
    <div class="mb-4 px-4 py-3 rounded border <?php echo $fClass; ?> text-sm">
        <?php echo e($flash['message']); ?>
    </div>
<?php endif; ?>

<!-- Header -->
<section class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-3">
    <div>
        <h1 class="text-3xl font-bold text-text-heading">Shopping List</h1>
        <p class="text-text-muted mt-1"><?php echo $total; ?> item<?php echo $total !== 1 ? 's' : ''; ?> to pick up</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" onclick="startShopping()" class="btn btn-cta btn-md flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Start Shopping
        </button>
        <?php if ($total > 0): ?>
            <a href="/items" class="btn btn-subtle btn-md">View Pantry</a>
        <?php endif; ?>
    </div>
</section>

<!-- Add item manually -->
<div class="card p-4 mb-6">
    <form action="/shopping-list/item/add" method="POST" class="flex flex-col sm:flex-row gap-2">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input
            type="text"
            name="name"
            placeholder="Add an item (e.g. 2 cups milk)"
            required
            class="flex-1 border border-border-default rounded-lg px-3 py-2 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand"
        >
        <input
            type="text"
            name="quantity"
            placeholder="Qty (optional)"
            class="w-full sm:w-28 border border-border-default rounded-lg px-3 py-2 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand"
        >
        <button type="submit" class="btn btn-cta btn-md whitespace-nowrap">Add</button>
    </form>
</div>

<?php if ($total === 0): ?>
    <!-- Empty state -->
    <div class="card p-12 text-center">
        <svg class="mx-auto mb-4 w-12 h-12 text-text-muted opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 7h13L17 13M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
        </svg>
        <p class="text-text-muted mb-4">Your shopping list is empty.</p>
        <a href="/recipes/suggested" class="btn btn-cta btn-md">Find Recipes to Cook</a>
    </div>
<?php else: ?>
    <?php foreach ($groups as $groupTitle => $items): ?>
        <div class="mb-6">
            <!-- Group heading -->
            <?php if ($groupTitle !== ''): ?>
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-text-muted uppercase tracking-wide"><?php echo e($groupTitle); ?></h2>
                </div>
            <?php else: ?>
                <div class="flex items-center gap-2 mb-2">
                    <h2 class="text-sm font-semibold text-text-muted uppercase tracking-wide">Added manually</h2>
                </div>
            <?php endif; ?>

            <div class="card bg-bg-component rounded-xl shadow-sm overflow-hidden">
                <ul class="divide-y divide-border-default" id="group-<?php echo e(md5($groupTitle)); ?>">
                    <?php foreach ($items as $item): ?>
                        <?php $itemId = (int)$item['id']; ?>
                        <li class="p-4" id="item-<?php echo $itemId; ?>">

                            <!-- Display row -->
                            <div class="flex items-start justify-between gap-3" id="display-<?php echo $itemId; ?>">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-text-base truncate"><?php echo e($item['name']); ?></p>
                                    <?php if (!empty($item['quantity'])): ?>
                                        <p class="text-xs text-text-muted mt-0.5"><?php echo e($item['quantity']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        onclick="toggleEdit(<?php echo $itemId; ?>)"
                                        class="btn btn-subtle btn-sm"
                                        aria-label="Edit item"
                                    >Edit</button>

                                    <!-- Move to Pantry — opens modal -->
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm"
                                        title="Move to Pantry"
                                        onclick="openPantryModal(<?php echo $itemId; ?>, <?php echo htmlspecialchars(json_encode($item['name']), ENT_QUOTES, 'UTF-8'); ?>)"
                                    >
                                        <svg class="w-4 h-4 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                        Pantry
                                    </button>

                                    <!-- Delete -->
                                    <form action="/shopping-list/item/<?php echo $itemId; ?>/delete" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" aria-label="Remove item">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Inline edit form (hidden by default) -->
                            <div id="edit-<?php echo $itemId; ?>" class="hidden mt-3">
                                <form action="/shopping-list/item/<?php echo $itemId; ?>/update" method="POST" class="flex flex-col sm:flex-row gap-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input
                                        type="text"
                                        name="name"
                                        value="<?php echo e($item['name']); ?>"
                                        required
                                        class="flex-1 border border-border-default rounded-lg px-3 py-1.5 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand"
                                    >
                                    <input
                                        type="text"
                                        name="quantity"
                                        value="<?php echo e($item['quantity'] ?? ''); ?>"
                                        placeholder="Qty (optional)"
                                        class="w-full sm:w-28 border border-border-default rounded-lg px-3 py-1.5 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand"
                                    >
                                    <button type="submit" class="btn btn-cta btn-sm">Save</button>
                                    <button type="button" onclick="toggleEdit(<?php echo $itemId; ?>)" class="btn btn-subtle btn-sm">Cancel</button>
                                </form>
                            </div>

                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Barcode Scanner modal -->
<div id="scanner-modal" class="fixed inset-0 z-[90] hidden flex flex-col bg-black" role="dialog" aria-modal="true" aria-label="Barcode Scanner">
    <!-- Top bar -->
    <div class="flex items-center justify-between p-4 text-white shrink-0">
        <h2 class="text-lg font-semibold">Scan a Barcode</h2>
        <button type="button" onclick="stopScanner()" class="p-2 rounded-full hover:bg-white/10 transition" aria-label="Close scanner">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Camera feed area — html5-qrcode renders into #scanner-reader -->
    <div class="relative flex-1 overflow-hidden bg-black">
        <div id="scanner-reader" class="w-full h-full"></div>

        <!-- Error / unsupported message -->
        <div id="scanner-error" class="absolute inset-0 hidden flex items-center justify-center p-6">
            <div class="bg-bg-component rounded-xl p-6 text-center max-w-sm shadow-xl">
                <svg class="w-10 h-10 mx-auto mb-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p id="scanner-error-msg" class="text-sm text-text-base"></p>
                <button type="button" onclick="stopScanner()" class="btn btn-subtle btn-md mt-4">Close</button>
            </div>
        </div>
    </div>

    <!-- Bottom result panel -->
    <div class="shrink-0 p-4 bg-bg-component border-t border-border-default">
        <p class="text-xs text-text-muted text-center mb-3">Point your camera at a barcode on any food item</p>
        <div id="scanner-result" class="hidden"></div>
    </div>
</div>

<!-- Scanned item → Pantry form modal (for items NOT on the shopping list) -->
<div id="scanned-pantry-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50" onclick="closeScannedPantryModal()"></div>
    <div class="relative bg-bg-component rounded-xl shadow-xl w-full max-w-sm p-6 z-10">
        <h2 class="text-lg font-semibold text-text-heading mb-1">Add to Pantry</h2>
        <p id="scanned-display-name" class="text-sm text-text-muted mb-4 truncate"></p>

        <form id="scanned-pantry-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" id="scanned-food-name" name="food_name" value="">
            <input type="hidden" id="scanned-brand-name" name="brand_name" value="">

            <div class="space-y-3">
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label for="sp-quantity" class="block text-xs font-medium text-text-muted mb-1">Quantity</label>
                        <input type="number" id="sp-quantity" name="quantity" min="0" step="any" placeholder="e.g. 1"
                            class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand">
                    </div>
                    <div class="flex-1">
                        <label for="sp-unit" class="block text-xs font-medium text-text-muted mb-1">Unit</label>
                        <input type="text" id="sp-unit" name="unit" placeholder="e.g. box"
                            class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand">
                    </div>
                </div>
            </div>

            <div class="flex gap-2 mt-5">
                <button type="button" onclick="submitScannedPantry()" class="btn btn-cta btn-md flex-1">Add to Pantry</button>
                <button type="button" onclick="closeScannedPantryModal()" class="btn btn-subtle btn-md">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Move to Pantry modal -->
<div id="pantry-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="pantry-modal-title">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50" onclick="closePantryModal()"></div>
    <!-- Panel -->
    <div class="relative bg-bg-component rounded-xl shadow-xl w-full max-w-sm p-6 z-10">
        <h2 id="pantry-modal-title" class="text-lg font-semibold text-text-heading mb-1">Add to Pantry</h2>
        <p id="pantry-modal-item-name" class="text-sm text-text-muted mb-4 truncate"></p>

        <form id="pantry-modal-form" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

            <div class="space-y-3">
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label for="pm-quantity" class="block text-xs font-medium text-text-muted mb-1">Quantity</label>
                        <input
                            type="number"
                            id="pm-quantity"
                            name="quantity"
                            min="0"
                            step="any"
                            placeholder="e.g. 2"
                            class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand"
                        >
                    </div>
                    <div class="flex-1">
                        <label for="pm-unit" class="block text-xs font-medium text-text-muted mb-1">Unit</label>
                        <input
                            type="text"
                            id="pm-unit"
                            name="unit"
                            placeholder="e.g. cups"
                            class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand"
                        >
                    </div>
                </div>
                <div>
                    <label for="pm-brand" class="block text-xs font-medium text-text-muted mb-1">Brand <span class="font-normal">(optional)</span></label>
                    <input
                        type="text"
                        id="pm-brand"
                        name="brand"
                        placeholder="e.g. Quaker"
                        class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-bg-page text-text-base focus:outline-none focus:ring-2 focus:ring-brand"
                    >
                </div>
            </div>

            <p class="text-xs text-text-muted mt-3">We'll look up nutrition data automatically.</p>

            <div class="flex gap-2 mt-5">
                <button type="submit" class="btn btn-cta btn-md flex-1">Add to Pantry</button>
                <button type="button" class="btn btn-subtle btn-md" onclick="closePantryModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function toggleEdit(id) {
    var display = document.getElementById('display-' + id);
    var edit    = document.getElementById('edit-' + id);
    if (!display || !edit) return;
    var isEditing = !edit.classList.contains('hidden');
    if (isEditing) {
        edit.classList.add('hidden');
        display.classList.remove('hidden');
    } else {
        display.classList.add('hidden');
        edit.classList.remove('hidden');
        var input = edit.querySelector('input[name="name"]');
        if (input) input.focus();
    }
}

function openPantryModal(id, name) {
    var modal = document.getElementById('pantry-modal');
    var form  = document.getElementById('pantry-modal-form');
    var label = document.getElementById('pantry-modal-item-name');
    if (!modal || !form || !label) return;
    form.action = '/shopping-list/item/' + id + '/move-to-pantry';
    label.textContent = name;
    // Reset fields
    document.getElementById('pm-quantity').value = '';
    document.getElementById('pm-unit').value = '';
    document.getElementById('pm-brand').value = '';
    modal.classList.remove('hidden');
    document.getElementById('pm-quantity').focus();
}

function closePantryModal() {
    var modal = document.getElementById('pantry-modal');
    if (modal) modal.classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePantryModal();
        stopScanner();
        closeScannedPantryModal();
    }
});

// ─── Barcode Scanner (html5-qrcode) ──────────────────────────────────────────

var html5QrCode        = null;
var lastScannedBarcode = null;
var scanCooldown       = false;

function startShopping() {
    document.getElementById('scanner-modal').classList.remove('hidden');
    document.getElementById('scanner-error').classList.add('hidden');
    document.getElementById('scanner-result').classList.add('hidden');
    lastScannedBarcode = null;
    scanCooldown       = false;

    if (typeof Html5Qrcode === 'undefined') {
        showScannerError('Scanner library failed to load. Please refresh the page and try again.');
        return;
    }

    // Clean up any previous instance
    if (html5QrCode) {
        html5QrCode.stop().catch(function () {}).finally(function () {
            html5QrCode.clear();
            html5QrCode = null;
            _startHtml5QrCode();
        });
    } else {
        _startHtml5QrCode();
    }
}

function _startHtml5QrCode() {
    html5QrCode = new Html5Qrcode('scanner-reader');

    var config = {
        fps: 10,
        qrbox: { width: 250, height: 150 },
        aspectRatio: 1.7,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
    };

    html5QrCode.start(
        { facingMode: 'environment' },
        config,
        function (decodedText) {
            if (scanCooldown || decodedText === lastScannedBarcode) return;
            lastScannedBarcode = decodedText;
            scanCooldown = true;
            handleScannedBarcode(decodedText);
            setTimeout(function () { scanCooldown = false; }, 3000);
        },
        function () { /* per-frame scan errors are normal, ignore */ }
    ).catch(function (err) {
        showScannerError('Could not start camera: ' + err);
    });
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().catch(function () {}).finally(function () {
            html5QrCode.clear();
            html5QrCode = null;
        });
    }
    lastScannedBarcode = null;
    scanCooldown       = false;
    document.getElementById('scanner-modal').classList.add('hidden');
    document.getElementById('scanner-result').classList.add('hidden');
}

function showScannerError(msg) {
    var errorEl  = document.getElementById('scanner-error');
    var errorMsg = document.getElementById('scanner-error-msg');
    var reader   = document.getElementById('scanner-reader');
    if (errorEl)  errorEl.classList.remove('hidden');
    if (errorMsg) errorMsg.textContent = msg;
    if (reader)   reader.classList.add('hidden');
}

function handleScannedBarcode(barcode) {
    var resultEl = document.getElementById('scanner-result');
    resultEl.classList.remove('hidden');
    resultEl.innerHTML = '<p class="text-sm text-text-muted text-center">Looking up product\u2026</p>';

    var params = new URLSearchParams({
        csrf_token: '<?php echo $csrf; ?>',
        barcode: barcode
    });

    fetch('/api/shopping/scan-barcode', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(function (resp) { return resp.json(); })
    .then(function (data) {
        if (!data.found) {
            resultEl.innerHTML = '<div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800 text-center">'
                + escHtml(data.message || 'Product not found for this barcode.')
                + '</div>';
            return;
        }

        var displayName = data.brand_name
            ? escHtml(data.brand_name) + ' ' + escHtml(data.food_name)
            : escHtml(data.food_name);

        if (data.in_list) {
            resultEl.innerHTML = '<div class="p-3 bg-green-50 border border-green-200 rounded-lg z-[101]">'
                + '<p class="text-sm font-semibold text-green-800">' + displayName + '</p>'
                + '<p class="text-xs text-green-600 mb-2">On your shopping list — move to pantry?</p>'
                + '<button onclick="openPantryModalFromScan(' + data.list_item_id + ', ' + escHtml(JSON.stringify(data.list_item_name)) + ')" class="btn btn-cta btn-sm w-full">Add to Pantry</button>'
                + '</div>';
        } else {
            resultEl.innerHTML = '<div class="p-3 bg-blue-50 border border-blue-200 rounded-lg z-[101]">'
                + '<p class="text-sm font-semibold text-blue-800">' + displayName + '</p>'
                + '<p class="text-xs text-blue-600 mb-2">Not on your list — add directly to pantry?</p>'
                + '<button onclick="openScannedPantryForm(' + escHtml(JSON.stringify(data.food_name)) + ', ' + escHtml(JSON.stringify(data.brand_name || '')) + ')" class="btn btn-secondary btn-sm w-full">Add to Pantry</button>'
                + '</div>';
        }
    })
    .catch(function () {
        resultEl.innerHTML = '<div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 text-center">Error looking up product. Please try again.</div>';
    });
}

function openPantryModalFromScan(itemId, itemName) {
    stopScanner();
    openPantryModal(itemId, itemName);
}

function openScannedPantryForm(foodName, brandName) {
    document.getElementById('scanned-food-name').value  = foodName;
    document.getElementById('scanned-brand-name').value = brandName;
    document.getElementById('sp-quantity').value = '';
    document.getElementById('sp-unit').value     = '';

    var label = brandName ? brandName + ' ' + foodName : foodName;
    document.getElementById('scanned-display-name').textContent = label;
    document.getElementById('scanned-pantry-modal').classList.remove('hidden');
}

function closeScannedPantryModal() {
    document.getElementById('scanned-pantry-modal').classList.add('hidden');
}

function submitScannedPantry() {
    var form   = document.getElementById('scanned-pantry-form');
    var params = new URLSearchParams(new FormData(form));

    fetch('/api/shopping/scanned-to-pantry', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(function (resp) { return resp.json(); })
    .then(function (data) {
        if (data.success) {
            closeScannedPantryModal();
            stopScanner();
            // Refresh page so shopping list and pantry counts update
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Something went wrong.'));
        }
    })
    .catch(function () {
        alert('Error adding to pantry. Please try again.');
    });
}

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}
</script>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
