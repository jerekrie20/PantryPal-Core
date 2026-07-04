<?php
/**
 * Main application header.
 * This partial expects a $username variable to be available.
 */
global $username;

// Ensure the new avatar component is available to be used in this file.
require_once VIEW_PATH . '/Components/avatar.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
?>
<header class="bg-bg-component shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center space-x-2">
                <div style="width: 20px; height: 20px; background-color: var(--color-brand-accent); border-radius: 0 var(--radius-full) 0 var(--radius-full); transform: rotate(-45deg);" class="inline-block"></div>
                <a href="/" class="text-xl font-bold text-text-base" aria-label="PantryPal Home">PantryPal</a>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Desktop nav -->
                <a href="/dashboard" class="btn btn-subtle btn-md hidden sm:inline-flex">Dashboard</a>
                <a href="/recipes" class="btn btn-subtle btn-md hidden sm:inline-flex">Recipes</a>
                <a href="/items" class="btn btn-subtle btn-md hidden sm:inline-flex">Items</a>
                <a href="/shopping-list" class="btn btn-subtle btn-md hidden sm:inline-flex" title="Shopping List">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 7h13L17 13M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                    </svg>
                </a>
                <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="/admin" class="btn btn-subtle btn-md hidden sm:inline-flex">Admin</a>
                <?php endif; ?>
                <form action="/logout" method="POST" class="hidden sm:inline-block">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
                    <button type="submit" class="btn btn-subtle btn-md">Logout</button>
                </form>

                <!-- Mobile hamburger -->
                <button id="mobile-nav-toggle" class="inline-flex sm:hidden items-center justify-center rounded p-2 text-text-muted hover:text-text-heading focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand" aria-label="Open main menu" aria-controls="mobile-nav-panel" aria-expanded="false">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Avatar -->
                <button aria-label="User menu">
                    <?php
                    user_avatar([
                        'class' => 'h-8 w-8 text-text-muted',
                        'username' => $username
                    ]);
                    ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile dropdown panel -->
    <div id="mobile-nav-panel" class="sm:hidden hidden border-t border-border-default bg-bg-component">
        <nav class="container mx-auto px-4 py-3 space-y-1">
            <a href="/dashboard" class="block px-3 py-2 rounded text-sm font-medium hover:bg-surface-default">Dashboard</a>
            <a href="/recipes" class="block px-3 py-2 rounded text-sm font-medium hover:bg-surface-default">Recipes</a>
            <a href="/recipes?ugc=1" class="block px-3 py-2 rounded text-sm font-medium hover:bg-surface-default">My Recipes</a>
            <a href="/items" class="block px-3 py-2 rounded text-sm font-medium hover:bg-surface-default">Items</a>
            <a href="/shopping-list" class="block px-3 py-2 rounded text-sm font-medium hover:bg-surface-default">Shopping List</a>
            <?php if (!empty($_SESSION['is_admin'])): ?>
            <a href="/admin" class="block px-3 py-2 rounded text-sm font-medium hover:bg-surface-default">Admin</a>
            <?php endif; ?>
            <form action="/logout" method="POST" class="block">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
                <button type="submit" class="block w-full text-left px-3 py-2 rounded text-sm font-medium hover:bg-surface-default">Logout</button>
            </form>
        </nav>
    </div>

    <script>
    (function(){
      var btn = document.getElementById('mobile-nav-toggle');
      var panel = document.getElementById('mobile-nav-panel');
      if (!btn || !panel) return;
      function closePanel(){ panel.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); }
      btn.addEventListener('click', function(e){
        e.stopPropagation();
        var isHidden = panel.classList.contains('hidden');
        if (isHidden) { panel.classList.remove('hidden'); btn.setAttribute('aria-expanded','true'); }
        else { closePanel(); }
      });
      document.addEventListener('click', function(e){
        if (!panel.classList.contains('hidden')) {
          if (!panel.contains(e.target) && !btn.contains(e.target)) { closePanel(); }
        }
      });
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closePanel(); });
    })();
    </script>
</header>

