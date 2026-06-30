<?php
/**
 * Design system style guide — visual reference for every primitive.
 * Gated by /__internal/styleguide (admin + INTERNAL_TOOLS_ENABLED).
 */
require_once VIEW_PATH . '/Components/ui_elements.php';
require_once VIEW_PATH . '/Components/form_elements.php';
include VIEW_PATH . '/Layouts/Guest/header.php';
?>

<section class="section-y hero-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <?php ui_hero(
            'PantryPal Design System',
            'Spice Market palette — terracotta brand, sage accent, warm cream surfaces. Fraunces display + Inter body.',
            [
                ['text' => 'Jump to components', 'href' => '#components', 'variant' => 'cta'],
                ['text' => 'Color tokens', 'href' => '#tokens', 'variant' => 'secondary'],
            ],
            'Style guide'
        ); ?>
    </div>
</section>

<!-- Typography -->
<section class="section-y bg-bg-component">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <p class="eyebrow mb-3">Typography</p>
        <h2 class="text-text-heading mb-6">Type scale</h2>

        <div class="card mb-6">
            <h1>The quick brown fox · h1 (Fraunces)</h1>
            <h2 class="mt-4">Section headline · h2 (Fraunces)</h2>
            <h3 class="mt-4">Subsection title · h3 (Fraunces)</h3>
            <h4 class="mt-4">Card heading · h4 (Inter 600)</h4>
            <h5 class="mt-4">Small heading · h5 (Inter 600)</h5>
            <p class="mt-6">Body — Inter regular. Effortlessly track your groceries, monitor expiration dates, and discover recipes with what you have on hand.</p>
            <p class="lede mt-4">Lede — larger, muted intro paragraph for marketing pages and section headers.</p>
            <p class="text-text-muted mt-2 text-sm">Small muted text — captions, helper text, metadata.</p>
        </div>
    </div>
</section>

<!-- Color tokens -->
<section id="tokens" class="section-y bg-bg-subtle">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <p class="eyebrow mb-3">Color tokens</p>
        <h2 class="text-text-heading mb-6">Palette</h2>

        <h3 class="mb-4">Brand — terracotta</h3>
        <div class="grid grid-cols-3 sm:grid-cols-6 lg:grid-cols-9 gap-2 mb-10">
            <?php foreach (['50','100','200','300','400','500','600','700','800','900'] as $stop): ?>
                <div>
                    <div class="aspect-square rounded-lg" style="background: var(--color-brand-<?= $stop ?>);"></div>
                    <p class="text-xs text-text-muted mt-1 text-center">brand-<?= $stop ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="mb-4">Accent — sage</h3>
        <div class="grid grid-cols-3 sm:grid-cols-6 lg:grid-cols-9 gap-2 mb-10">
            <?php foreach (['50','100','200','300','400','500','600','700','800','900'] as $stop): ?>
                <div>
                    <div class="aspect-square rounded-lg" style="background: var(--color-accent-<?= $stop ?>);"></div>
                    <p class="text-xs text-text-muted mt-1 text-center">accent-<?= $stop ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="mb-4">Surfaces & text</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-10">
            <?php
            $surfaces = [
                'bg-page' => '#FFFBF5',
                'bg-subtle' => '#FAF5EC',
                'bg-component' => '#FFFFFF',
                'border-default' => '#E7E5E4',
            ];
            foreach ($surfaces as $name => $hex): ?>
                <div class="card">
                    <div class="aspect-video rounded border border-border-default" style="background: var(--color-<?= $name ?>);"></div>
                    <p class="text-sm font-semibold mt-2"><?= $name ?></p>
                    <p class="text-xs text-text-muted font-mono"><?= $hex ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="mb-4">Status</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <?php foreach (['success', 'warning', 'danger', 'info'] as $s): ?>
                <div class="alert alert-<?= $s ?>">
                    <p class="font-semibold capitalize"><?= $s ?></p>
                    <p class="text-sm mt-1">Body copy in a <?= $s ?> alert.</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Components -->
<section id="components" class="section-y bg-bg-component">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        <div>
            <p class="eyebrow mb-3">Components</p>
            <h2 class="text-text-heading mb-6">Buttons</h2>
            <div class="card space-y-6">
                <div>
                    <p class="text-sm font-semibold mb-3">Variants (md)</p>
                    <div class="flex flex-wrap gap-3">
                        <button class="btn btn-cta btn-md">Primary CTA</button>
                        <button class="btn btn-secondary btn-md">Secondary</button>
                        <button class="btn btn-subtle btn-md">Subtle</button>
                        <button class="btn btn-ghost btn-md">Ghost</button>
                        <button class="btn btn-danger btn-md">Danger</button>
                        <button class="btn btn-cta btn-md" disabled>Disabled</button>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-semibold mb-3">Sizes</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <button class="btn btn-cta btn-sm">Small</button>
                        <button class="btn btn-cta btn-md">Medium</button>
                        <button class="btn btn-cta btn-lg">Large</button>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-text-heading mb-6">Badges</h2>
            <div class="card">
                <div class="flex flex-wrap gap-2">
                    <span class="badge-success">In stock</span>
                    <span class="badge-warning">Expires in 2 days</span>
                    <span class="badge-danger">Expired</span>
                    <span class="badge-info">New</span>
                    <span class="badge-neutral">Uncategorized</span>
                    <span class="badge-brand">Pro</span>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-text-heading mb-6">Cards</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="card">
                    <h4 class="mb-2">Basic card</h4>
                    <p class="text-text-muted text-sm">White surface, soft warm border and shadow.</p>
                </div>
                <div class="card">
                    <span class="badge-warning mb-3">Expiring soon</span>
                    <h4 class="mb-1">Organic milk</h4>
                    <p class="text-text-muted text-sm">2 days left · Fridge</p>
                </div>
                <div class="card hero-bg">
                    <h4 class="mb-2">Highlight card</h4>
                    <p class="text-text-muted text-sm">Same card on the warm hero background.</p>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-text-heading mb-6">Form controls</h2>
            <div class="card max-w-xl">
                <form class="space-y-4">
                    <?php form_input('demo_name', 'Item name', 'text', ['placeholder' => 'e.g. Organic milk', 'value' => '']); ?>
                    <?php form_input('demo_qty', 'Quantity', 'number', ['value' => '1']); ?>
                    <div>
                        <label class="block text-sm font-medium text-text-heading mb-1">Unit</label>
                        <select>
                            <option>Select unit</option>
                            <option>g</option>
                            <option>kg</option>
                            <option>oz</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-heading mb-1">Notes</label>
                        <textarea rows="3" placeholder="Any notes…"></textarea>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <h2 class="text-text-heading mb-6">Page header primitive</h2>
            <div class="card-flush p-6">
                <?php ui_page_header(
                    'My Pantry',
                    'Browse your items by ingredients or products.',
                    '<a href="#" class="btn btn-cta btn-md">Add new item</a>',
                    'Inventory'
                ); ?>
            </div>
        </div>

        <div>
            <h2 class="text-text-heading mb-6">Empty state primitive</h2>
            <?php ui_empty_state(
                'Your pantry is empty',
                'Scan a barcode or add your first item to get tracking expiration dates and recipe ideas.',
                'Scan first item',
                '#',
                '🛒'
            ); ?>
        </div>

        <div>
            <h2 class="text-text-heading mb-6">Feature grid primitive</h2>
            <?php ui_feature_grid([
                ['icon' => '🛒', 'title' => 'Item tracking', 'body' => 'Add ingredients and products with quantity, unit, and expiration date.'],
                ['icon' => '📅', 'title' => 'Expiration alerts', 'body' => 'See what needs attention first. Optional push and email reminders.'],
                ['icon' => '🍳', 'title' => 'Cook tonight', 'body' => 'Recipes you can make right now with what you already have.'],
            ]); ?>
        </div>

    </div>
</section>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>
