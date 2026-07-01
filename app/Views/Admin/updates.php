<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>
<div class="flex flex-col gap-3 mb-4">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Updates</h1>
    <a href="/admin/updates/create" class="btn btn-cta btn-sm">Post Update</a>
  </div>
  <!-- Filters -->
  <?php $filters = $filters ?? []; ?>
  <form method="get" action="/admin/updates" class="bg-bg-component rounded-lg p-3 shadow flex flex-col sm:flex-row gap-3">
    <div class="flex-1">
      <label class="block text-xs text-text-muted mb-1">Search</label>
      <input class="w-full" type="text" name="q" placeholder="Title or message" value="<?php echo htmlspecialchars((string)($filters['q'] ?? '')); ?>">
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">Status</label>
      <?php $act = (string)($filters['is_active'] ?? ''); ?>
      <select name="is_active">
        <option value="" <?php echo $act===''?'selected':''; ?>>— any —</option>
        <option value="1" <?php echo $act==='1'?'selected':''; ?>>Active</option>
        <option value="0" <?php echo $act==='0'?'selected':''; ?>>Inactive</option>
      </select>
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">Target User ID</label>
      <input class="w-28" type="number" name="target_user_id" value="<?php echo htmlspecialchars((string)($filters['target_user_id'] ?? '')); ?>" placeholder="Any">
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">Author User ID</label>
      <input class="w-28" type="number" name="created_by" value="<?php echo htmlspecialchars((string)($filters['created_by'] ?? '')); ?>" placeholder="Any">
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
      <a class="btn btn-subtle" href="/admin/updates">Reset</a>
    </div>
  </form>
</div>
<div class="space-y-3">
<?php foreach (($updates ?? []) as $u): ?>
  <div class="bg-bg-component rounded-lg p-4 shadow">
    <div class="flex items-center justify-between mb-1">
      <h3 class="font-semibold text-text-heading flex-1 mr-3"><?php echo htmlspecialchars($u['title'] ?? 'Update'); ?></h3>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs <?php echo !empty($u['is_active']) ? 'badge-success' : 'badge-neutral'; ?>"><?php echo !empty($u['is_active']) ? 'Active' : 'Inactive'; ?></span>
        <span class="text-xs text-text-muted"><?php echo htmlspecialchars(substr((string)($u['created_at'] ?? ''), 0, 16)); ?></span>
      </div>
    </div>
    <div class="text-xs text-text-muted mb-2">
      <?php if (!empty($u['target_user_id'])): ?>Target: User #<?php echo (int)$u['target_user_id']; ?><?php else: ?>Target: All users<?php endif; ?>
      <?php if (!empty($u['author_username'])): ?> · By <?php echo htmlspecialchars($u['author_username']); ?><?php endif; ?>
    </div>
    <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($u['message'] ?? '')); ?></p>
    <div class="mt-3 flex items-center justify-end gap-2">
      <a class="btn btn-subtle btn-sm" href="/admin/updates/<?php echo (int)($u['id'] ?? 0); ?>/edit">Edit</a>
      <form method="post" action="/admin/updates/<?php echo (int)($u['id'] ?? 0); ?>/delete" onsubmit="return confirm('Delete this update?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php if (!empty($pagination) && is_array($pagination)): $p = $pagination; $cur = (int)($p['currentPage'] ?? 1); $tot = (int)($p['totalPages'] ?? 1); ?>
<div class="mt-4 flex items-center justify-between text-sm text-text-muted">
  <div>Page <?php echo $cur; ?> of <?php echo $tot; ?> · <?php echo (int)($p['totalItems'] ?? 0); ?> results</div>
  <div class="flex items-center gap-2">
    <?php $qs = $_GET; $qs['page'] = max(1, $cur - 1); $prevUrl = '/admin/updates' . ($qs ? ('?' . http_build_query($qs)) : ''); ?>
    <?php $qs2 = $_GET; $qs2['page'] = min($tot, $cur + 1); $nextUrl = '/admin/updates' . ($qs2 ? ('?' . http_build_query($qs2)) : ''); ?>
    <a class="btn btn-subtle btn-sm <?php echo $cur <= 1 ? 'pointer-events-none opacity-50' : ''; ?>" href="<?php echo htmlspecialchars($prevUrl); ?>">Prev</a>
    <a class="btn btn-subtle btn-sm <?php echo $cur >= $tot ? 'pointer-events-none opacity-50' : ''; ?>" href="<?php echo htmlspecialchars($nextUrl); ?>">Next</a>
  </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
