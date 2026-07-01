<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
$item = $item ?? [];
$input = $_POST ?: $item;
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Edit Item #<?php echo (int)($item['id'] ?? 0); ?></h1>
</div>
<form method="post" action="/admin/items/<?php echo (int)($item['id'] ?? 0); ?>" class="space-y-4">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1">Ingredient ID <span class="text-xs text-text-muted">(optional)</span></label>
      <input class="w-full" type="number" name="ingredient_id" value="<?php echo htmlspecialchars((string)($input['ingredient_id'] ?? '')); ?>">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Product ID <span class="text-xs text-text-muted">(optional)</span></label>
      <input class="w-full" type="number" name="product_id" value="<?php echo htmlspecialchars((string)($input['product_id'] ?? '')); ?>">
    </div>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1">Quantity</label>
      <input class="w-full" type="text" name="quantity" value="<?php echo htmlspecialchars((string)($input['quantity'] ?? '1')); ?>">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Unit <span class="text-xs text-text-muted">(optional)</span></label>
      <input class="w-full" type="text" name="unit" value="<?php echo htmlspecialchars((string)($input['unit'] ?? '')); ?>">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Purchase Date <span class="text-xs text-text-muted">(YYYY-MM-DD)</span></label>
      <input class="w-full" type="date" name="purchase_date" value="<?php echo htmlspecialchars((string)($input['purchase_date'] ?? '')); ?>">
    </div>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1">Expiration Date <span class="text-xs text-text-muted">(YYYY-MM-DD)</span></label>
      <input class="w-full" type="date" name="expiration_date" value="<?php echo htmlspecialchars((string)($input['expiration_date'] ?? '')); ?>">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Entered Name <span class="text-xs text-text-muted">(optional)</span></label>
      <input class="w-full" type="text" name="entered_name" value="<?php echo htmlspecialchars((string)($input['entered_name'] ?? '')); ?>">
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium mb-1">Entered Brand <span class="text-xs text-text-muted">(optional)</span></label>
    <input class="w-full" type="text" name="entered_brand" value="<?php echo htmlspecialchars((string)($input['entered_brand'] ?? '')); ?>">
  </div>
  <div class="pt-2 flex gap-2">
    <button type="submit" class="btn btn-cta">Update</button>
    <a class="btn btn-subtle" href="/admin/items">Cancel</a>
  </div>
</form>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
?>