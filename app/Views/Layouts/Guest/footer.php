<footer class="border-t border-border-default bg-bg-component py-10 mt-0">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-2">
                <span class="inline-block w-5 h-5 rounded-md" style="background: var(--color-cta);"></span>
                <span class="font-semibold text-text-heading">PantryPal</span>
                <span class="text-text-muted text-sm">— Cook what you have.</span>
            </div>
            <nav class="flex items-center gap-5 text-sm">
                <a href="/features" class="text-text-muted hover:text-text-heading">Features</a>
                <a href="/about" class="text-text-muted hover:text-text-heading">About</a>
                <a href="https://platform.fatsecret.com" target="_blank" rel="noopener" class="text-text-muted hover:text-text-heading">Data: FatSecret</a>
            </nav>
        </div>
        <p class="text-text-subtle text-xs text-center mt-6">&copy; <span id="current-year"></span> PantryPal. Reduce waste, save money, eat well.</p>
    </div>
</footer>

<script>
    document.getElementById('current-year').textContent = new Date().getFullYear();
</script>

</body>
</html>
