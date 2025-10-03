<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/'); if ($path === '') $path = '/';
$lp = strtolower($path);

// Build breadcrumbs
$crumbs = [];
$crumbs[] = ['label' => 'Home', 'url' => '/dashboard'];
$crumbs[] = ['label' => 'Admin', 'url' => '/admin'];

$section = '';
$action = '';
if (str_starts_with($lp, '/admin/users')) { $section = 'Users'; }
elseif (str_starts_with($lp, '/admin/items')) { $section = 'Items'; }
elseif (str_starts_with($lp, '/admin/recipes')) {
    $section = 'Recipes';
    if ($lp === '/admin/recipes/create') { $action = 'Create'; }
    elseif (preg_match('#^/admin/recipes/[0-9]+/edit$#i', $path)) { $action = 'Edit'; }
}
elseif (str_starts_with($lp, '/admin/updates')) {
    $section = 'Updates';
    if ($lp === '/admin/updates/create') { $action = 'Create'; }
    elseif (preg_match('#^/admin/updates/[0-9]+/edit$#i', $path)) { $action = 'Edit'; }
}

if ($section !== '') {
    $crumbs[] = ['label' => $section, 'url' => '/admin/' . strtolower($section)];
}
if ($action !== '') {
    $crumbs[] = ['label' => $action, 'url' => null];
}

// Determine active tab for sub menu
$tabs = [
    ['label' => 'Dashboard', 'href' => '/admin'],
    ['label' => 'Users', 'href' => '/admin/users'],
    ['label' => 'Items', 'href' => '/admin/items'],
    ['label' => 'Recipes', 'href' => '/admin/recipes'],
    ['label' => 'Updates', 'href' => '/admin/updates'],
];

$activeHref = '/admin';
foreach ($tabs as $t) {
    if ($t['href'] !== '/admin' && str_starts_with($lp, rtrim(strtolower($t['href']), '/'))) {
        $activeHref = $t['href'];
        break;
    }
}
if ($lp === '/admin') { $activeHref = '/admin'; }

// Contextual actions
$showAddRecipe = str_starts_with($lp, '/admin/recipes');
$showPostUpdate = str_starts_with($lp, '/admin/updates');
?>

<!-- Admin breadcrumbs + sub menu -->
<nav class="mb-4" aria-label="Breadcrumb">
  <ol class="flex flex-wrap items-center gap-1 text-sm text-text-muted">
    <?php foreach ($crumbs as $i => $c): $last = $i === count($crumbs)-1; ?>
      <?php if (!empty($c['url']) && !$last): ?>
        <li><a class="hover:text-text-heading" href="<?php echo htmlspecialchars($c['url']); ?>"><?php echo htmlspecialchars($c['label']); ?></a></li>
      <?php else: ?>
        <li aria-current="page" class="text-text-heading font-medium"><?php echo htmlspecialchars($c['label']); ?></li>
      <?php endif; ?>
      <?php if (!$last): ?><li class="opacity-60">/</li><?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
  <div class="overflow-x-auto -mx-1 px-1">
    <div role="tablist" aria-label="Admin sections" class="flex gap-1">
      <?php foreach ($tabs as $t): $isActive = ($activeHref === $t['href']); ?>
        <a role="tab" href="<?php echo htmlspecialchars($t['href']); ?>"
           aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>"
           class="px-3 py-2 rounded-md text-sm font-medium whitespace-nowrap <?php echo $isActive ? 'bg-surface-default text-text-heading' : 'text-text-muted hover:text-text-heading'; ?>">
          <?php echo htmlspecialchars($t['label']); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="flex gap-2 justify-end">
    <?php if ($showAddRecipe): ?><a class="btn btn-subtle btn-sm" href="/admin/recipes/create">Add Recipe</a><?php endif; ?>
    <?php if ($showPostUpdate): ?><a class="btn btn-subtle btn-sm" href="/admin/updates/create">Post Update</a><?php endif; ?>
  </div>
</div>
