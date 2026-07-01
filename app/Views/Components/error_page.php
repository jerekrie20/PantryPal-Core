<?php
/**
 * Shared error page shell.
 * Included by 401/403/404/500 views. Standalone HTML (no layout include) so
 * it still renders when the layout itself is broken.
 *
 * Expects the including file to set:
 *   $errStatus   int    (e.g. 404)
 *   $errTitle    string One-line label (e.g. "Page not found")
 *   $errBody     string One or two sentences.
 *   $errActions  array  Optional [['text' => ..., 'href' => ..., 'variant' => 'cta'|'secondary'], ...]
 *   $errAccent   string Optional token name for the big status number:
 *                       'danger' | 'warning' | 'info' | 'brand' (default 'danger')
 */
$errStatus  = $errStatus  ?? 500;
$errTitle   = $errTitle   ?? 'Something went wrong';
$errBody    = $errBody    ?? 'Please try again in a moment.';
$errActions = $errActions ?? [['text' => 'Go home', 'href' => '/', 'variant' => 'cta']];
$errAccent  = $errAccent  ?? 'danger';

$accentColor = match ($errAccent) {
    'warning' => 'var(--color-warning-text)',
    'info'    => 'var(--color-info-text)',
    'brand'   => 'var(--color-brand-600)',
    default   => 'var(--color-danger-text)',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal · <?= htmlspecialchars($errTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap" rel="stylesheet">
    <?php echo function_exists('vite_tags') ? vite_tags() : ''; ?>
</head>
<body class="bg-bg-page text-text-base font-body antialiased">
    <main class="min-h-screen flex items-center justify-center p-4">
        <div class="card max-w-lg w-full text-center">
            <p class="font-display text-7xl font-medium leading-none" style="color: <?= $accentColor ?>;"><?= (int)$errStatus ?></p>
            <h1 class="text-text-heading mt-4"><?= htmlspecialchars($errTitle) ?></h1>
            <p class="text-text-muted mt-3 max-w-md mx-auto"><?= htmlspecialchars($errBody) ?></p>
            <div class="mt-8 flex flex-col-reverse sm:flex-row gap-3 justify-center">
                <?php foreach ($errActions as $a):
                    $variant = $a['variant'] ?? 'cta';
                ?>
                    <a href="<?= htmlspecialchars($a['href'] ?? '/') ?>" class="btn btn-<?= htmlspecialchars($variant) ?> btn-md">
                        <?= htmlspecialchars($a['text'] ?? 'Go home') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>
