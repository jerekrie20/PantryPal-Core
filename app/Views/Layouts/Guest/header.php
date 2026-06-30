<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' · ' : '' ?>PantryPal</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap" rel="stylesheet">
    <?php echo vite_tags(); ?>
</head>
<body class="bg-bg-page text-text-base font-body antialiased">

<header class="sticky top-0 z-50 bg-bg-component/90 backdrop-blur border-b border-border-default">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2 group" aria-label="PantryPal home">
                <span class="inline-block w-6 h-6 rounded-md" style="background: var(--color-cta);"></span>
                <span class="text-xl font-semibold text-text-heading">PantryPal</span>
            </a>

            <nav class="hidden md:flex items-center gap-1">
                <a href="/features" class="btn btn-ghost btn-sm">Features</a>
                <a href="/about" class="btn btn-ghost btn-sm">About</a>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="btn btn-ghost btn-sm">Dashboard</a>
                    <form action="/logout" method="POST" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-ghost btn-sm">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="/login" class="btn btn-ghost btn-sm">Log in</a>
                    <a href="/register" class="btn btn-cta btn-sm ml-2">Get started</a>
                <?php endif; ?>
            </nav>

            <div class="md:hidden">
                <details class="group relative">
                    <summary class="list-none inline-flex items-center justify-center text-text-muted hover:text-text-heading cursor-pointer p-2 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-cta-focus-ring)]">
                        <span class="sr-only">Toggle menu</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </summary>
                    <div class="group-open:block hidden absolute right-0 mt-2 w-56 bg-bg-component border border-border-default rounded-xl shadow-lg">
                        <div class="p-2 space-y-1">
                            <a href="/features" class="block px-3 py-2 rounded-md text-sm font-medium text-text-base hover:bg-bg-subtle">Features</a>
                            <a href="/about" class="block px-3 py-2 rounded-md text-sm font-medium text-text-base hover:bg-bg-subtle">About</a>
                            <?php if (!empty($_SESSION['user_id'])): ?>
                                <a href="/dashboard" class="block px-3 py-2 rounded-md text-sm font-medium text-text-base hover:bg-bg-subtle">Dashboard</a>
                                <form action="/logout" method="POST" class="block">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-text-base hover:bg-bg-subtle">Logout</button>
                                </form>
                            <?php else: ?>
                                <a href="/login" class="block px-3 py-2 rounded-md text-sm font-medium text-text-base hover:bg-bg-subtle">Log in</a>
                                <a href="/register" class="block mx-2 mt-1 btn btn-cta btn-sm">Get started</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</header>
