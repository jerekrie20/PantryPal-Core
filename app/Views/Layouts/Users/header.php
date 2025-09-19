<?php
/**
 * Main application header.
 * This partial expects a $username variable to be available.
 */
global $username;

// Ensure the new avatar component is available to be used in this file.
require_once VIEW_PATH . '/Components/avatar.php';
?>
<header class="bg-bg-component shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center space-x-2">
                <div style="width: var(--spacing-5); height: var(--spacing-5); background-color: var(--color-brand-accent); border-radius: 0 var(--radius-full) 0 var(--radius-full); transform: rotate(-45deg);" class="inline-block"></div>
                <a href="/" class="text-xl font-bold text-text-base" aria-label="PantryPal Home">PantryPal</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="/recipes" class="btn btn-subtle btn-md hidden sm:inline-flex">Recipes</a>
                <a href="/logout" class="btn btn-subtle btn-md hidden sm:inline-flex">Logout</a>
                <button aria-label="User menu">
                    <?php
                    // Call the new SVG avatar component instead of using an <img> tag.
                    // You can easily style it by changing the classes passed to it.
                    user_avatar([
                            'class' => 'h-8 w-8 text-text-muted',
                            'username' => $username
                    ]);
                    ?>
                </button>
            </div>
        </div>
    </div>
</header>

