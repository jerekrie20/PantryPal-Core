<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>
<div class="flex flex-col gap-3 mb-4">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">All Items</h1>
  </div>
  <!-- Filters -->
  <?php $filters = $filters ?? []; ?>
  <form method="get" action="/admin/items" class="bg-bg-component rounded-lg p-3 shadow flex flex-col sm:flex-row gap-3">
    <div class="flex-1">
      <label class="block text-xs text-text-muted mb-1">Search</label>
      <input class="input w-full" type="text" name="q" placeholder="Name, ingredient, product" value="<?php echo htmlspecialchars((string)($filters['q'] ?? '')); ?>">
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">User ID</label>
      <input class="input w-28" type="number" name="user_id" value="<?php echo htmlspecialchars((string)($filters['user_id'] ?? '')); ?>" placeholder="Any">
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">Per Page</label>
      <?php $pp = (int)($filters['perPage'] ?? 25); ?>
      <select class="input" name="perPage">
        <?php foreach ([10,25,50,100] as $n): ?>
          <option value="<?php echo $n; ?>" <?php echo $pp === $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex items-end gap-2">
      <button class="btn btn-subtle" type="submit">Apply</button>
      <a class="btn btn-subtle" href="/admin/items">Reset</a>
    </div>
  </form>
</div>

<!-- Mobile cards -->
<div class="sm:hidden space-y-3 mb-4">
<?php foreach (($items ?? []) as $it): ?>
<?php
  $name = $it['entered_name'] ?? '';
  if (!$name) {
    if (!empty($it['ingredient_name'])) $name = $it['ingredient_name'];
    elseif (!empty($it['product_title'])) $name = $it['product_title'];
    else $name = 'Item #' . (int)$it['id'];
  }
?>
  <div class="bg-bg-component rounded-lg p-4 shadow">
    <div class="flex items-start justify-between">
      <div>
        <div class="font-semibold text-text-heading"><?php echo htmlspecialchars($name); ?></div>
        <div class="text-sm text-text-muted">ID #<?php echo (int)$it['id']; ?> · User #<?php echo (int)($it['user_id'] ?? 0); ?></div>
      </div>
      <div class="text-xs text-text-muted"><?php echo htmlspecialchars((string)($it['created_at'] ?? '')); ?></div>
    </div>
    <div class="mt-3 flex gap-2">
      <a class="btn btn-subtle btn-sm" href="/admin/items/<?php echo (int)$it['id']; ?>/edit">Edit</a>
      <form method="post" action="/admin/items/<?php echo (int)$it['id']; ?>/delete" onsubmit="return confirm('Delete this item?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- Desktop table -->
<div class="hidden sm:block bg-bg-component rounded-xl shadow-md overflow-x-auto">
<table class="min-w-full text-sm">
<thead>
<tr class="text-left border-b border-border-default">
  <th class="px-4 py-2">ID</th>
  <th class="px-4 py-2">User</th>
  <th class="px-4 py-2">Name</th>
  <th class="px-4 py-2">Created</th>
  <th class="px-4 py-2">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach (($items ?? []) as $it): ?>
<?php
  $name = $it['entered_name'] ?? '';
  if (!$name) {
    if (!empty($it['ingredient_name'])) $name = $it['ingredient_name'];
    elseif (!empty($it['product_title'])) $name = $it['product_title'];
    else $name = 'Item #' . (int)$it['id'];
  }
?>
<tr class="border-b border-border-default">
  <td class="px-4 py-2"><?php echo (int)$it['id']; ?></td>
  <td class="px-4 py-2"><?php echo (int)($it['user_id'] ?? 0); ?></td>
  <td class="px-4 py-2"><?php echo htmlspecialchars($name); ?></td>
  <td class="px-4 py-2"><?php echo htmlspecialchars((string)($it['created_at'] ?? '')); ?></td>
  <td class="px-4 py-2">
    <div class="flex items-center gap-2">
      <a class="btn btn-subtle btn-xs" href="/admin/items/<?php echo (int)$it['id']; ?>/edit">Edit</a>
      <form method="post" action="/admin/items/<?php echo (int)$it['id']; ?>/delete" onsubmit="return confirm('Delete this item?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <button class="btn btn-danger btn-xs" type="submit">Delete</button>
      </form>
    </div>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php if (!empty($pagination) && is_array($pagination)): $p = $pagination; $cur = (int)($p['currentPage'] ?? 1); $tot = (int)($p['totalPages'] ?? 1); ?>
<div class="mt-4 flex items-center justify-between text-sm text-text-muted">
  <div>Page <?php echo $cur; ?> of <?php echo $tot; ?> · <?php echo (int)($p['totalItems'] ?? 0); ?> results</div>
  <div class="flex items-center gap-2">
    <?php $qs = $_GET; $qs['page'] = max(1, $cur - 1); $prevUrl = '/admin/items' . ($qs ? ('?' . http_build_query($qs)) : ''); ?>
    <?php $qs2 = $_GET; $qs2['page'] = min($tot, $cur + 1); $nextUrl = '/admin/items' . ($qs2 ? ('?' . http_build_query($qs2)) : ''); ?>
    <a class="btn btn-subtle btn-xs <?php echo $cur <= 1 ? 'pointer-events-none opacity-50' : ''; ?>" href="<?php echo htmlspecialchars($prevUrl); ?>">Prev</a>
    <a class="btn btn-subtle btn-xs <?php echo $cur >= $tot ? 'pointer-events-none opacity-50' : ''; ?>" href="<?php echo htmlspecialchars($nextUrl); ?>">Next</a>
  </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
