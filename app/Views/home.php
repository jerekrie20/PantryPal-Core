<?php include 'Layouts/Guest/header.php'; ?>

<section class="hero-bg py-16 sm:py-24">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-[#36454F] leading-tight">
            Smart Pantry Management with <span class="text-[#4CAF50]">PantryPal</span>
        </h1>
        <p class="mt-6 max-w-2xl mx-auto text-lg sm:text-xl text-gray-600">
            Effortlessly track your groceries, monitor expiration dates, reduce food waste, and discover recipes with what you have on hand.
        </p>
        <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/register" class="btn-primary inline-block px-8 py-3 rounded-lg text-lg font-semibold shadow-md hover:shadow-lg transform hover:scale-105">
                Get Started Free
            </a>
            <a href="/login" class="btn-secondary inline-block px-8 py-3 rounded-lg text-lg font-semibold">
                Log In
            </a>
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
                <img src="/images/home/pantry.webp" alt="Organized Pantry Illustration" class="rounded-lg shadow-xl w-full h-auto object-cover">
            </div>
            <div class="space-y-6 text-gray-700">
                <p class="leading-relaxed text-lg">
                    Tired of finding expired food in the back of your fridge or pantry? PantryPal helps you keep track of all your items, their purchase dates, and their expiration dates.
                </p>
                <p class="leading-relaxed">
                    Add, view, edit, and delete items. Categorize for better organization and track quantities so you always know what you have at a glance.
                </p>
                <p class="leading-relaxed">
                    Built for simplicity and speed using PHP and Tailwind CSS.
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
                <div class="feature-icon text-3xl mb-2">🛒</div>
                <h3 class="text-xl font-semibold text-[#36454F] mb-2">Item Tracking</h3>
                <p class="text-gray-700 leading-relaxed">
                    Easily add, view, edit, and delete pantry and fridge items.
                </p>
            </div>
            <div class="bg-white p-8 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 text-center">
                <div class="feature-icon text-3xl mb-2">📅</div>
                <h3 class="text-xl font-semibold text-[#36454F] mb-2">Expiration Monitoring</h3>
                <p class="text-gray-700 leading-relaxed">
                    Record purchase and expiration dates; reduce spoilage with timely reminders.
                </p>
            </div>
            <div class="bg-white p-8 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 text-center">
                <div class="feature-icon text-3xl mb-2">📋</div>
                <h3 class="text-xl font-semibold text-[#36454F] mb-2">Categorization & Quantity</h3>
                <p class="text-gray-700 leading-relaxed">
                    Organize items by category and keep track of quantities.
                </p>
            </div>
            <div class="bg-white p-8 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 text-center sm:col-span-2 lg:col-span-1 lg:col-start-2">
                <div class="feature-icon text-3xl mb-2">🍳</div>
                <h3 class="text-xl font-semibold text-[#36454F] mb-2">Recipe Suggestions</h3>
                <p class="text-gray-700 leading-relaxed">
                    Discover recipes based on ingredients you already have.
                </p>
            </div>
        </div>
        <div class="text-center mt-12">
            <a href="/register" class="btn-primary inline-block px-8 py-3 rounded-lg text-lg font-semibold shadow-md hover:shadow-lg transform hover:scale-105">
                Create Your Free Account
            </a>
        </div>
    </div>
</section>

<?php include_once 'Layouts/Guest/footer.php'; ?>

