<?php
require_once VIEW_PATH . '/Components/ui_elements.php';
include VIEW_PATH . '/Layouts/Guest/header.php';
?>

<section class="section-y hero-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <?php ui_hero(
            'Reduce waste, save money, eat well.',
            'PantryPal is a smart pantry that keeps track of what you have, what\'s expiring, and what you can cook with it.',
            [],
            'About PantryPal'
        ); ?>
    </div>
</section>

<section class="section-y bg-bg-component">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-start max-w-5xl mx-auto">
            <div class="space-y-5">
                <p class="eyebrow">Why we built PantryPal</p>
                <h2 class="text-text-heading">Most households throw away food they forgot they owned.</h2>
                <p class="text-text-base leading-relaxed">Not for lack of effort — kitchens are chaotic. PantryPal makes it easy to keep track of what you have and when it expires, so you can cook more and waste less.</p>
                <p class="text-text-base leading-relaxed">We pair a simple inventory with recipe discovery so you can quickly find meal ideas that use ingredients already in your kitchen — not another shopping trip.</p>

                <h3 class="text-text-heading mt-8">What you can do</h3>
                <ul class="space-y-2 text-text-base">
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        Track items with purchase and expiration dates
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        Organize by category and quantity
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        See what's expiring soon at a glance
                    </li>
                    <li class="flex gap-3">
                        <span class="inline-flex shrink-0 mt-0.5 w-5 h-5 rounded-full items-center justify-center text-xs font-bold" style="background: var(--color-accent-100); color: var(--color-accent-700);">✓</span>
                        Discover recipes using what you have on hand
                    </li>
                </ul>
            </div>
            <div>
                <img src="/images/home/pantry.webp" alt="An organized pantry" class="rounded-2xl shadow-lg w-full h-auto object-cover" />
            </div>
        </div>
    </div>
</section>

<section class="section-y bg-bg-subtle">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <p class="eyebrow mb-3">Privacy and data</p>
            <h2 class="text-text-heading mb-4">Your pantry stays yours.</h2>
            <p class="text-text-base leading-relaxed">We don't sell your personal data. Recipe results may come from third-party providers so you can discover new dishes, but your saved items and personal notes never leave PantryPal.</p>
        </div>
    </div>
</section>

<section class="section-y bg-bg-component">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="card text-center max-w-3xl mx-auto py-12 hero-bg">
            <p class="eyebrow mb-3">Get started</p>
            <h2 class="text-text-heading">Free. No card. Five minutes to set up.</h2>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="/register" class="btn btn-cta btn-lg">Create your free account</a>
                <a href="/login" class="btn btn-secondary btn-lg">Log in</a>
            </div>
        </div>
    </div>
</section>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>
