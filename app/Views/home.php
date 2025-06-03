<?php include 'Layouts/Guest/header.php'; ?>

<section class="hero-bg py-16 sm:py-24">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-[#36454F] leading-tight">
            Smart Pantry Management with <span class="text-[#4CAF50]">PantryPal</span>
        </h1>
        <p class="mt-6 max-w-2xl mx-auto text-lg sm:text-xl text-gray-600">
            Effortlessly track your groceries, monitor expiration dates, reduce food waste, and discover recipes with what you have on hand.
        </p>
        <div class="mt-10">
            <a href="#" class="btn-primary inline-block px-8 py-3 rounded-lg text-lg font-semibold shadow-md hover:shadow-lg transform hover:scale-105">
                Get Started
            </a>
            <p class="mt-3 text-sm text-gray-500">(Conceptual: Links to App Sign Up / Login)</p>
        </div>
    </div>
</section>

<section id="about" class="py-16 sm:py-20 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold section-title">What is PantryPal?</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                PantryPal is your digital assistant for a smarter, more organized kitchen. Our mission is to help you minimize food waste, save money, and make meal planning easier.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <img src="https://placehold.co/600x450/E8F5E9/36454F?text=Pantry+Organization" alt="Organized Pantry Illustration" class="rounded-lg shadow-xl w-full h-auto object-cover">
            </div>
            <div class="space-y-6 text-gray-700">
                <p class="leading-relaxed text-lg">
                    Tired of finding expired food in the back of your fridge or pantry? PantryPal Core helps you keep track of all your items, their purchase dates, and crucially, their expiration dates.
                </p>
                <p class="leading-relaxed">
                    Our web application allows you to easily add, view, edit, and delete items. Categorize them for better organization and note quantities to know what you have at a glance. With upcoming features like expiration alerts and recipe suggestions based on your current inventory, PantryPal aims to be an indispensable tool for any household.
                </p>
                <p class="leading-relaxed">
                    Built with a focus on simplicity and utility, using reliable technologies like PHP for its core logic and Tailwind CSS for a clean interface.
                </p>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-16 sm:py-20 hero-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold section-title">PantryPal Features</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                Everything you need to manage your kitchen inventory effectively.
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 text-center">
                <div class="feature-icon">🛒</div> {/* Unicode for shopping cart */}
                <h3 class="text-xl font-semibold text-[#36454F] mb-2">Item Tracking</h3>
                <p class="text-gray-700 leading-relaxed">
                    Easily add, view, edit, and delete pantry and fridge items. Keep a detailed inventory of what you have.
                </p>
            </div>
            <div class="bg-white p-8 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 text-center">
                <div class="feature-icon">📅</div> {/* Unicode for calendar */}
                <h3 class="text-xl font-semibold text-[#36454F] mb-2">Expiration Monitoring</h3>
                <p class="text-gray-700 leading-relaxed">
                    Record purchase and expiration dates. Get alerts for items nearing their end to reduce spoilage.
                </p>
            </div>
            <div class="bg-white p-8 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 text-center">
                <div class="feature-icon">📋</div> {/* Unicode for clipboard/list */}
                <h3 class="text-xl font-semibold text-[#36454F] mb-2">Categorization & Quantity</h3>
                <p class="text-gray-700 leading-relaxed">
                    Organize items by category and keep track of quantities to simplify shopping and meal prep.
                </p>
            </div>
            <div class="bg-white p-8 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 text-center sm:col-span-2 lg:col-span-1 lg:col-start-2">
                <div class="feature-icon">🍳</div> {/* Unicode for frying pan/cooking */}
                <h3 class="text-xl font-semibold text-[#36454F] mb-2">Recipe Suggestions (Coming Soon)</h3>
                <p class="text-gray-700 leading-relaxed">
                    Discover simple recipes based on the ingredients you already have in your pantry, making meal ideas effortless.
                </p>
            </div>
        </div>
        <div class="text-center mt-12">
            <a href="#" class="btn-primary inline-block px-8 py-3 rounded-lg text-lg font-semibold shadow-md hover:shadow-lg transform hover:scale-105">
                Sign Up & Organize Your Pantry
            </a>
            <p class="mt-3 text-sm text-gray-500">(Conceptual: Links to App Sign Up)</p>
        </div>
    </div>
</section>

<?php include_once 'Layouts/Guest/footer.php'; ?>

