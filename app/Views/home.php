<?php
require_once VIEW_PATH . '/Components/ui_elements.php';
include VIEW_PATH . '/Layouts/Guest/header.php';
?>

<!-- Hero -->
<section class="section-y hero-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <?php ui_hero(
            'Cook what you have. Waste less. Spend less.',
            'PantryPal tracks what\'s in your kitchen, surfaces recipes you can make tonight, and turns your shopping list into something that actually pulls its weight.',
            [
                ['text' => 'Start free', 'href' => '/register', 'variant' => 'cta'],
                ['text' => 'See how it works', 'href' => '#how-it-works', 'variant' => 'secondary'],
            ],
            'Smart pantry · free to start'
        ); ?>

        <!-- Trust strip -->
        <div class="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-4xl mx-auto">
            <div class="text-center">
                <p class="font-display text-4xl text-text-heading">$1,500</p>
                <p class="text-sm text-text-muted mt-1">avg US household food waste / year</p>
            </div>
            <div class="text-center">
                <p class="font-display text-4xl text-text-heading">40%</p>
                <p class="text-sm text-text-muted mt-1">of US food never gets eaten</p>
            </div>
            <div class="text-center">
                <p class="font-display text-4xl text-text-heading">5 min</p>
                <p class="text-sm text-text-muted mt-1">to set up your first pantry</p>
            </div>
            <div class="text-center">
                <p class="font-display text-4xl text-text-heading">Free</p>
                <p class="text-sm text-text-muted mt-1">no credit card required</p>
            </div>
        </div>
    </div>
</section>

<!-- How it works -->
<section id="how-it-works" class="section-y bg-bg-component">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <p class="eyebrow mb-3">How it works</p>
            <h2 class="text-text-heading">From grocery bag to dinner — in three steps.</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <div class="card relative">
                <div class="absolute -top-3 -left-3 w-10 h-10 rounded-full flex items-center justify-center font-display text-lg font-semibold" style="background: var(--color-brand-100); color: var(--color-brand-700); border: 1px solid var(--color-brand-200);">1</div>
                <h3 class="mt-2 mb-2">Scan or type</h3>
                <p class="text-text-muted">Add items by barcode, voice, or quick search. Expiration dates are filled in automatically when we know them.</p>
            </div>
            <div class="card relative">
                <div class="absolute -top-3 -left-3 w-10 h-10 rounded-full flex items-center justify-center font-display text-lg font-semibold" style="background: var(--color-brand-100); color: var(--color-brand-700); border: 1px solid var(--color-brand-200);">2</div>
                <h3 class="mt-2 mb-2">See what to cook</h3>
                <p class="text-text-muted">Get recipes ranked by what you already own, sorted so the items about to expire get used first.</p>
            </div>
            <div class="card relative">
                <div class="absolute -top-3 -left-3 w-10 h-10 rounded-full flex items-center justify-center font-display text-lg font-semibold" style="background: var(--color-brand-100); color: var(--color-brand-700); border: 1px solid var(--color-brand-200);">3</div>
                <h3 class="mt-2 mb-2">Shop on purpose</h3>
                <p class="text-text-muted">Your shopping list auto-fills with what's missing for the week's plan — no duplicates, no forgotten staples.</p>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section id="features" class="section-y bg-bg-subtle">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <p class="eyebrow mb-3">Features</p>
            <h2 class="text-text-heading">Everything you need to stop throwing food away.</h2>
            <p class="lede mt-4 mx-auto">A pantry tracker that earns its keep — by surfacing what to cook before it spoils, and by knowing exactly what's left in the fridge when you're at the store.</p>
        </div>

        <?php ui_feature_grid([
            ['icon' => '🛒', 'title' => 'Barcode + manual entry', 'body' => 'Scan a product to add it in seconds, or type it in. Brand and category fill themselves in.'],
            ['icon' => '⏰', 'title' => 'Expiration tracking', 'body' => 'See what\'s expiring this week at a glance. Optional alerts let you act before things spoil.'],
            ['icon' => '🍳', 'title' => 'Cook tonight', 'body' => 'Recipes ranked by what you already own — and what\'s about to expire.'],
            ['icon' => '📋', 'title' => 'Smart shopping list', 'body' => 'Auto-deduped against your pantry. Add a recipe; the missing ingredients appear in your list.'],
            ['icon' => '🥕', 'title' => 'Categories & quantities', 'body' => 'Organize by aisle, fridge, or freezer. Track units that match how you actually shop.'],
            ['icon' => '🤖', 'title' => 'AI cooking assistant', 'body' => 'Ask "what can I make for dinner?" or "substitute for buttermilk?" — answered from your real pantry.'],
        ]); ?>
    </div>
</section>

<!-- Closing CTA -->
<section class="section-y bg-bg-component">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="card text-center max-w-3xl mx-auto py-12 hero-bg">
            <p class="eyebrow mb-3">Ready when you are</p>
            <h2 class="text-text-heading">Spend less on groceries this month.</h2>
            <p class="lede mt-4 mx-auto">Free to start. Five minutes to set up. No card required.</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="/register" class="btn btn-cta btn-lg">Create your free account</a>
                <a href="/login" class="btn btn-secondary btn-lg">Log in</a>
            </div>
        </div>
    </div>
</section>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>
