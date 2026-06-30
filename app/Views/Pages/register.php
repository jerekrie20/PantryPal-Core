<?php
require_once VIEW_PATH . '/Components/form_elements.php';
include VIEW_PATH . '/Layouts/Guest/header.php';

$errors = $errors ?? [];
$input  = $input ?? [];
?>

<main class="section-y hero-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center max-w-5xl mx-auto">

            <!-- Value prop (desktop only) -->
            <aside class="hidden lg:block">
                <p class="eyebrow mb-3">Free to start</p>
                <h1 class="text-text-heading">Start cooking with what's already in your kitchen.</h1>
                <p class="lede mt-6">Free forever. No credit card. Five minutes to set up.</p>
                <ul class="mt-8 space-y-3 text-text-base">
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        <span>Track unlimited pantry items.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        <span>Get recipe suggestions based on what's expiring.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        <span>Build smarter shopping lists that pull their weight.</span>
                    </li>
                </ul>
            </aside>

            <!-- Form -->
            <div class="card p-6 md:p-8 w-full max-w-md mx-auto lg:max-w-none">
                <div class="mb-6">
                    <h2 class="text-text-heading">Create your account</h2>
                    <p class="text-text-muted mt-1 text-sm">Takes about a minute. Cancel anytime.</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert-danger mb-4" role="alert">
                        <?= htmlspecialchars((string)$error) ?>
                    </div>
                <?php endif; ?>

                <form action="/register" method="POST" class="space-y-5">
                    <?php echo csrf_field(); ?>

                    <?php form_input('username', 'Username', 'text', [
                        'placeholder' => 'e.g. pantrypal123',
                        'required'    => true,
                        'value'       => $input['username'] ?? '',
                        'error'       => $errors['username'] ?? null,
                    ]); ?>

                    <?php form_input('email', 'Email', 'email', [
                        'placeholder' => 'you@example.com',
                        'required'    => true,
                        'value'       => $input['email'] ?? '',
                        'error'       => $errors['email'] ?? null,
                    ]); ?>

                    <?php form_input('password', 'Password', 'password', [
                        'placeholder' => 'At least 8 characters',
                        'required'    => true,
                        'error'       => $errors['password'] ?? null,
                    ]); ?>

                    <?php form_input('password_confirm', 'Confirm password', 'password', [
                        'placeholder' => '••••••••',
                        'required'    => true,
                        'error'       => $errors['password_confirm'] ?? null,
                    ]); ?>

                    <?php form_button('Create free account', 'cta', ['size' => 'lg', 'fullWidth' => true]); ?>

                    <p class="text-xs text-text-muted text-center mt-3">
                        By creating an account you agree to our terms. Your data stays yours.
                    </p>
                </form>

                <p class="mt-6 text-center text-sm text-text-muted">
                    Already have an account? <a href="/login" class="font-semibold">Log in</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>
