<?php
require_once VIEW_PATH . '/Components/form_elements.php';
include VIEW_PATH . '/Layouts/Guest/header.php';
?>

<main class="section-y hero-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center max-w-5xl mx-auto">

            <!-- Value prop (desktop only) -->
            <aside class="hidden lg:block">
                <p class="eyebrow mb-3">Welcome back</p>
                <h1 class="text-text-heading">Cook what you have. Waste less. Spend less.</h1>
                <p class="lede mt-6">Pick up where you left off — your pantry, recipes, and shopping list are right where you set them.</p>
                <ul class="mt-8 space-y-3 text-text-base">
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        <span>Track what's in your kitchen, expiration and all.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        <span>Get recipes based on what you already own.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        <span>A shopping list that knows what you're missing.</span>
                    </li>
                </ul>
            </aside>

            <!-- Form -->
            <div class="card p-6 md:p-8 w-full max-w-md mx-auto lg:max-w-none">
                <div class="mb-6">
                    <h2 class="text-text-heading">Log in</h2>
                    <p class="text-text-muted mt-1 text-sm">Sign in to manage your pantry.</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert-danger mb-4" role="alert">
                        <?= htmlspecialchars((string)$error) ?>
                    </div>
                <?php endif; ?>

                <form action="/login" method="POST" class="space-y-5">
                    <?php echo csrf_field(); ?>

                    <?php form_input('email', 'Email', 'email', [
                        'placeholder' => 'you@example.com',
                        'required'    => true,
                        'value'       => $input['email'] ?? '',
                        'error'       => $errors['email'] ?? null,
                    ]); ?>

                    <div>
                        <?php form_input('password', 'Password', 'password', [
                            'placeholder' => '••••••••',
                            'required'    => true,
                            'error'       => $errors['password'] ?? null,
                        ]); ?>
                        <div class="text-right mt-2">
                            <a href="/forgot-password" class="text-sm">Forgot password?</a>
                        </div>
                    </div>

                    <?php form_button('Log in', 'cta', ['size' => 'lg', 'fullWidth' => true]); ?>
                </form>

                <p class="mt-6 text-center text-sm text-text-muted">
                    New to PantryPal? <a href="/register" class="font-semibold">Create a free account</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>
