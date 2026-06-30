<?php
/**
 * UI primitives for the PantryPal design system.
 * Pattern mirrors form_elements.php — pure functions, no global state.
 *
 * Usage:
 *   require_once VIEW_PATH . '/Components/ui_elements.php';
 *   ui_page_header('My Pantry', 'Browse by ingredients or products', '<a class="btn btn-cta btn-md" href="/items/create">Add item</a>');
 */

if (!function_exists('ui_e')) {
    function ui_e($v): string {
        if ($v === null) return '';
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Section title row: eyebrow + headline + optional subhead + actions slot.
 * The action slot is raw HTML (already escaped by caller).
 */
if (!function_exists('ui_page_header')) {
    function ui_page_header(string $title, ?string $subtitle = null, ?string $actionsHtml = null, ?string $eyebrow = null): void {
        ?>
        <section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
            <div class="min-w-0">
                <?php if ($eyebrow): ?>
                    <p class="eyebrow mb-2"><?= ui_e($eyebrow) ?></p>
                <?php endif; ?>
                <h1 class="text-text-heading"><?= ui_e($title) ?></h1>
                <?php if ($subtitle): ?>
                    <p class="text-text-muted mt-2 max-w-2xl"><?= ui_e($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($actionsHtml): ?>
                <div class="shrink-0 flex flex-wrap gap-2"><?= $actionsHtml ?></div>
            <?php endif; ?>
        </section>
        <?php
    }
}

/**
 * Empty-state placeholder: headline + body + one CTA.
 * Used when a list is empty. Should pitch the next action, not apologize.
 */
if (!function_exists('ui_empty_state')) {
    function ui_empty_state(string $title, string $body, ?string $ctaText = null, ?string $ctaHref = null, ?string $icon = null): void {
        ?>
        <div class="card flex flex-col items-center text-center py-12 px-6">
            <?php if ($icon): ?>
                <div class="text-5xl mb-4" aria-hidden="true"><?= ui_e($icon) ?></div>
            <?php endif; ?>
            <h3 class="text-text-heading mb-2"><?= ui_e($title) ?></h3>
            <p class="text-text-muted max-w-md mb-6"><?= ui_e($body) ?></p>
            <?php if ($ctaText && $ctaHref): ?>
                <a href="<?= ui_e($ctaHref) ?>" class="btn btn-cta btn-md"><?= ui_e($ctaText) ?></a>
            <?php endif; ?>
        </div>
        <?php
    }
}

/**
 * Open/close a marketing section with consistent vertical rhythm.
 * Pass $bg = 'page' | 'subtle' | 'hero' | 'white'.
 */
if (!function_exists('ui_section_open')) {
    function ui_section_open(string $bg = 'page', ?string $id = null): void {
        $classes = match ($bg) {
            'subtle' => 'section-y bg-bg-subtle',
            'hero'   => 'section-y hero-bg',
            'white'  => 'section-y bg-bg-component',
            default  => 'section-y bg-bg-page',
        };
        $idAttr = $id ? ' id="' . ui_e($id) . '"' : '';
        echo "<section{$idAttr} class=\"{$classes}\">"
             . '<div class="container mx-auto px-4 sm:px-6 lg:px-8">';
    }
}
if (!function_exists('ui_section_close')) {
    function ui_section_close(): void { echo '</div></section>'; }
}

/**
 * Marketing hero: eyebrow + display headline + lede + CTA row.
 * $ctas is an array of ['text' => string, 'href' => string, 'variant' => 'cta'|'secondary'|'subtle'].
 */
if (!function_exists('ui_hero')) {
    function ui_hero(string $headline, ?string $lede = null, array $ctas = [], ?string $eyebrow = null): void {
        ?>
        <div class="text-center max-w-3xl mx-auto">
            <?php if ($eyebrow): ?>
                <p class="eyebrow mb-4"><?= ui_e($eyebrow) ?></p>
            <?php endif; ?>
            <h1 class="text-text-heading"><?= ui_e($headline) ?></h1>
            <?php if ($lede): ?>
                <p class="lede mt-6 mx-auto"><?= ui_e($lede) ?></p>
            <?php endif; ?>
            <?php if (!empty($ctas)): ?>
                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                    <?php foreach ($ctas as $cta):
                        $variant = $cta['variant'] ?? 'cta';
                        $class = 'btn btn-' . ui_e($variant) . ' btn-lg';
                    ?>
                        <a href="<?= ui_e($cta['href'] ?? '#') ?>" class="<?= $class ?>"><?= ui_e($cta['text'] ?? '') ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

/**
 * Feature grid for marketing — pass [['icon' => '🛒', 'title' => '...', 'body' => '...'], ...].
 */
if (!function_exists('ui_feature_grid')) {
    function ui_feature_grid(array $features, int $cols = 3): void {
        $colClass = match ($cols) {
            2 => 'grid-cols-1 sm:grid-cols-2',
            4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
            default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        };
        ?>
        <div class="grid <?= $colClass ?> gap-6">
            <?php foreach ($features as $f): ?>
                <div class="card h-full">
                    <?php if (!empty($f['icon'])): ?>
                        <div class="text-3xl mb-3" aria-hidden="true"><?= ui_e($f['icon']) ?></div>
                    <?php endif; ?>
                    <h3 class="text-text-heading mb-2"><?= ui_e($f['title'] ?? '') ?></h3>
                    <p class="text-text-muted"><?= ui_e($f['body'] ?? '') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
