<?php
require_once VIEW_PATH . '/Components/form_elements.php';
include VIEW_PATH . '/Layouts/Guest/header.php';

$errors = $errors ?? [];
$input = $input ?? [];
?>


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
                    <h1 class="text-3xl font-bold text-text-heading">Create an Account</h1>
                </div>
                <p class="mt-2 text-text-muted">Join PantryPal to start managing your pantry today.</p>
            </div>

            <form action="/register" method="POST" class="space-y-6">

                <?php
                form_input('username', 'Username', 'text', [
                        'placeholder' => 'e.g., pantrypal123',
                        'required' => true,
                        'value' => $input['username'] ?? '',
                        'error' => $errors['username'] ?? null
                ]);

                form_input('email', 'Email Address', 'email', [
                        'placeholder' => 'you@example.com',
                        'required' => true,
                        'value' => $input['email'] ?? '',
                        'error' => $errors['email'] ?? null
                ]);

                form_input('password', 'Password', 'password', [
                        'placeholder' => '••••••••',
                        'required' => true,
                        'error' => $errors['password'] ?? null
                ]);

                form_input('password_confirm', 'Confirm Password', 'password', [
                        'placeholder' => '••••••••',
                        'required' => true,
                        'error' => $errors['password_confirm'] ?? null
                ]);
                ?>

                <div class="pt-2">
                    <?php
                    form_button('Create Account', 'cta', [
                            'size' => 'lg',
                            'fullWidth' => true
                    ]);
                    ?>
                </div>

            </form>

            <div class="mt-6 text-center text-sm text-text-muted">
                <p>
                    Already have an account?
                    <a href="/login" class="font-medium">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>

    </div>
</main>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>


