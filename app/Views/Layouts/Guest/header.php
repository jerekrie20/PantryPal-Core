<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' · ' : '' ?>PantryPal</title>
    <?php echo vite_tags(); ?>

<body class="antialiased">

<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="logo-leaf mr-4"></div>
                <a href="#" class="text-2xl font-bold text-[#36454F]">PantryPal</a>
            </div>
            <nav class="hidden md:flex space-x-8">
                <a href="/" class="font-medium text-gray-600 hover:text-primary">Home</a>
                <a href="/about" class="font-medium text-gray-600 hover:text-primary">About</a>
                <a href="/features" class="font-medium text-gray-600 hover:text-primary">Features</a>
                <?php if (!empty($_SESSION['user_id'])) { ?>
                    <a href="/dashboard" class="font-medium text-gray-600 hover:text-primary">Dashboard</a>
                    <a href="/logout" class="font-medium text-gray-600 hover:text-primary">Logout</a>
                <?php } else { ?>
                    <a href="/login" class="font-medium text-gray-600 hover:text-primary">Login</a>
                <?php } ?>
                <a href="/overview" class="font-medium text-gray-600 hover:text-primary">Learning</a>
                <a href="/theme" class="font-medium text-gray-600 hover:text-primary">Theme</a>
            </nav>
            <div class="md:hidden">
                <button id="mobile-menu-button"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16m-7 6h7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <div class="md:hidden hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="#"
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Home</a>
            <a href="#about"
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">About</a>
            <a href="#features"
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Features</a>

            <?php if (!empty($_SESSION['user_id'])) { ?>
                <a href="/dashboard"
                   class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Dashboard</a>
                <a href="/logout"
                   class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Logout</a>
            <?php } else { ?>
                <a href="/login"
                   class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Login</a>
            <?php } ?>

            <a href="/app/Views/Learning/overview.html"
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Learning</a>

            <a href="/app/Views/Learning/theme.php"
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-white hover:bg-primary">Theme</a>
        </div>
    </div>
</header>

