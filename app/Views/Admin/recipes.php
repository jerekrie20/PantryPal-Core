<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
$filters = $filters ?? [];
$pagination = $pagination ?? null;
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>
<div class="flex flex-col gap-3 mb-4">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">All Recipes</h1>
    <a href="/admin/recipes/create" class="btn btn-cta btn-sm">Add Recipe</a>
  </div>
  <!-- Filters -->
  <form method="get" action="/admin/recipes" class="bg-bg-component rounded-lg p-3 shadow flex flex-col sm:flex-row gap-3">
    <div class="flex-1">
      <label class="block text-xs text-text-muted mb-1">Search</label>
      <input class="w-full" type="text" name="q" placeholder="Title, source URL, ID..." value="<?php echo htmlspecialchars((string)($filters['q'] ?? '')); ?>">
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">Source</label>
      <?php $src = (string)($filters['source'] ?? ''); $opts = ['', 'manual','fdc','off','fatsecret']; ?>
      <select name="source">
        <?php foreach ($opts as $opt): $label = $opt === '' ? '— any —' : ucwords(str_replace('_',' ', $opt)); ?>
          <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $src === $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">Owner User ID</label>
      <input class="w-28" type="number" name="user_id" value="<?php echo htmlspecialchars((string)($filters['user_id'] ?? '')); ?>" placeholder="Any">
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">Per Page</label>
      <?php $pp = (int)($filters['perPage'] ?? 25); ?>
      <select name="perPage">
        <?php foreach ([10,25,50,100] as $n): ?>
          <option value="<?php echo $n; ?>" <?php echo $pp === $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex items-end gap-2">
      <button class="btn btn-subtle" type="submit">Apply</button>
      <a class="btn btn-subtle" href="/admin/recipes">Reset</a>
    </div>
  </form>
</div>

<!-- Mobile cards -->
<div class="sm:hidden space-y-3 mb-4">
<?php foreach (($recipes ?? []) as $r): ?>
  <div class="bg-bg-component rounded-lg p-4 shadow">
    <div class="flex items-start justify-between">
      <div>
        <div class="font-semibold text-text-heading"><?php echo htmlspecialchars($r['title'] ?? ''); ?></div>
        <div class="text-sm text-text-muted">ID #<?php echo (int)$r['id']; ?> · Source <?php echo htmlspecialchars((string)($r['api_source'] ?? '')); ?></div>
        <?php if (!empty($r['user_id'])): ?><div class="text-sm text-text-muted">Owner #<?php echo (int)$r['user_id']; ?></div><?php endif; ?>
      </div>
      <div class="text-xs text-text-muted"><?php echo htmlspecialchars((string)($r['created_at'] ?? '')); ?></div>
    </div>
    <div class="mt-3 flex gap-2">
      <a class="btn btn-subtle btn-sm" href="/admin/recipes/<?php echo (int)$r['id']; ?>/edit">Edit</a>
      <form method="post" action="/admin/recipes/<?php echo (int)$r['id']; ?>/delete" onsubmit="return confirm('Delete this recipe?');">
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
  <th class="px-4 py-2">Title</th>
  <th class="px-4 py-2">Source</th>
  <th class="px-4 py-2">Owner</th>
  <th class="px-4 py-2">Created</th>
  <th class="px-4 py-2">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach (($recipes ?? []) as $r): ?>
<tr class="border-b border-border-default">
  <td class="px-4 py-2"><?php echo (int)$r['id']; ?></td>
  <td class="px-4 py-2"><?php echo htmlspecialchars($r['title'] ?? ''); ?></td>
  <td class="px-4 py-2"><?php echo htmlspecialchars((string)($r['api_source'] ?? '')); ?></td>
  <td class="px-4 py-2"><?php echo htmlspecialchars((string)($r['user_id'] ?? '')); ?></td>
  <td class="px-4 py-2"><?php echo htmlspecialchars((string)($r['created_at'] ?? '')); ?></td>
  <td class="px-4 py-2">
    <div class="flex items-center gap-2">
      <a class="btn btn-subtle btn-sm" href="/admin/recipes/<?php echo (int)$r['id']; ?>/edit">Edit</a>
      <form method="post" action="/admin/recipes/<?php echo (int)$r['id']; ?>/delete" onsubmit="return confirm('Delete this recipe?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
      </form>
    </div>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php if (empty($recipes)): ?>
  <div class="mt-4 text-sm text-text-muted">No recipes found. Try adjusting the filters.</div>
<?php endif; ?>
<?php if (!empty($pagination) && is_array($pagination)): $p = $pagination; $cur = (int)($p['currentPage'] ?? 1); $tot = (int)($p['totalPages'] ?? 1); ?>
<div class="mt-4 flex items-center justify-between text-sm text-text-muted">
  <div>Page <?php echo $cur; ?> of <?php echo $tot; ?> · <?php echo (int)($p['totalItems'] ?? 0); ?> results</div>
  <div class="flex items-center gap-2">
    <?php $qs = $_GET; $qs['page'] = max(1, $cur - 1); $prevUrl = '/admin/recipes' . ($qs ? ('?' . http_build_query($qs)) : ''); ?>
    <?php $qs2 = $_GET; $qs2['page'] = min($tot, $cur + 1); $nextUrl = '/admin/recipes' . ($qs2 ? ('?' . http_build_query($qs2)) : ''); ?>
    <a class="btn btn-subtle btn-sm <?php echo $cur <= 1 ? 'pointer-events-none opacity-50' : ''; ?>" href="<?php echo htmlspecialchars($prevUrl); ?>">Prev</a>
    <a class="btn btn-subtle btn-sm <?php echo $cur >= $tot ? 'pointer-events-none opacity-50' : ''; ?>" href="<?php echo htmlspecialchars($nextUrl); ?>">Next</a>
  </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
