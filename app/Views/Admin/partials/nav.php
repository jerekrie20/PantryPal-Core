<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/'); if ($path === '') $path = '/';
$lp = strtolower($path);

$crumbs = [
    ['label' => 'Home',  'url' => '/dashboard'],
    ['label' => 'Admin', 'url' => '/admin'],
];

$section = '';
$action = '';
if (str_starts_with($lp, '/admin/users'))        { $section = 'Users'; }
elseif (str_starts_with($lp, '/admin/items'))    { $section = 'Items'; }
elseif (str_starts_with($lp, '/admin/recipes'))  {
    $section = 'Recipes';
    if ($lp === '/admin/recipes/create')                        { $action = 'Create'; }
    elseif (preg_match('#^/admin/recipes/[0-9]+/edit$#i', $path)){ $action = 'Edit'; }
}
elseif (str_starts_with($lp, '/admin/updates'))  {
    $section = 'Updates';
    if ($lp === '/admin/updates/create')                        { $action = 'Create'; }
    elseif (preg_match('#^/admin/updates/[0-9]+/edit$#i', $path)){ $action = 'Edit'; }
}

if ($section !== '') $crumbs[] = ['label' => $section, 'url' => '/admin/' . strtolower($section)];
if ($action !== '')  $crumbs[] = ['label' => $action,  'url' => null];

$tabs = [
    ['label' => 'Dashboard', 'href' => '/admin'],
    ['label' => 'Users',     'href' => '/admin/users'],
    ['label' => 'Items',     'href' => '/admin/items'],
    ['label' => 'Recipes',   'href' => '/admin/recipes'],
    ['label' => 'Updates',   'href' => '/admin/updates'],
];

$activeHref = '/admin';
foreach ($tabs as $t) {
    if ($t['href'] !== '/admin' && str_starts_with($lp, rtrim(strtolower($t['href']), '/'))) {
        $activeHref = $t['href'];
        break;
    }
}
if ($lp === '/admin') $activeHref = '/admin';

$showAddRecipe  = str_starts_with($lp, '/admin/recipes');
$showPostUpdate = str_starts_with($lp, '/admin/updates');
?>

<!-- Breadcrumbs -->
<nav class="mb-4" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1 text-sm text-text-muted">
        <?php foreach ($crumbs as $i => $c): $last = $i === count($crumbs) - 1; ?>
            <?php if (!empty($c['url']) && !$last): ?>
                <li><a class="hover:text-text-heading" href="<?= htmlspecialchars($c['url']) ?>"><?= htmlspecialchars($c['label']) ?></a></li>
            <?php else: ?>
                <li aria-current="page" class="text-text-heading font-medium"><?= htmlspecialchars($c['label']) ?></li>
            <?php endif; ?>
            <?php if (!$last): ?><li class="text-text-subtle">/</li><?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>

<!-- Sub-nav tabs + contextual actions -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div class="overflow-x-auto -mx-1 px-1">
        <div role="tablist" aria-label="Admin sections" class="inline-flex bg-bg-subtle rounded-lg p-1 gap-1">
            <?php foreach ($tabs as $t): $isActive = ($activeHref === $t['href']); ?>
                <a role="tab" href="<?= htmlspecialchars($t['href']) ?>" aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                   class="<?= $isActive
                        ? 'px-3 py-1.5 rounded-md text-sm font-semibold whitespace-nowrap bg-bg-component text-text-heading shadow-sm'
                        : 'px-3 py-1.5 rounded-md text-sm font-semibold whitespace-nowrap text-text-muted hover:text-text-heading' ?>">
                    <?= htmlspecialchars($t['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="flex gap-2 justify-end">
        <?php if ($showAddRecipe): ?>
            <a class="btn btn-cta btn-sm" href="/admin/recipes/create">+ Add recipe</a>
        <?php endif; ?>
        <?php if ($showPostUpdate): ?>
            <a class="btn btn-cta btn-sm" href="/admin/updates/create">+ Post update</a>
        <?php endif; ?>
    </div>
</div>
