<?php
/**
 * Shopping List View
 * Expects:
 * - $groups  array       Items keyed by recipe_title ('' = manually added)
 * - $total   int         Total item count
 * - $flash   array|null  ['type' => 'success|error|info', 'message' => string]
 */
require_once VIEW_PATH . '/Components/ui_elements.php';
ob_start();

$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');

$startShoppingBtn = '<button type="button" onclick="startShopping()" class="btn btn-cta btn-md">'
    . '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">'
    . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>'
    . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>'
    . '</svg>'
    . 'Scan barcode'
    . '</button>';
$pantryBtn = $total > 0 ? '<a href="/items" class="btn btn-secondary btn-md">View pantry</a>' : '';
$actions = $startShoppingBtn . $pantryBtn;
?>

<!-- Flash -->
<?php if (!empty($flash)):
    $fType  = $flash['type'] ?? 'info';
    $fClass = match ($fType) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        default   => 'alert-info',
    };
?>
    <div class="<?= $fClass ?> mb-4 text-sm"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php ui_page_header(
    'Shopping list',
    $total . ' item' . ($total !== 1 ? 's' : '') . ' to pick up.',
    $actions,
    'Shopping'
); ?>

<!-- Add item manually -->
<div class="card mb-6">
    <form action="/shopping-list/item/add" method="POST" class="flex flex-col sm:flex-row gap-2">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="text" name="name" placeholder="Add an item (e.g. 2 cups milk)" required class="flex-1 min-w-0">
        <input type="text" name="quantity" placeholder="Qty (optional)" class="w-full sm:w-32">
        <button type="submit" class="btn btn-cta btn-md whitespace-nowrap">Add</button>
    </form>
</div>

<?php if ($total === 0): ?>
    <?php ui_empty_state(
        'Your shopping list is empty',
        'Add items above, or save a recipe and we\'ll auto-fill the missing ingredients.',
        'Find recipes to cook',
        '/recipes/suggested',
        '🛒'
    ); ?>
<?php else: ?>
    <?php foreach ($groups as $groupTitle => $items): ?>
        <?php $isManual = $groupTitle === ''; ?>
        <section class="mb-6">
            <h2 class="eyebrow text-text-muted mb-2">
                <?= $isManual ? 'Added manually' : e($groupTitle) ?>
            </h2>

            <div class="card-flush">
                <ul class="divide-y divide-border-default" id="group-<?= e(md5((string)$groupTitle)) ?>">
                    <?php foreach ($items as $item):
                        $itemId = (int)$item['id'];
                    ?>
                        <li class="p-4" id="item-<?= $itemId ?>">
                            <!-- Display row -->
                            <div class="flex items-start justify-between gap-3" id="display-<?= $itemId ?>">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-text-base truncate"><?= e($item['name']) ?></p>
                                    <?php if (!empty($item['quantity'])): ?>
                                        <p class="text-xs text-text-muted mt-0.5"><?= e($item['quantity']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" onclick="toggleEdit(<?= $itemId ?>)" class="btn btn-ghost btn-sm" aria-label="Edit item">Edit</button>
                                    <button type="button" class="btn btn-secondary btn-sm" title="Move to pantry"
                                            onclick="openPantryModal(<?= $itemId ?>, <?= htmlspecialchars(json_encode($item['name']), ENT_QUOTES, 'UTF-8') ?>)">
                                        Move to pantry
                                    </button>
                                    <form action="/shopping-list/item/<?= $itemId ?>/delete" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" aria-label="Remove item">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Inline edit form -->
                            <div id="edit-<?= $itemId ?>" class="hidden mt-3">
                                <form action="/shopping-list/item/<?= $itemId ?>/update" method="POST" class="flex flex-col sm:flex-row gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="text" name="name" value="<?= e($item['name']) ?>" required class="flex-1 min-w-0">
                                    <input type="text" name="quantity" value="<?= e($item['quantity'] ?? '') ?>" placeholder="Qty (optional)" class="w-full sm:w-32">
                                    <button type="submit" class="btn btn-cta btn-sm">Save</button>
                                    <button type="button" onclick="toggleEdit(<?= $itemId ?>)" class="btn btn-ghost btn-sm">Cancel</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Barcode scanner modal -->
<div id="scanner-modal" class="fixed inset-0 z-[90] hidden flex flex-col bg-black" role="dialog" aria-modal="true" aria-label="Barcode Scanner">
    <div class="flex items-center justify-between p-4 text-white shrink-0">
        <h2 class="text-lg font-semibold">Scan a barcode</h2>
        <button type="button" onclick="stopScanner()" class="p-2 rounded-full hover:bg-white/10 transition" aria-label="Close scanner">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="relative flex-1 overflow-hidden bg-black">
        <div id="scanner-reader" class="w-full h-full"></div>

        <div id="scanner-error" class="absolute inset-0 hidden flex items-center justify-center p-6">
            <div class="card max-w-sm text-center">
                <div class="text-3xl mb-3" aria-hidden="true">⚠️</div>
                <p id="scanner-error-msg" class="text-sm text-text-base"></p>
                <button type="button" onclick="stopScanner()" class="btn btn-secondary btn-md mt-4">Close</button>
            </div>
        </div>
    </div>

    <div class="shrink-0 p-4 bg-bg-component border-t border-border-default">
        <p class="text-xs text-text-muted text-center mb-3">Point your camera at a barcode on any food item.</p>
        <div id="scanner-result" class="hidden"></div>
    </div>
</div>

<!-- Scanned item → add to pantry (for items NOT on the list) -->
<div id="scanned-pantry-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50" onclick="closeScannedPantryModal()"></div>
    <div class="relative card w-full max-w-sm z-10">
        <h2 class="text-text-heading text-lg mb-1">Add to pantry</h2>
        <p id="scanned-display-name" class="text-sm text-text-muted mb-4 truncate"></p>

        <form id="scanned-pantry-form" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" id="scanned-barcode" name="barcode" value="">
            <input type="hidden" id="scanned-food-name" name="food_name" value="">
            <input type="hidden" id="scanned-brand-name" name="brand_name" value="">

            <div class="flex gap-3">
                <div class="flex-1">
                    <label for="sp-quantity" class="block text-xs font-medium text-text-muted mb-1">Quantity</label>
                    <input type="number" id="sp-quantity" name="quantity" min="0" step="any" placeholder="e.g. 1" class="w-full">
                </div>
                <div class="flex-1">
                    <label for="sp-unit" class="block text-xs font-medium text-text-muted mb-1">Unit</label>
                    <input type="text" id="sp-unit" name="unit" placeholder="e.g. box" class="w-full">
                </div>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="submitScannedPantry()" class="btn btn-cta btn-md flex-1">Add to pantry</button>
                <button type="button" onclick="closeScannedPantryModal()" class="btn btn-ghost btn-md">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Move-to-pantry modal -->
<div id="pantry-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="pantry-modal-title">
    <div class="absolute inset-0 bg-black/50" onclick="closePantryModal()"></div>
    <div class="relative card w-full max-w-sm z-10">
        <h2 id="pantry-modal-title" class="text-text-heading text-lg mb-1">Add to pantry</h2>
        <p id="pantry-modal-item-name" class="text-sm text-text-muted mb-4 truncate"></p>

        <form id="pantry-modal-form" method="POST" action="" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="flex gap-3">
                <div class="flex-1">
                    <label for="pm-quantity" class="block text-xs font-medium text-text-muted mb-1">Quantity</label>
                    <input type="number" id="pm-quantity" name="quantity" min="0" step="any" placeholder="e.g. 2" class="w-full">
                </div>
                <div class="flex-1">
                    <label for="pm-unit" class="block text-xs font-medium text-text-muted mb-1">Unit</label>
                    <input type="text" id="pm-unit" name="unit" placeholder="e.g. cups" class="w-full">
                </div>
            </div>
            <div>
                <label for="pm-brand" class="block text-xs font-medium text-text-muted mb-1">Brand <span class="font-normal text-text-subtle">(optional)</span></label>
                <input type="text" id="pm-brand" name="brand" placeholder="e.g. Quaker" class="w-full">
            </div>

            <p class="text-xs text-text-muted">We'll look up nutrition data automatically.</p>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn btn-cta btn-md flex-1">Add to pantry</button>
                <button type="button" class="btn btn-ghost btn-md" onclick="closePantryModal()">Cancel</button>
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
        showScannerError('Scanner library failed to load. Refresh the page and try again.');
        return;
    }

    // Camera requires a secure context (HTTPS); a plain-HTTP LAN address will never work
    if (!window.isSecureContext) {
        showScannerError('The camera only works over a secure (https) connection. Open the site via its https address and try again.');
        return;
    }

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
        var msg = String(err);
        if (msg.indexOf('NotAllowedError') !== -1) {
            showScannerError('Camera access is blocked for this site. Tap the lock/aA icon in your browser\'s address bar, allow Camera, then reload this page.');
        } else if (msg.indexOf('NotFoundError') !== -1) {
            showScannerError('No camera was found on this device.');
        } else if (msg.indexOf('NotReadableError') !== -1) {
            showScannerError('The camera is being used by another app. Close it and try again.');
        } else {
            showScannerError('Could not start camera: ' + msg);
        }
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
    resultEl.innerHTML = '<p class="text-sm text-text-muted text-center">Looking up product…</p>';

    var params = new URLSearchParams({
        csrf_token: '<?= $csrf ?>',
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
            resultEl.innerHTML = '<div class="alert-warning text-sm text-center">'
                + escHtml(data.message || 'Product not found for this barcode.')
                + '</div>';
            return;
        }

        var displayName = data.brand_name
            ? escHtml(data.brand_name) + ' ' + escHtml(data.food_name)
            : escHtml(data.food_name);

        if (data.in_list) {
            resultEl.innerHTML = '<div class="alert-success">'
                + '<p class="text-sm font-semibold">' + displayName + '</p>'
                + '<p class="text-xs mb-2">On your shopping list — move to pantry?</p>'
                + '<button onclick="openPantryModalFromScan(' + data.list_item_id + ', ' + escHtml(JSON.stringify(data.list_item_name)) + ')" class="btn btn-cta btn-sm w-full">Add to pantry</button>'
                + '</div>';
        } else {
            resultEl.innerHTML = '<div class="alert-info">'
                + '<p class="text-sm font-semibold">' + displayName + '</p>'
                + '<p class="text-xs mb-2">Not on your list — add directly to pantry?</p>'
                + '<button onclick="openScannedPantryForm(' + escHtml(JSON.stringify(data.food_name)) + ', ' + escHtml(JSON.stringify(data.brand_name || '')) + ', \'' + barcode + '\')" class="btn btn-secondary btn-sm w-full">Add to pantry</button>'
                + '</div>';
        }
    })
    .catch(function () {
        resultEl.innerHTML = '<div class="alert-danger text-sm text-center">Error looking up product. Try again.</div>';
    });
}

function openPantryModalFromScan(itemId, itemName) {
    stopScanner();
    openPantryModal(itemId, itemName);
}

function openScannedPantryForm(foodName, brandName, barcode) {
    document.getElementById('scanned-food-name').value  = foodName;
    document.getElementById('scanned-brand-name').value = brandName;
    document.getElementById('scanned-barcode').value    = barcode || '';
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
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Something went wrong.'));
        }
    })
    .catch(function () {
        alert('Error adding to pantry. Try again.');
    });
}

function escHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/Layouts/Users/layout.php';
