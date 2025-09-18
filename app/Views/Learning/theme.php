<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PantryPal Theme Style Guide</title>
    <?php echo vite_tags(); ?>
    <style>
        /* Your existing styles (unchanged) */
        aside::-webkit-scrollbar{width:6px}
        aside::-webkit-scrollbar-thumb{background-color:#CBD5E0;border-radius:3px}
        main::-webkit-scrollbar{width:8px}
        main::-webkit-scrollbar-thumb{background-color:#A0AEC0;border-radius:4px}
        .sg-item-card{padding:1rem;border-width:1px;border-color:var(--color-border-default,#E0E0E0);border-radius:.5rem;background-color:var(--color-bg-component,#FFFFFF)}
        .sg-item-card p.description{font-size:.875rem;color:var(--color-text-muted,#718096);margin-top:.5rem}
        .sg-item-card span{color:var(--color-text-base,black)}
        .combination-preview{padding:var(--spacing-6,1.5rem);border-radius:var(--radius-lg,.5rem);border:1px solid var(--color-border-default,#E0E0E0);margin-top:var(--spacing-4,1rem)}

        /* >>> Enhancements: wide content + code panel on right (non-destructive) <<< */

        /* Use more screen width without changing your HTML:
           override Tailwind's .max-w-4xl for this page only */
        .max-w-4xl {
            max-width: min(1400px, 92vw) !important;
        }

        /* Each demo becomes a two-column pair that spans the full parent grid width */
        .sg-code-pair{
            display:grid;
            grid-template-columns: minmax(0,1.1fr) minmax(0,1.4fr); /* roomier code pane */
            gap: 1.25rem;
            align-items:start;
            width:100%;
            /* If the parent is a grid (like your color/shadow sections),
               make the pair span all columns so it gets full width */
            grid-column: 1 / -1;
        }

        /* Make the code side comfy and readable */
        .sg-code{
            position:relative;
            border:1px solid var(--color-border-default,#E0E0E0);
            border-radius:.5rem;
            background:#0b1020;             /* dark background for contrast */
            color:#e6edf3;
            padding: 0.75rem 0.75rem 0.75rem 0.75rem;
            overflow:auto;
        }
        .sg-code pre{
            margin:0;
            white-space:pre;                 /* horizontal scrolling instead of wrapping */
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size:.8rem;
            line-height:1.5;
        }
        .sg-copy{
            position:absolute;
            top:8px;
            right:8px;
            font-size:.75rem;
            padding:.25rem .5rem;
            border:1px solid #2d3759;
            border-radius:.375rem;
            background:#121733;
            color:#e6edf3;
            cursor:pointer;
        }
        .sg-copy:active{ transform: translateY(1px); }

        /* Responsive: on narrow screens stack preview and code */
        @media (max-width: 900px){
            .sg-code-pair{ grid-template-columns: 1fr; }
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
        <nav class="space-y-4">
            <div>
                <h2 class="px-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Foundations</h2>
                <ul class="space-y-1 mt-2">
                    <li><a href="/" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Home</a></li>
                    <li><a href="#introduction" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Introduction</a></li>
                    <li><a href="#colors" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Colors</a></li>
                    <li><a href="#typography" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Typography</a></li>
                    <li><a href="#spacing" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Spacing</a></li>
                    <li><a href="#radius" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Border Radius</a></li>
                    <li><a href="#shadows" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Shadows</a></li>
                    <li><a href="#transitions" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Transitions</a></li>
                </ul>
            </div>
            <div>
                <h2 class="px-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Components</h2>
                <ul class="space-y-1 mt-2">
                    <li><a href="#buttons" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Buttons</a></li>
                    <li><a href="#forms" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Forms</a></li>
                    <li><a href="#badges-alerts" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Badges & Alerts</a></li>
                    <li><a href="#cards-modals" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Cards & Modals</a></li>
                    <li><a href="#images-media" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Images & Media</a></li>
                </ul>
            </div>
            <div>
                <h2 class="px-3 text-xs font-semibold text-text-muted uppercase tracking-wider">Patterns & Layouts</h2>
                <ul class="space-y-1 mt-2">
                    <li><a href="#hero" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Hero Sections</a></li>
                    <li><a href="#backgrounds" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Backgrounds</a></li>
                    <li><a href="#page-layouts" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Page Layouts</a></li>
                    <li><a href="#combinations" class="block font-medium py-2 px-3 rounded-md hover:bg-bg-component">Combinations</a></li>
                </ul>
            </div>
        </nav>
    </aside>

    <main class="flex-1 ml-64 p-8 overflow-y-auto" style="scroll-padding-top: 2rem; scroll-behavior: smooth;">
        <div class="max-w-4xl mx-auto space-y-12">

            <section id="introduction">
                <h2 class="text-3xl font-bold text-text-heading mb-4">PantryPal Style Guide</h2>
                <p class="text-lg text-text-muted">This guide documents the visual style, components, and design patterns for the PantryPal application, based on the theme defined in <code>style.css</code> (loaded via Vite). Use the CSS variables and component classes provided to maintain consistency.</p>
            </section>

            <section id="colors">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Color Palette</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-bg-page"></div>
                        <p class="font-mono text-xs">--color-bg-page <span class="text-text-muted">(#FDFBF6)</span></p>
                        <p class="description">Primary background color for all pages.</p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-bg-subtle"></div>
                        <p class="font-mono text-xs">--color-bg-subtle <span class="text-text-muted">(#E8F5E9)</span></p>
                        <p class="description">For subtle backgrounds like hero sections, sidebars, or highlighted areas.</p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-bg-component"></div>
                        <p class="font-mono text-xs">--color-bg-component <span class="text-text-muted">(#FFFFFF)</span></p>
                        <p class="description">Default background for UI components like cards, modals, and form inputs.</p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 flex items-center justify-center bg-text-base text-bg-page">Text</div>
                        <p class="font-mono text-xs">--color-text-base <span class="text-text-muted">(#36454F)</span></p>
                        <p class="description">Primary text color for body copy.</p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 flex items-center justify-center bg-text-heading text-bg-page">Heading</div>
                        <p class="font-mono text-xs">--color-text-heading <span class="text-text-muted">(#2E7D32)</span></p>
                        <p class="description">Text color for main headings.</p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 flex items-center justify-center bg-text-muted text-bg-page">Muted</div>
                        <p class="font-mono text-xs">--color-text-muted <span class="text-text-muted">(#718096)</span></p>
                        <p class="description">For secondary information or placeholders.</p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-cta"></div>
                        <p class="font-mono text-xs">--color-cta <span class="text-text-muted">(#4CAF50)</span></p>
                        <p class="description">Primary call-to-action color.</p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 flex items-center justify-center bg-cta text-text-on-cta">On CTA</div>
                        <p class="font-mono text-xs">--color-text-on-cta <span class="text-text-muted">(#FFFFFF)</span></p>
                        <p class="description">Text for elements on a CTA background.</p>
                    </div>
                    <div class="sg-item-card">
                        <div class="w-full h-20 rounded-md mb-2 border border-border-default bg-danger"></div>
                        <p class="font-mono text-xs">--color-danger <span class="text-text-muted">(#DC2626)</span></p>
                        <p class="description">For destructive actions like delete.</p>
                    </div>
                </div>
            </section>

            <section id="typography">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Typography</h2>
                <div class="sg-item-card">
                    <p class="text-sm text-text-muted mb-1">Font Family Body: <code class="text-xs">var(--font-body)</code></p>
                    <h1 class="text-4xl font-bold text-text-heading mt-2">Heading 1: The quick brown fox</h1>
                    <h2 class="text-3xl font-bold text-text-heading mt-1">Heading 2: Jumps over the lazy dog</h2>
                    <h3 class="text-2xl text-text-heading mt-1">Heading 3: And runs away quickly</h3>
                    <h4 class="text-xl text-text-heading mt-1">Heading 4: With a piece of toast</h4>
                    <p class="mt-2">This is a paragraph of body text. It's designed to be readable and clean. Links, like <a href="#">this example link</a>, are styled according to the theme.</p>
                </div>
            </section>

            <section id="spacing">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Spacing System</h2>
                <p class="mb-4 text-text-muted">Based on a unit of <code class="text-xs">--spacing-unit: 0.25rem</code> (4px). Used for padding, margins, and gaps.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-0: 0rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: 0px;"></div></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-1: 0.25rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-1);"></div></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-2: 0.5rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-2);"></div></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-3: 0.75rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-3);"></div></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-4: 1rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-4);"></div></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-6: 1.5rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-6);"></div></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-8: 2rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-8);"></div></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-12: 3rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-12);"></div></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--spacing-16: 4rem</p><div class="w-full h-4 bg-brand-accent mt-1" style="width: var(--spacing-16);"></div></div>
                </div>
            </section>

            <section id="radius">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Border Radius</h2>
                <p class="mb-4 text-text-muted">Defines the roundness of corners for elements like buttons, cards, and inputs.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center text-text-muted rounded-none">none</div><p class="font-mono text-xs mt-1">--radius-none: 0px</p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center text-text-muted rounded-sm">sm</div><p class="font-mono text-xs mt-1">--radius-sm: 0.125rem</p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center text-text-muted rounded-md">md</div><p class="font-mono text-xs mt-1">--radius-md: 0.375rem</p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center text-text-muted rounded-lg">lg</div><p class="font-mono text-xs mt-1">--radius-lg: 0.5rem</p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center text-text-muted rounded-xl">xl</div><p class="font-mono text-xs mt-1">--radius-xl: 0.75rem</p></div>
                    <div class="sg-item-card"><div class="w-24 h-24 bg-bg-subtle border border-border-input flex items-center justify-center text-xs text-center text-text-muted rounded-full">full</div><p class="font-mono text-xs mt-1">--radius-full: 9999px</p></div>
                </div>
            </section>

            <section id="shadows">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Shadows</h2>
                <p class="mb-4 text-text-muted">Provides depth to elements like cards and modals.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center text-text-muted rounded-lg shadow-sm">shadow-sm</div></div>
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center text-text-muted rounded-lg shadow">shadow</div></div>
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center text-text-muted rounded-lg shadow-md">shadow-md</div></div>
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center text-text-muted rounded-lg shadow-lg">shadow-lg</div></div>
                    <div class="sg-item-card"><div class="w-full h-24 bg-bg-component flex items-center justify-center text-xs text-center text-text-muted rounded-lg shadow-xl">shadow-xl</div></div>
                </div>
            </section>

            <section id="transitions">
                <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Transitions</h2>
                <p class="mb-4 text-text-muted">Defines properties for smooth animations.</p>
                <div class="space-y-2">
                    <div class="sg-item-card"><p class="font-mono text-xs">--transition-property-common</p><p class="description">For general purpose transitions.</p></div>
                    <div class="sg-item-card"><p class="font-mono text-xs">--transition-duration-default</p><p class="description">Standard duration (150ms).</p></div>
                </div>
            </section>

            <section id="components" class="space-y-12">
                <section id="buttons">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Buttons</h2>
                    <p class="mb-4 text-sm text-text-muted">Use the base <code>.btn</code> class with a modifier for style. Use <code>-sm</code>, <code>-md</code>, or <code>-lg</code> for size variations.</p>

                    <div class="sg-item-card space-y-4">
                        <div>
                            <h4 class="font-semibold mb-2">Primary (CTA)</h4>
                            <div class="flex flex-wrap gap-4 items-center">
                                <button class="btn-cta-lg">Large CTA</button>
                                <button class="btn-cta-md">Default CTA</button>
                                <button class="btn-cta-sm">Small CTA</button>
                                <button class="btn-cta-md" disabled>Disabled</button>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold mb-2">Secondary</h4>
                            <div class="flex flex-wrap gap-4 items-center">
                                <button class="btn-secondary-lg">Large Secondary</button>
                                <button class="btn-secondary-md">Default Secondary</button>
                                <button class="btn-secondary-sm">Small Secondary</button>
                                <button class="btn-secondary-md" disabled>Disabled</button>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold mb-2">Subtle / Ghost</h4>
                            <div class="flex flex-wrap gap-4 items-center">
                                <button class="btn-subtle-lg">Large Subtle</button>
                                <button class="btn-subtle-md">Default Subtle</button>
                                <button class="btn-subtle-sm">Small Subtle</button>
                                <button class="btn-subtle-md" disabled>Disabled</button>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold mb-2">Destructive</h4>
                            <div class="flex flex-wrap gap-4 items-center">
                                <button class="btn-danger-lg">Large Danger</button>
                                <button class="btn-danger-md">Default Danger</button>
                                <button class="btn-danger-sm">Small Danger</button>
                                <button class="btn-danger-md" disabled>Disabled</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="forms">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Forms</h2>
                    <div class="sg-item-card space-y-4 max-w-md">
                        <div>
                            <label for="text-input" class="block text-sm font-medium mb-1">Text Input</label>
                            <input type="text" id="text-input" placeholder="Enter text here" class="w-full">
                        </div>
                        <div>
                            <label for="text-input-err" class="block text-sm font-medium mb-1 text-danger">Input with Error</label>
                            <input type="text" id="text-input-err" value="Invalid entry" class="w-full border-danger focus:border-danger focus:ring-danger">
                            <p class="text-xs text-danger mt-1">Please enter a valid item name.</p>
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
                        <fieldset>
                            <legend class="text-sm font-medium mb-2">Options</legend>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input id="check1" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-cta focus:ring-cta">
                                    <label for="check1" class="ml-2 block text-sm">Checkbox Option A</label>
                                </div>
                                <div class="flex items-center">
                                    <input id="radio1" name="radio-group" type="radio" class="h-4 w-4 border-gray-300 text-cta focus:ring-cta">
                                    <label for="radio1" class="ml-2 block text-sm">Radio Option X</label>
                                </div>
                                <div class="flex items-center">
                                    <input id="radio2" name="radio-group" type="radio" class="h-4 w-4 border-gray-300 text-cta focus:ring-cta">
                                    <label for="radio2" class="ml-2 block text-sm">Radio Option Y</label>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </section>

                <section id="badges-alerts">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Badges & Alerts</h2>
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Badges</h3>
                            <div class="sg-item-card flex flex-wrap gap-4 items-center">
                                <span class="badge-success">In Stock</span>
                                <span class="badge-warning">Expiring Soon</span>
                                <span class="badge-danger">Expired</span>
                                <span class="badge-info">New</span>
                                <span class="badge-neutral">Dairy</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Alerts</h3>
                            <div class="sg-item-card space-y-4">
                                <div class="alert-info">This is an informational alert.</div>
                                <div class="alert-success">Item successfully added to your pantry.</div>
                                <div class="alert-warning">Your milk is about to expire.</div>
                                <div class="alert-danger">Failed to save item. Please try again.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="cards-modals">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Cards & Modals</h2>
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Card</h3>
                            <div class="card max-w-sm">
                                <h4 class="text-lg font-semibold text-text-heading mb-2">Card Title</h4>
                                <p class="text-sm">This is some content within a card component. It uses the defined card styles for background, padding, border-radius, and shadow.</p>
                                <button class="btn-cta-md mt-4">Action</button>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Modal (Static Example)</h3>
                            <div class="sg-item-card">
                                <div class="relative w-full h-96 bg-bg-subtle rounded-lg flex items-center justify-center">
                                    <div class="absolute inset-0 bg-gray-800 bg-opacity-75"></div>
                                    <div class="card w-full max-w-md mx-4">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="text-lg font-semibold text-text-heading">Confirm Action</h4>
                                            <button class="btn-subtle-sm p-1 rounded-full">&times;</button>
                                        </div>
                                        <p class="text-sm text-text-muted mb-6">Are you sure you want to delete "Organic Milk"? This action cannot be undone.</p>
                                        <div class="flex justify-end space-x-3">
                                            <button class="btn-secondary-md">Cancel</button>
                                            <button class="btn-danger-md">Yes, Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="images-media">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Images & Media</h2>
                    <div class="sg-item-card grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h4 class="font-semibold mb-2">Standard Image Card</h4>
                            <div class="card p-0 overflow-hidden max-w-xs">
                                <img src="https://placehold.co/400x250/E8F5E9/36454F?text=Pantry+Item" alt="Placeholder">
                                <div class="p-4">
                                    <h5 class="font-semibold text-text-heading">Almond Flour</h5>
                                    <p class="text-sm text-text-muted mt-1">A great gluten-free alternative.</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">User Avatars</h4>
                            <div class="flex items-center space-x-4">
                                <img class="h-16 w-16 rounded-full object-cover" src="https://placehold.co/96x96/66BB6A/FFFFFF?text=A" alt="Avatar">
                                <img class="h-12 w-12 rounded-full object-cover" src="https://placehold.co/80x80/36454F/FFFFFF?text=B" alt="Avatar">
                                <img class="h-8 w-8 rounded-full object-cover" src="https://placehold.co/64x64/E0E0E0/36454F?text=C" alt="Avatar">
                            </div>
                        </div>
                    </div>
                </section>
            </section>

            <section id="patterns-layouts" class="space-y-12">
                <section id="hero">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Hero Sections</h2>
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Centered Hero</h3>
                            <div class="combination-preview bg-bg-subtle py-16 sm:py-24">
                                <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
                                    <h1 class="text-4xl md:text-6xl font-bold text-text-heading mb-6">Effortless Pantry Management is Here.</h1>
                                    <p class="max-w-3xl mx-auto text-lg md:text-xl text-text-muted mb-8">Track groceries, reduce waste, and discover recipes with what you already have.</p>
                                    <div><button type="button" class="btn-cta-lg">Get Started for Free</button></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Hero with Image</h3>
                            <div class="combination-preview">
                                <div class="grid md:grid-cols-2 gap-8 items-center">
                                    <div>
                                        <h1 class="text-4xl font-bold text-text-heading mb-4">A Smarter Kitchen Awaits</h1>
                                        <p class="text-lg text-text-muted mb-6">Stop letting good food go to waste. PantryPal helps you keep track of everything, so you can save money and eat better.</p>
                                        <button class="btn-cta-lg">Sign Up Now</button>
                                    </div>
                                    <div>
                                        <img src="https://placehold.co/600x400/E8F5E9/36454F?text=App+Screenshot" alt="App Screenshot" class="rounded-lg shadow-lg">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="backgrounds">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Backgrounds</h2>
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Solid Color Backgrounds</h3>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="combination-preview bg-cta text-text-on-cta">
                                    <h4 class="font-bold">CTA Background</h4>
                                    <p>This is text on the primary CTA background color.</p>
                                </div>
                                <div class="combination-preview bg-text-base text-text-on-cta">
                                    <h4 class="font-bold">Dark Background</h4>
                                    <p>This uses the base text color as a background for high contrast.</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Background Image with Overlay</h3>
                            <div class="combination-preview p-0 overflow-hidden">
                                <div class="relative h-64 bg-gray-500 rounded-lg">
                                    <img src="https://placehold.co/800x400/2E7D32/FFFFFF?text=Healthy+Greens" class="absolute h-full w-full object-cover" alt="Leafy greens">
                                    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
                                    <div class="relative h-full flex flex-col justify-center items-center text-center p-4">
                                        <h2 class="text-3xl font-bold text-white mb-4">Discover Fresh Recipes</h2>
                                        <p class="text-white text-opacity-90 max-w-md mb-6">Use up your fresh ingredients with our smart recipe suggestions.</p>
                                        <button class="btn-cta-md">Find a Recipe</button>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs mt-2 text-text-muted">Note: Placeholder images from placehold.co may not render in all preview environments, but the code is correct.</p>
                        </div>
                    </div>
                </section>

                <section id="page-layouts">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Page Layouts</h2>
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Dashboard Layout</h3>
                            <div class="combination-preview bg-bg-subtle">
                                <div class="flex space-x-6">
                                    <div class="w-1/4 bg-bg-component rounded-lg p-4">
                                        <h4 class="font-semibold mb-4">Navigation</h4>
                                        <ul class="space-y-2 text-sm">
                                            <li class="font-bold text-cta bg-bg-subtle p-2 rounded-md">Dashboard</li>
                                            <li class="p-2">My Pantry</li>
                                            <li class="p-2">Recipes</li>
                                            <li class="p-2">Shopping List</li>
                                            <li class="p-2">Settings</li>
                                        </ul>
                                    </div>
                                    <div class="w-3/4 space-y-4">
                                        <div class="card p-4">
                                            <h4 class="font-semibold text-text-heading">Items Expiring Soon</h4>
                                            <p class="text-sm text-text-muted">...</p>
                                        </div>
                                        <div class="card p-4">
                                            <h4 class="font-semibold text-text-heading">Recent Additions</h4>
                                            <p class="text-sm text-text-muted">...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="combinations">
                    <h2 class="text-2xl font-bold text-text-heading mb-6 border-b pb-2 border-border-default">Combinations</h2>
                    <div class="space-y-12">
                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Application Header (Logged In)</h3>
                            <div class="combination-preview p-0">
                                <header class="bg-bg-component shadow-sm">
                                    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                                        <div class="flex items-center justify-between h-16">
                                            <div class="flex items-center">
                                                <div class="logo-leaf w-5 h-5 bg-brand-accent rounded-none rounded-tr-full rounded-bl-full transform -rotate-45"></div>
                                                <span class="ml-2 text-xl font-bold text-text-base">PantryPal</span>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <a href="#" class="btn-subtle-md hidden sm:inline-flex">Dashboard</a>
                                                <a href="#" class="btn-secondary-md hidden sm:inline-flex">My Recipes</a>
                                                <button class="btn-cta-md">Add Item</button>
                                                <img class="h-8 w-8 rounded-full object-cover" src="https://placehold.co/64x64/E0E0E0/36454F?text=U" alt="User Avatar">
                                            </div>
                                        </div>
                                    </div>
                                </header>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Pantry Item List</h3>
                            <div class="combination-preview bg-bg-subtle">
                                <ul class="space-y-3">
                                    <li class="card flex items-center justify-between p-4">
                                        <div class="flex items-center">
                                            <img src="https://placehold.co/80x80/E8F5E9/36454F?text=Milk" class="w-12 h-12 rounded-lg object-cover mr-4" alt="Milk">
                                            <div>
                                                <p class="font-semibold text-text-base">Organic Milk</p>
                                                <p class="text-sm text-text-muted">Expires in 7 days</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="badge-success">Dairy</span>
                                            <button class="btn-subtle-sm">Edit</button>
                                        </div>
                                    </li>
                                    <li class="card flex items-center justify-between p-4">
                                        <div class="flex items-center">
                                            <img src="https://placehold.co/80x80/E8F5E9/36454F?text=Eggs" class="w-12 h-12 rounded-lg object-cover mr-4" alt="Eggs">
                                            <div>
                                                <p class="font-semibold text-text-base">Free-Range Eggs</p>
                                                <p class="text-sm text-text-muted">Expires in 3 days</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="badge-warning">Expiring Soon</span>
                                            <button class="btn-subtle-sm">Edit</button>
                                        </div>
                                    </li>
                                    <li class="card flex items-center justify-between p-4 opacity-70">
                                        <div class="flex items-center">
                                            <img src="https://placehold.co/80x80/E8F5E9/36454F?text=Bread" class="w-12 h-12 rounded-lg object-cover mr-4" alt="Bread">
                                            <div>
                                                <p class="font-semibold text-text-base">Sourdough Bread</p>
                                                <p class="text-sm text-text-muted">Expired Yesterday</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="badge-danger">Expired</span>
                                            <button class="btn-subtle-sm">Edit</button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-text-heading mb-3">Complex Form</h3>
                            <div class="combination-preview bg-bg-page max-w-full">
                                <h4 class="text-lg font-semibold text-text-heading mb-1">Add a New Pantry Item</h4>
                                <p class="text-sm text-text-muted mb-6">Fill out the details below to add a new item to your virtual pantry.</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="item-name-2" class="block text-sm font-medium text-text-base mb-1">Item Name</label>
                                        <input type="text" id="item-name-2" placeholder="e.g., Organic Milk" class="w-full">
                                    </div>
                                    <div>
                                        <label for="item-category-2" class="block text-sm font-medium text-text-base mb-1">Category</label>
                                        <select id="item-category-2" class="w-full">
                                            <option>Dairy</option><option>Produce</option><option>Pantry Staples</option>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="item-qty" class="block text-sm font-medium text-text-base mb-1">Quantity</label>
                                            <input type="number" id="item-qty" value="1" class="w-full">
                                        </div>
                                        <div>
                                            <label for="item-unit" class="block text-sm font-medium text-text-base mb-1">Unit</label>
                                            <input type="text" id="item-unit" placeholder="e.g., gallon" class="w-full">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="item-expiry" class="block text-sm font-medium text-text-base mb-1">Expiration Date</label>
                                        <input type="date" id="item-expiry" class="w-full">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="item-notes" class="block text-sm font-medium text-text-base mb-1">Notes (optional)</label>
                                        <textarea id="item-notes" rows="3" placeholder="e.g., Opened on Monday" class="w-full"></textarea>
                                    </div>
                                </div>
                                <div class="flex justify-end space-x-3 pt-6 mt-6 border-t border-border-default">
                                    <button class="btn-secondary-md">Cancel</button>
                                    <button class="btn-cta-md">Save Item</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>
            </section>

        </div>
    </main>
</div>

<!-- Enhancement script: wrap each demo block with a wide right-side code panel -->
<script>
    (function(){
        const SELECTORS = ['.sg-item-card', '.combination-preview'];

        const escapeHtml = (str) =>
            str.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

        // Best-effort pretty-printer (keeps attributes; formats nesting)
        const pretty = (html) => {
            try {
                const voids = /^(?:area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)$/i;
                const parts = html.trim().split(/>\s*</);
                let out = '', indent = 0;
                parts.forEach((p,i)=>{
                    if (p.match(/^\/\w/)) indent = Math.max(indent-1,0);
                    out += (i?'\n':'') + '  '.repeat(indent) + '<' + p + '>';
                    if (p.match(/^(\w|!DOCTYPE|!--)/) && !p.match(/\/$/) && !p.startsWith('!--') && !p.match(new RegExp('^'+voids.source))) indent++;
                    if (p.startsWith('!--') && !p.endsWith('--')) indent++;
                    if (p.endsWith('--')) indent = Math.max(indent-1,0);
                });
                return out;
            } catch(e){ return html; }
        };

        const makePair = (el, snapshot) => {
            if (el.closest('.sg-code-pair')) return;

            const pair = document.createElement('div');
            pair.className = 'sg-code-pair';

            const left = document.createElement('div');
            left.className = 'sg-preview';
            el.parentNode.insertBefore(pair, el);
            pair.appendChild(left);
            left.appendChild(el);

            const right = document.createElement('div');
            right.className = 'sg-code';

            const btn = document.createElement('button');
            btn.className = 'sg-copy';
            btn.type = 'button';
            btn.textContent = 'Copy';

            btn.addEventListener('click', async () => {
                try{
                    await navigator.clipboard.writeText(snapshot);
                    btn.textContent = 'Copied!';
                    setTimeout(()=> btn.textContent = 'Copy', 1200);
                }catch(e){
                    btn.textContent = 'Failed';
                    setTimeout(()=> btn.textContent = 'Copy', 1200);
                }
            });

            const pre = document.createElement('pre');
            pre.innerHTML = escapeHtml(pretty(snapshot));

            right.appendChild(btn);
            right.appendChild(pre);
            pair.appendChild(right);
        };

        // Capture all targets' outerHTML before any DOM changes
        const targets = [];
        SELECTORS.forEach(sel => {
            document.querySelectorAll(sel).forEach(node => {
                if (!node.closest('.sg-code-pair')) {
                    targets.push({ node, html: node.outerHTML });
                }
            });
        });

        // Build pairs (each spans full width of any parent grid)
        targets.forEach(({node, html}) => makePair(node, html));
    })();
</script>
</body>
</html>
