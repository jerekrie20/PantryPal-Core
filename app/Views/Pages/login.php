<?php require_once VIEW_PATH . '/Components/form_elements.php'; ?>
<?php include VIEW_PATH . '/Layouts/Guest/header.php'; ?>

<div
        class="relative h-64 bg-cover bg-center rounded-lg overflow-hidden bg-[image:var(--hero-img)]"
        style="--hero-img: url('<?= e(asset('images/home/pantry.webp')) ?>')"
>

</div>

<main class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">

        <div class="card p-6 md:p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center">
                    <div style="width: var(--spacing-5); height: var(--spacing-5); background-color: var(--color-brand-accent); border-radius: 0 var(--radius-full) 0 var(--radius-full); transform: rotate(-45deg);" class="inline-block mr-2"></div>
                    <h1 class="text-3xl font-bold text-text-heading">Welcome Back</h1>
                </div>
                <p class="mt-2 text-text-muted">Sign in to manage your pantry.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert-danger mb-4" role="alert">
                    <?php echo htmlspecialchars((string)$error); ?>
                </div>
            <?php endif; ?>

            <form action="/login" method="POST" class="space-y-6">

                <?php
                form_input('email', 'Email Address', 'email', [
                        'placeholder' => 'you@example.com',
                        'required' => true,
                        'value' => $input['email'] ?? '',
                        'error' => $errors['email'] ?? null
                ]);
                ?>

                <div>
                    <?php
                    form_input('password', 'Password', 'password', [
                            'placeholder' => '••••••••',
                            'required' => true,
                            'error' => $errors['password'] ?? null
                    ]);
                    ?>
                    <div class="text-right mt-2">
                        <a href="/forgot-password" class="text-sm font-medium">
                            Forgot Password?
                        </a>
                    </div>
                </div>

                <?php
                form_button('Sign In', 'cta', [
                        'size' => 'lg',
                        'fullWidth' => true
                ]);
                ?>

            </form>

            <div class="mt-6 text-center text-sm text-text-muted">
                <p>
                    Don't have an account?
                    <a href="/register" class="font-medium">
                        Sign up here
                    </a>
                </p>
            </div>
        </div>

    </div>
</main>


<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>


