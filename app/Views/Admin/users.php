<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>
<div class="flex flex-col gap-3 mb-4">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">All Users</h1>
  </div>
  <!-- Filters -->
  <?php $filters = $filters ?? []; ?>
  <form method="get" action="/admin/users" class="bg-bg-component rounded-lg p-3 shadow flex flex-col sm:flex-row gap-3">
    <div class="flex-1">
      <label class="block text-xs text-text-muted mb-1">Search</label>
      <input class="input w-full" type="text" name="q" placeholder="Username or email" value="<?php echo htmlspecialchars((string)($filters['q'] ?? '')); ?>">
    </div>
    <div>
      <label class="block text-xs text-text-muted mb-1">Role</label>
      <?php $role = (string)($filters['role'] ?? ''); ?>
      <select class="input" name="role">
        <option value="" <?php echo $role===''?'selected':''; ?>>— any —</option>
        <option value="admin" <?php echo $role==='admin'?'selected':''; ?>>Admin</option>
        <option value="user" <?php echo $role==='user'?'selected':''; ?>>User</option>
      </select>
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
      <a class="btn btn-subtle" href="/admin/users">Reset</a>
    </div>
  </form>
</div>

<!-- Mobile cards -->
<div class="sm:hidden space-y-3 mb-4">
  <?php foreach (($users ?? []) as $u): ?>
    <div class="bg-bg-component rounded-lg p-4 shadow">
      <div class="flex items-center justify-between">
        <div class="font-semibold text-text-heading">
          <?php echo htmlspecialchars($u['username'] ?? ''); ?>
        </div>
        <span class="text-xs px-2 py-0.5 rounded-full <?php echo !empty($u['is_admin']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'; ?>">
          <?php echo !empty($u['is_admin']) ? 'Admin' : 'User'; ?>
        </span>
      </div>
      <div class="text-sm text-text-muted mt-1">ID #<?php echo (int)$u['id']; ?> · <?php echo htmlspecialchars((string)($u['created_at'] ?? '')); ?></div>
      <div class="mt-2 text-sm"><a class="text-link" href="mailto:<?php echo htmlspecialchars($u['email'] ?? ''); ?>"><?php echo htmlspecialchars($u['email'] ?? ''); ?></a></div>
      <div class="mt-3 flex gap-2">
        <form method="post" action="/admin/users/<?php echo (int)$u['id']; ?>/toggle-admin">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <button class="btn btn-subtle btn-sm" type="submit"><?php echo !empty($u['is_admin']) ? 'Revoke Admin' : 'Make Admin'; ?></button>
        </form>
        <form method="post" action="/admin/users/<?php echo (int)$u['id']; ?>/delete" onsubmit="return confirm('Delete this user? This cannot be undone.');">
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
  <th class="px-4 py-2">Username</th>
  <th class="px-4 py-2">Email</th>
  <th class="px-4 py-2">Role</th>
  <th class="px-4 py-2">Created</th>
  <th class="px-4 py-2">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach (($users ?? []) as $u): ?>
<tr class="border-b border-border-default">
  <td class="px-4 py-2"><?php echo (int)$u['id']; ?></td>
  <td class="px-4 py-2"><?php echo htmlspecialchars($u['username'] ?? ''); ?></td>
  <td class="px-4 py-2"><a class="text-link" href="mailto:<?php echo htmlspecialchars($u['email'] ?? ''); ?>"><?php echo htmlspecialchars($u['email'] ?? ''); ?></a></td>
  <td class="px-4 py-2">
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs <?php echo !empty($u['is_admin']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'; ?>">
      <?php echo !empty($u['is_admin']) ? 'Admin' : 'User'; ?>
    </span>
  </td>
  <td class="px-4 py-2"><?php echo htmlspecialchars((string)($u['created_at'] ?? '')); ?></td>
  <td class="px-4 py-2">
    <div class="flex items-center gap-2">
      <form method="post" action="/admin/users/<?php echo (int)$u['id']; ?>/toggle-admin">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <button class="btn btn-subtle btn-xs" type="submit"><?php echo !empty($u['is_admin']) ? 'Revoke' : 'Make Admin'; ?></button>
      </form>
      <form method="post" action="/admin/users/<?php echo (int)$u['id']; ?>/delete" onsubmit="return confirm('Delete this user? This cannot be undone.');">
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
    <?php $qs = $_GET; $qs['page'] = max(1, $cur - 1); $prevUrl = '/admin/users' . ($qs ? ('?' . http_build_query($qs)) : ''); ?>
    <?php $qs2 = $_GET; $qs2['page'] = min($tot, $cur + 1); $nextUrl = '/admin/users' . ($qs2 ? ('?' . http_build_query($qs2)) : ''); ?>
    <a class="btn btn-subtle btn-xs <?php echo $cur <= 1 ? 'pointer-events-none opacity-50' : ''; ?>" href="<?php echo htmlspecialchars($prevUrl); ?>">Prev</a>
    <a class="btn btn-subtle btn-xs <?php echo $cur >= $tot ? 'pointer-events-none opacity-50' : ''; ?>" href="<?php echo htmlspecialchars($nextUrl); ?>">Next</a>
  </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
