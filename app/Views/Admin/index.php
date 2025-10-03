<?php
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold text-text-heading">Admin Dashboard</h1>
  <div class="hidden sm:flex gap-2">
    <a class="btn btn-subtle btn-sm" href="/admin/recipes/create">Add Recipe</a>
    <a class="btn btn-subtle btn-sm" href="/admin/updates/create">Post Update</a>
  </div>
</div>

<!-- Quick actions grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
  <a href="/admin/users" class="block bg-bg-component rounded-xl p-5 shadow hover:shadow-md transition">
    <div class="text-sm text-text-muted mb-1">Manage</div>
    <div class="text-xl font-semibold text-text-heading">Users</div>
    <div class="mt-2 text-text-muted">View all users and roles</div>
  </a>
  <a href="/admin/items" class="block bg-bg-component rounded-xl p-5 shadow hover:shadow-md transition">
    <div class="text-sm text-text-muted mb-1">Audit</div>
    <div class="text-xl font-semibold text-text-heading">Items</div>
    <div class="mt-2 text-text-muted">Browse all pantry items</div>
  </a>
  <a href="/admin/recipes" class="block bg-bg-component rounded-xl p-5 shadow hover:shadow-md transition">
    <div class="text-sm text-text-muted mb-1">Content</div>
    <div class="text-xl font-semibold text-text-heading">Recipes</div>
    <div class="mt-2 text-text-muted">List and curate recipes</div>
  </a>
  <a href="/admin/recipes/create" class="block bg-bg-component rounded-xl p-5 shadow hover:shadow-md transition">
    <div class="text-sm text-text-muted mb-1">Create</div>
    <div class="text-xl font-semibold text-text-heading">Add Recipe</div>
    <div class="mt-2 text-text-muted">Add a manual recipe entry</div>
  </a>
  <a href="/admin/updates" class="block bg-bg-component rounded-xl p-5 shadow hover:shadow-md transition">
    <div class="text-sm text-text-muted mb-1">Communicate</div>
    <div class="text-xl font-semibold text-text-heading">Updates</div>
    <div class="mt-2 text-text-muted">View announcements</div>
  </a>
  <a href="/admin/updates/create" class="block bg-bg-component rounded-xl p-5 shadow hover:shadow-md transition">
    <div class="text-sm text-text-muted mb-1">Post</div>
    <div class="text-xl font-semibold text-text-heading">New Update</div>
    <div class="mt-2 text-text-muted">Send a message to users</div>
  </a>
</div>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
