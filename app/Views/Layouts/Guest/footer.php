<footer class="footer-bg py-8 text-center">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center mb-2">
            <div class="logo-leaf !bg-[#4CAF50]"></div>
            <a href="https://platform.fatsecret.com">
                <img alt="Nutrition information provided by fatsecret Platform API" src="https://platform.fatsecret.com/api/static/images/powered_by_fatsecret_horizontal_brand.svg" border="0"/>
            </a>
        </div>
        <p class="text-gray-600 text-sm">
            &copy; <span id="current-year"></span> PantryPal. Reduce waste, save money, eat well.
        </p>
    </div>
</footer>

<script>
    document.getElementById('current-year').textContent = new Date().getFullYear();

    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
</script>

</body>
</html>

