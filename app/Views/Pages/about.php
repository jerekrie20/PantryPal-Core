<?php include VIEW_PATH . '/Layouts/Guest/header.php'; ?>

<section class="py-16 sm:py-24">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto text-center mb-10">
      <h1 class="text-4xl sm:text-5xl font-extrabold text-[#36454F]">About PantryPal</h1>
      <p class="mt-4 text-lg text-gray-600">Our mission is to help you reduce food waste, save money, and plan meals with confidence.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
      <div class="space-y-5 text-gray-700">
        <h2 class="text-2xl font-semibold text-[#36454F]">Why we built PantryPal</h2>
        <p>Most households throw away food because it was forgotten or expired. PantryPal makes it easy to keep track of what you have and when it expires, so you can cook more and waste less.</p>
        <p>We pair a simple inventory with recipe discovery so you can quickly find meal ideas that use the ingredients already in your kitchen.</p>
        <h3 class="text-xl font-semibold text-[#36454F]">What you can do</h3>
        <ul class="list-disc list-inside space-y-2">
          <li>Add items with purchase and expiration dates</li>
          <li>Organize by category and track quantities</li>
          <li>See what's expiring soon at a glance</li>
          <li>Discover recipes using what you already have</li>
        </ul>
      </div>
      <div>
        <img src="/images/home/pantry.webp" alt="Organized pantry" class="rounded-xl shadow-lg w-full h-auto object-cover" />
      </div>
    </div>

    <div class="mt-12 max-w-3xl mx-auto">
      <h2 class="text-2xl font-semibold text-[#36454F] mb-3">Privacy and data</h2>
      <p class="text-gray-700">Your pantry data stays in your account. We do not sell your personal data. Recipe results may come from third-party providers, but your saved items are kept within PantryPal.</p>
    </div>

    <div class="mt-12 text-center">
      <a href="/register" class="btn-primary inline-block px-8 py-3 rounded-lg text-lg font-semibold shadow-md hover:shadow-lg transform hover:scale-105">Create a free account</a>
      <a href="/login" class="btn-secondary inline-block px-8 py-3 rounded-lg text-lg font-semibold ml-3">Log in</a>
    </div>
  </div>
</section>

<?php include VIEW_PATH . '/Layouts/Guest/footer.php'; ?>
