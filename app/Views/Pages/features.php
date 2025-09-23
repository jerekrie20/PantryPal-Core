<?php include VIEW_PATH . '/Layouts/Guest/header.php'; ?>

<section class="py-16 sm:py-24 hero-bg">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto text-center mb-10">
      <h1 class="text-4xl sm:text-5xl font-extrabold text-[#36454F]">Features</h1>
      <p class="mt-4 text-lg text-gray-600">Tools to keep your kitchen organized and your meals on track.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-xl shadow">
        <div class="text-3xl mb-2">🛒</div>
        <h3 class="text-xl font-semibold text-[#36454F] mb-1">Inventory</h3>
        <p class="text-gray-700">Add items with quantity, unit, category, and images. Edit or remove as you go.</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <div class="text-3xl mb-2">📅</div>
        <h3 class="text-xl font-semibold text-[#36454F] mb-1">Expiration Tracking</h3>
        <p class="text-gray-700">Track purchase and expiration dates and see what needs attention first.</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <div class="text-3xl mb-2">🍳</div>
        <h3 class="text-xl font-semibold text-[#36454F] mb-1">Recipes</h3>
        <p class="text-gray-700">Find and save recipes, including suggestions that use what you already have.</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <div class="text-3xl mb-2">📊</div>
        <h3 class="text-xl font-semibold text-[#36454F] mb-1">Dashboard</h3>
        <p class="text-gray-700">A quick view of totals, expiring soon, and saved recipes.</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <div class="text-3xl mb-2">📱</div>
        <h3 class="text-xl font-semibold text-[#36454F] mb-1">Mobile Friendly</h3>
        <p class="text-gray-700">Responsive pages and navigation work great on phones and tablets.</p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <div class="text-3xl mb-2">🔒</div>
        <h3 class="text-xl font-semibold text-[#36454F] mb-1">Your Data</h3>
        <p class="text-gray-700">Your pantry stays your pantry. You control your items and saved recipes.</p>
      </div>
    </div>

    <div class="text-center mt-12">
      <a href="/register" class="btn-primary inline-block px-8 py-3 rounded-lg text-lg font-semibold shadow-md hover:shadow-lg transform hover:scale-105">Get started</a>
      <a href="/login" class="btn-secondary inline-block px-8 py-3 rounded-lg text-lg font-semibold ml-3">Log in</a>
    </div>
  </div>
</section>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>
