<?php require_once VIEW_PATH . '/Components/form_elements.php'; ?>
<?php include VIEW_PATH . '/Layouts/Guest/header.php'; ?>

<div
        class="relative h-64 bg-cover bg-center rounded-lg overflow-hidden bg-[image:var(--hero-img)]"
        style="--hero-img: url('<?= e(asset('images/home/pantry.webp')) ?>')"
>

</div>

<main class="flex items-center justify-center  py-[var(--spacing-12)]">
    <div class="w-full max-w-md">

        <div class="card">
            <div class="text-center mb-[var(--spacing-8)]">
                <div class="inline-flex items-center justify-center">
                    <div class="logo-leaf mr-[var(--spacing-2)]"></div>
                    <h1 class="section-title text-3xl font-bold">Welcome Back</h1>
                </div>
                <p class="mt-[var(--spacing-2)] text-gray-600">Sign in to manage your pantry.</p>
            </div>

            <form action="/login" method="POST" class="space-y-[var(--spacing-6)]">

                <?php
                form_input(
                    id: 'email',
                    type: 'email',
                    label: 'Email Address',
                    placeholder: 'you@example.com'
                );
                ?>

                <div>
                    <?php
                    form_input(
                        id: 'password',
                        type: 'password',
                        label: 'Password',
                        placeholder: '••••••••'
                    );
                    ?>
                    <div class="text-right mt-[var(--spacing-2)]">
                        <a href="/forgot-password.php" class="text-sm font-medium">
                            Forgot Password?
                        </a>
                    </div>
                </div>

                <?php
                form_button(
                    text: 'Sign In',
                    type: 'submit',
                    size: 'lg'
                );
                ?>

            </form>

            <div class="mt-[var(--spacing-6)] text-center text-sm text-gray-600">
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


