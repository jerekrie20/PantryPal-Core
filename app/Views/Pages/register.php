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
                    <h1 class="section-title text-3xl font-bold">Create an Account</h1>
                </div>
                <p class="mt-[var(--spacing-2)] text-gray-600">Create an account</p>
            </div>

            <div class="text-center mb-[var(--spacing-8)]">
                <?php if (!empty($errors)) {
                    foreach ($errors as $field => $message) {
                        ?>
                        <p class="mt-[var(--spacing-2)] text-gray-600"><?php echo htmlspecialchars($field) . ": " . htmlspecialchars($message) . "<br>"; ?></p>
                    <?php }
                } ?>
            </div>

            <form action="/register" method="POST" class="space-y-[var(--spacing-6)]">

                <?php
                form_input(
                        id: 'username',
                        type: 'text',
                        label: 'Username',
                        placeholder: 'pal34',
                        value: $input['username'] ?? ''
                );
                ?>

                <?php
                form_input(
                        id: 'email',
                        type: 'email',
                        label: 'Email Address',
                        placeholder: 'you@example.com',
                        value: $input['email'] ?? ''
                );
                ?>


                <?php
                form_input(
                        id: 'password',
                        type: 'password',
                        label: 'Password',
                        placeholder: '••••••••',
                        value: $input['password'] ?? ''
                );
                ?>


                <?php
                form_input(
                        id: 'password_confirm',
                        type: 'password',
                        label: 'Confirm Password',
                        placeholder: '••••••••',
                        value: $input['password_confirm'] ?? ''
                );
                ?>


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
                    Have an account?
                    <a href="/login" class="font-medium">
                        Login Here
                    </a>
                </p>
            </div>
        </div>

    </div>
</main>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>


