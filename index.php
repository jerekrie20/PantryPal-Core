<?php
// Determine if we're in development mode
$isDev = file_exists(__DIR__ . '/node_modules'); //

// Function to include Vite assets
function viteAssets() { //
    global $isDev;

    if ($isDev) { //
        // In development, include the Vite client and use the dev server
        // Use a timestamp to prevent caching issues
        $timestamp = time(); //
        echo '<script type="module" src="https://localhost:5173/pantrypal_core/@vite/client?' . $timestamp . '"></script>'; //
        echo '<script type="module" src="https://localhost:5173/pantrypal_core/src/js/main.js?' . $timestamp . '"></script>'; //
    } else {
        // In production, include the built assets (ensure manifest path is correct)
        $manifestPath = __DIR__ . '/dist/manifest.json'; //
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true); //

            if (isset($manifest['src/js/main.js']) && isset($manifest['src/js/main.js']['file'])) { //
                // CSS is imported in JS, so Vite automatically injects the CSS
                echo '<script type="module" src="/pantrypal_core/dist/' . $manifest['src/js/main.js']['file'] . '"></script>'; // Adjusted path for base
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal Core</title>
    <?php viteAssets(); ?>

</head>
<body>
<div class="container bg-amber-900 mx-auto py-8">
    <h1 class="text-3xl font-bold text-center mb-6">Welceome! to PantryPal Core</h1>
    <p class="text-center mb-4">This is a vanilla PHP project with Vite and Tailwind CSS.</p>
</div>
</body>
</html>