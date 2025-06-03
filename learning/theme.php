<?php
// Determine if we're in development mode
$isDev = file_exists(__DIR__ . '/../node_modules');

// Function to include Vite assets
function viteAssets()
{ //
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

            if (isset($manifest['/src/js/main.js']) && isset($manifest['src/js/main.js']['file'])) { //
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
    <title>PantryPal Theme Style Guide</title>
    <?php viteAssets(); ?>
    <!-- Visualization & Content Choices:
        - Colors: Static HTML swatches with hex, variable name, description, Tailwind class example.
        - Typography: Static HTML text samples, variable names, description.
        - Spacing/Borders/Shadows: Static HTML list of variables, description, Tailwind class example.
        - Components: Static HTML visual examples, class name, description.
        - Theme Combinations: Static HTML mockups of UI sections.
        - Interactions: None. Sidebar links are standard anchor links.
        - Libraries: Tailwind CSS (loaded via Vite from style.css).
    -->
    <style>
        /* Minimal styles for sidebar scrollbar if needed, as base Tailwind might not cover this. */
        aside::-webkit-scrollbar {
            width: 6px;
        }

        aside::-webkit-scrollbar-thumb {
            background-color: #CBD5E0; /* Consider var(--color-border-input) or similar theme color */
            border-radius: 3px;
        }

        main::-webkit-scrollbar {
            width: 8px;
        }

        main::-webkit-scrollbar-thumb {
            background-color: #A0AEC0; /* Consider var(--color-text-muted) or similar theme color */
            border-radius: 4px;
        }

        .sg-item-card {
            padding: 1rem; /* p-4 */
            border-width: 1px;
            border-color: var(--color-border-default, #E0E0E0); /* Fallback for Canvas if vars not loaded */
            border-radius: 0.5rem; /* rounded-lg */
            background-color: var(--color-bg-component, #FFFFFF); /* Fallback for Canvas */
        }
        .sg-item-card p.description {
            font-size: 0.875rem; /* text-sm */
            /*color: var(--color-text-muted, #718096);*/
            margin-top: 0.5rem; /* mt-2 */
        }
        .sg-item-card span{
            color: var(--color-text-base, black);
        }
        .combination-preview {
            padding: var(--spacing-6, 1.5rem);
            border-radius: var(--radius-lg, 0.5rem);
            border: 1px solid var(--color-border-default, #E0E0E0);
            margin-top: var(--spacing-4, 1rem);
        }
    </style>
</head>
<body class="bg-bg-page text-text-base font-body">

<div class="min-h-screen flex">
    <aside class="w-64 bg-bg-subtle p-6 space-y-6 border-r border-border-default fixed top-0 left-0 h-full overflow-y-auto">
        <div class="flex items-center space-x-2">
            <div class="logo-leaf w-5 h-5 bg-brand-accent rounded-none rounded-tr-full rounded-bl-full transform -rotate-45 inline-block"></div>
            <h1 class="text-xl font-bold text-text-base">PantryPal Theme</h1>
        </div>
        <nav>
            <ul class="space-y-1">
                <li><a href="../app/Views/home.php" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Home</a></li>
                <li><a href="#colors" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Colors</a></li>
                <li><a href="#typography" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Typography</a></li>
                <li><a href="#spacing" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Spacing</a></li>
                <li><a href="#radius" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Border Radius</a></li>
                <li><a href="#shadows" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Shadows</a></li>
                <li><a href="#transitions" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Transitions</a></li>
                <li><a href="#components" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Components</a></li>
                <li><a href="#combinations" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component focus:bg-bg-component focus:text-cta">Combinations</a></li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 ml-64 p-8 overflow-y-auto" style="scroll-padding-top: 2rem; scroll-behavior: smooth;">
        <div class="max-w-4xl mx-auto space-y-12">

            <section>
                <h2 class="text-3xl font-bold text-text-heading mb-4">PantryPal Style Guide</h2>
                <p class="text-lg">This guide documents the visual style, components, and design patterns for the PantryPal application, based on the theme defined in <code>style.css</code> (loaded via Vite). Use the CSS variables and component classes provided to maintain consistency.</p>
            </section>

            <section id="colors">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Color Palette</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-bg-page"></div>
                        <p class="font-mono text-xs">--color-bg-page <span >(#FDFBF6)</span></p>
                        <p class="description">Primary background color for all pages.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">bg-bg-page</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-bg-subtle"></div>
                        <p class="font-mono text-xs">--color-bg-subtle <span >(#E8F5E9)</span></p>
                        <p class="description">For subtle backgrounds like hero sections, sidebars, or highlighted areas.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">bg-bg-subtle</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-bg-footer"></div>
                        <p class="font-mono text-xs">--color-bg-footer <span >(#FAFAFA)</span></p>
                        <p class="description">Background color specifically for the site footer.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">bg-bg-footer</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-bg-component"></div>
                        <p class="font-mono text-xs">--color-bg-component <span >(#FFFFFF)</span></p>
                        <p class="description">Default background for UI components like cards, modals, and form inputs.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">bg-bg-component</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 flex items-center justify-center bg-text-base text-bg-page">Text</div>
                        <p class="font-mono text-xs">--color-text-base <span >(#36454F)</span></p>
                        <p class="description">Primary text color for body copy and general content.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">text-text-base</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 flex items-center justify-center bg-text-heading text-bg-page">Heading</div>
                        <p class="font-mono text-xs">--color-text-heading <span >(#2E7D32)</span></p>
                        <p class="description">Text color for main headings and section titles.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">text-text-heading</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 flex items-center justify-center bg-text-muted text-bg-page">Muted</div>
                        <p class="font-mono text-xs">--color-text-muted <span >(#718096)</span></p>
                        <p class="description">For secondary information, placeholders, or less emphasized text.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs"></code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 flex items-center justify-center bg-cta text-text-on-cta">On CTA</div>
                        <p class="font-mono text-xs">--color-text-on-cta <span >(#FFFFFF)</span></p>
                        <p class="description">Text color for elements placed on a CTA background (e.g., button text).</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">text-text-on-cta</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-cta"></div>
                        <p class="font-mono text-xs">--color-cta <span >(#4CAF50)</span></p>
                        <p class="description">Primary call-to-action color for buttons, links, and interactive elements.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">bg-cta</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-cta-hover"></div>
                        <p class="font-mono text-xs">--color-cta-hover <span >(#388E3C)</span></p>
                        <p class="description">Hover state for CTA elements.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">hover:bg-cta-hover</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default flex items-center justify-center" style="box-shadow: 0 0 0 3px var(--color-cta-focus-ring);">Focus Ring</div>
                        <p class="font-mono text-xs">--color-cta-focus-ring <span >(#66BB6A)</span></p>
                        <p class="description">Focus ring color for CTA elements, ensuring accessibility.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">focus:ring-cta-focus-ring</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-brand-accent"></div>
                        <p class="font-mono text-xs">--color-brand-accent <span >(#66BB6A)</span></p>
                        <p class="description">Specific brand accent color, e.g., for the logo leaf or minor highlights.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">bg-brand-accent</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border-2 border-border-default"></div>
                        <p class="font-mono text-xs">--color-border-default <span >(#E0E0E0)</span></p>
                        <p class="description">Default border color for dividers, cards, and layout elements.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">border-border-default</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border-2 border-border-input"></div>
                        <p class="font-mono text-xs">--color-border-input <span >(#D1D5DB)</span></p>
                        <p class="description">Border color for form input fields in their default state.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">border-border-input</code></p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border-2 border-border-input-focus"></div>
                        <p class="font-mono text-xs">--color-border-input-focus <span >(var(--color-cta))</span></p>
                        <p class="description">Border color for form input fields when focused. Typically matches CTA color.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">focus:border-border-input-focus</code></p>
                    </div>
                </div>
            </section>

            <section id="typography">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Typography</h2>
                <div class="space-y-6">
                    <div class="sg-item-card">
                        <p class="text-sm  mb-1">Font Family Body: <code class="text-xs">var(--font-body)</code></p>
                        <p class="description">Primary font for all body text and general content. (Inter, sans-serif)</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">font-body</code> (if 'body' is key in <code>fontFamily</code> config)</p>
                        <h1 class="text-4xl font-bold mt-2">Heading 1: The quick brown fox</h1>
                        <h2 class="text-3xl font-bold mt-1">Heading 2: Jumps over the lazy dog</h2>
                        <h3 class="text-2xl mt-1">Heading 3: And runs away quickly</h3>
                        <h4 class="text-xl mt-1">Heading 4: With a piece of toast</h4>
                        <p class="mt-2">This is a paragraph of body text using <code class="text-xs">var(--font-body)</code>. It's designed to be readable and clean. Links, like <a href="#">this example link</a>, are styled according to the theme.</p>
                    </div>
                    <div class="sg-item-card">
                        <p class="text-sm  mb-1">Font Family Monospace: <code class="text-xs">var(--font-mono)</code></p>
                        <p class="description">Monospace font for code blocks and preformatted text.</p>
                        <p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">font-mono</code> (if 'mono' is key in <code>fontFamily</code> config)</p>
                        <pre class="mt-2"><code>// This is a code block example
function greet(name) {
  return \`Hello, \${name}!\`;
}</code></pre>
                    </div>
                </div>
            </section>

            <section id="spacing">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Spacing System</h2>
                <p class="mb-4">Based on a unit of <code class="text-xs">--spacing-unit: 0.25rem</code> (4px). Used for padding, margins, and gaps.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-0: 0rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: 0px;"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-0, m-0, space-x-0</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-1: 0.25rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-1);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-1, m-1, space-x-1</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-2: 0.5rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-2);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-2, m-2, space-x-2</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-3: 0.75rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-3);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-3, m-3, space-x-3</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-4: 1rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-4);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-4, m-4, space-x-4</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-5: 1.25rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-5);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-5, m-5, space-x-5</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-6: 1.5rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-6);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-6, m-6, space-x-6</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-8: 2rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-8);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-8, m-8, space-x-8</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-10: 2.5rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-10);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-10, m-10, space-x-10</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-12: 3rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-12);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-12, m-12, space-x-12</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-16: 4rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-16);"></div><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">p-16, m-16, space-x-16</code></p></div>
                </div>
            </section>

            <section id="radius">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Border Radius</h2>
                <p class="mb-4">Defines the roundness of corners for elements like buttons, cards, and inputs.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center rounded-none">none</div><p class="font-mono text-xs mt-1">--radius-none: 0px</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">rounded-none</code></p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center rounded-sm">sm</div><p class="font-mono text-xs mt-1">--radius-sm: 0.125rem</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">rounded-sm</code></p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center rounded">DEFAULT</div><p class="font-mono text-xs mt-1">--radius-DEFAULT: 0.25rem</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">rounded</code></p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center rounded-md">md</div><p class="font-mono text-xs mt-1">--radius-md: 0.375rem</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">rounded-md</code></p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center rounded-lg">lg</div><p class="font-mono text-xs mt-1">--radius-lg: 0.5rem</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">rounded-lg</code></p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center rounded-xl">xl</div><p class="font-mono text-xs mt-1">--radius-xl: 0.75rem</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">rounded-xl</code></p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center rounded-2xl">2xl</div><p class="font-mono text-xs mt-1">--radius-2xl: 1rem</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">rounded-2xl</code></p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center rounded-full">full</div><p class="font-mono text-xs mt-1">--radius-full: 9999px</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">rounded-full</code></p></div>
                </div>
            </section>

            <section id="shadows">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Shadows</h2>
                <p class="mb-4">Provides depth to elements like cards and modals.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center rounded-lg shadow-sm">shadow-sm</div><p class="font-mono text-xs mt-1">--shadow-sm</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">shadow-sm</code></p></div>
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center rounded-lg shadow">shadow (DEFAULT)</div><p class="font-mono text-xs mt-1">--shadow-DEFAULT</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">shadow</code></p></div>
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center rounded-lg shadow-md">shadow-md</div><p class="font-mono text-xs mt-1">--shadow-md</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">shadow-md</code></p></div>
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center rounded-lg shadow-lg">shadow-lg</div><p class="font-mono text-xs mt-1">--shadow-lg</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">shadow-lg</code></p></div>
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center rounded-lg shadow-xl">shadow-xl</div><p class="font-mono text-xs mt-1">--shadow-xl</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">shadow-xl</code></p></div>
                </div>
            </section>

            <section id="transitions">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Transitions</h2>
                <p class="mb-4">Defines properties for smooth animations on hover, focus, and other state changes.</p>
                <div class="transitions-items-container space-y-2">
                    <div class="sg-item-card"><p class="font-mono text-xs">--transition-property-common: <span >color, background-color, ...</span></p><p class="description">For general purpose transitions on multiple properties.</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">transition-all</code> (approximates)</p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--transition-property-colors: <span >color, background-color, ...</span></p><p class="description">Specifically for color-related transitions.</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">transition-colors</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--transition-timing-default: <span >cubic-bezier(0.4, 0, 0.2, 1)</span></p><p class="description">Default easing function for smooth acceleration and deceleration.</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">ease-in-out</code> (default)</p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--transition-duration-default: <span >150ms</span></p><p class="description">Standard duration for most transitions.</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">duration-150</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--transition-duration-fast: <span >100ms</span></p><p class="description">Faster duration for quick feedback.</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">duration-100</code></p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--transition-duration-slow: <span >300ms</span></p><p class="description">Slower duration for more pronounced transitions.</p><p class="font-mono text-xs mt-1">Tailwind: <code class="text-xs">duration-300</code></p></div>
                </div>
            </section>

            <section id="components">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Components</h2>
                <p class="mb-4">Examples of common UI components styled with the PantryPal theme.</p>
                <div class="space-y-8">
                    <div>
                        <h3 class="text-xl font-semibold mb-3">Buttons</h3>
                        <div class="sg-item-card flex flex-wrap gap-4 items-center">
                            <button class="btn-cta">CTA Button</button>
                            <button class="btn-secondary">Secondary Button</button>
                            <a href="#" class="btn-cta">Link as CTA</a>
                        </div>
                        <p class="text-xs mt-2 ">Classes: <code>.btn</code> (base), <code>.btn-cta</code>, <code>.btn-secondary</code>. These are custom component classes defined in <code>style.css</code>.</p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold mb-3">Alerts</h3>
                        <div class="sg-item-card space-y-4">
                            <div class="alert-info">This is an informational alert. (<code>.alert-info</code>)</div>
                            <div class="alert-success">This is a success alert. (<code>.alert-success</code>)</div>
                            <div class="alert-warning">This is a warning alert. (<code>.alert-warning</code>)</div>
                            <div class="alert-danger">This is a danger alert. (<code>.alert-danger</code>)</div>
                        </div>
                        <p class="text-xs mt-2 ">Base class: <code>.alert</code>. Specific alert types (<code>.alert-info</code>, etc.) are custom component classes.</p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold mb-3">Card</h3>
                        <div class="card max-w-sm">
                            <h4 class="text-lg font-semibold mb-2">Card Title</h4>
                            <p class="text-sm">This is some content within a card component. It uses the defined card styles for background, padding, border-radius, and shadow.</p>
                            <button class="btn-cta mt-4">Action</button>
                        </div>
                        <p class="text-xs mt-2 ">Class: <code>.card</code>. A custom component class.</p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold mb-3">Form Elements</h3>
                        <div class="sg-item-card space-y-4 max-w-md">
                            <div>
                                <label for="text-input" class="block text-sm font-medium mb-1">Text Input</label>
                                <input type="text" id="text-input" placeholder="Enter text here" class="w-full">
                            </div>
                            <div>
                                <label for="select-input" class="block text-sm font-medium mb-1">Select</label>
                                <select id="select-input" class="w-full">
                                    <option>Option 1</option>
                                    <option>Option 2</option>
                                    <option>Option 3</option>
                                </select>
                            </div>
                            <div>
                                <label for="textarea-input" class="block text-sm font-medium mb-1">Textarea</label>
                                <textarea id="textarea-input" rows="3" placeholder="Enter longer text" class="w-full"></textarea>
                            </div>
                        </div>
                        <p class="text-xs mt-2 ">Form elements use base styles defined in <code>@layer base</code> from your <code>style.css</code>.</p>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold mb-3">Backgrounds & Links</h3>
                        <div class="space-y-4">
                            <div class="bg-cta p-4 rounded-lg">
                                <p class="text-text-on-cta">This container has <code>.bg-cta</code>. <a href="#" class="link-on-cta">This is a .link-on-cta</a> or an <code>a</code> tag styled by <code>.bg-cta a</code>.</p>
                            </div>
                            <div class="bg-subtle p-4 rounded-lg">
                                <p>This container has <code>.bg-subtle</code>. <a href="#" class="link-on-subtle">This is a .link-on-subtle</a> or an <code>a</code> tag styled by <code>.bg-subtle a</code>.</p>
                            </div>
                        </div>
                        <p class="text-xs mt-2 ">Classes: <code>.bg-cta</code>, <code>.link-on-cta</code>, <code>.bg-subtle</code>, <code>.link-on-subtle</code>. These are custom component classes.</p>
                    </div>
                </div>
            </section>

            <section id="combinations">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Theme Combinations</h2>
                <p class="mb-4">Examples of how theme elements work together in common UI patterns.</p>
                <div class="space-y-8">

                    <div>
                        <h3 class="text-xl font-semibold mb-3">Primary Action Block</h3>
                        <div class="combination-preview bg-cta">
                            <h3 class="text-2xl font-bold mb-2 text-text-on-cta">Special Announcement!</h3>
                            <p class="mb-4">Check out our latest feature that will revolutionize your pantry management. Click the button below to learn more and get started today.</p>
                            <button class="btn bg-bg-component text-cta hover:bg-opacity-90">Learn More</button>
                        </div>
                        <p class="text-xs mt-2 ">Demonstrates <code>--color-bg-cta</code>, <code>--color-text-on-cta</code>, and a contrasting button.</p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold mb-3">Content Card with Subtle Background</h3>
                        <div class="combination-preview bg-bg-subtle">
                            <div class="card">
                                <h4 class="text-lg font-semibold text-text-heading mb-2">Organize Your Spices</h4>
                                <p class="text-sm text-text-base mb-3">Keeping your spices organized can save you time and make cooking more enjoyable. Use PantryPal to track quantities and expiration dates.</p>
                                <a href="#" class="text-cta font-medium hover:text-cta-hover">Read more tips...</a>
                            </div>
                        </div>
                        <p class="text-xs mt-2 ">Shows <code>.card</code> on <code>--color-bg-subtle</code>, with standard text and link colors.</p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold mb-3">Standard Form on Page Background</h3>
                        <div class="combination-preview bg-bg-page max-w-md">
                            <h4 class="text-lg font-semibold text-text-heading mb-4">Add New Item</h4>
                            <div class="space-y-4">
                                <div>
                                    <label for="item-name" class="block text-sm font-medium text-text-base mb-1">Item Name</label>
                                    <input type="text" id="item-name" placeholder="e.g., Organic Milk" class="w-full">
                                </div>
                                <div>
                                    <label for="item-category" class="block text-sm font-medium text-text-base mb-1">Category</label>
                                    <select id="item-category" class="w-full">
                                        <option>Dairy</option>
                                        <option>Produce</option>
                                        <option>Pantry Staples</option>
                                    </select>
                                </div>
                                <button class="btn-cta w-full">Add to Pantry</button>
                            </div>
                        </div>
                        <p class="text-xs mt-2 ">Illustrates form elements using base styles on the default <code>--color-bg-page</code>.</p>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold mb-3">Footer Example</h3>
                        <div class="combination-preview bg-bg-footer text-text-base p-6 text-center">
                            <div class="flex items-center justify-center mb-2">
                                <div class="logo-leaf w-5 h-5 bg-brand-accent rounded-none rounded-tr-full rounded-bl-full transform -rotate-45 inline-block mr-2"></div>
                                <p class="text-lg font-semibold">PantryPal</p>
                            </div>
                            <p class="text-sm ">&copy; <?php echo date("Y"); ?> PantryPal. All rights reserved.</p>
                            <p class="text-sm mt-1"><a href="#" class="text-cta hover:text-cta-hover">Privacy Policy</a> | <a href="#" class="text-cta hover:text-cta-hover">Terms of Service</a></p>
                        </div>
                        <p class="text-xs mt-2 ">Shows <code>--color-bg-footer</code> with <code>--color-text-base</code>, <code>--color-text-muted</code>, and <code>--color-cta</code> for links.</p>
                    </div>

                </div>
            </section>
        </div>
    </main>
</div>

</body>
</html>
