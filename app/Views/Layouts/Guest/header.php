<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' · ' : '' ?>PantryPal</title>
    <?php echo vite_tags(); ?>
</head>
<body class="antialiased">

<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="logo-leaf mr-4"></div>
                <a href="/" class="text-2xl font-bold text-[#36454F]">PantryPal</a>
            </div>
            <nav class="hidden md:flex space-x-8">
                <a href="/" class="font-medium text-gray-600 hover:text-primary">Home</a>
                <a href="/about" class="font-medium text-gray-600 hover:text-primary">About</a>
                <a href="/features" class="font-medium text-gray-600 hover:text-primary">Features</a>
                <?php if (!empty($_SESSION['user_id'])) { ?>
                    <a href="/dashboard" class="font-medium text-gray-600 hover:text-primary">Dashboard</a>
                    <form action="/logout" method="POST" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="font-medium text-gray-600 hover:text-primary">Logout</button>
                    </form>
                <?php } else { ?>
                    <a href="/login" class="font-medium text-gray-600 hover:text-primary">Login</a>
                <?php } ?>
            </nav>
            <div class="md:hidden">
                <details class="group">
                    <summary class="list-none inline-flex items-center justify-center text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary cursor-pointer">
                        <span class="sr-only">Toggle main menu</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </summary>
                    <div class="group-open:block hidden bg-white border-t border-border-default shadow-sm">
                        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                            <a href="/" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Home</a>
                            <a href="/about" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">About</a>
                            <a href="/features" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Features</a>

                            <?php if (!empty($_SESSION['user_id'])) { ?>
                                <a href="/dashboard" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Dashboard</a>
                                <form action="/logout" method="POST" class="block">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                                    <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Logout</button>
                                </form>
                            <?php } else { ?>
                                <a href="/login" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Login</a>
                            <?php } ?>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</header>

