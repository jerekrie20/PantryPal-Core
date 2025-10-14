<?php
// This component expects an $item (array) variable.
// Determine image source: use actual URL if provided, else fallback placeholder with item name.
$imageSrc = null;
if (!empty($item['image']) && preg_match('#^https?://#i', $item['image'])) {
    $imageSrc = $item['image'];
} else {
    $placeholderText = !empty($item['name']) ? $item['name'] : 'Item';
    $imageSrc = 'https://placehold.co/80x80/E8F5E9/36454F?text=' . urlencode($placeholderText);
}
?>
<li class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 <?php echo isset($item['expired']) && $item['expired'] ? 'opacity-60' : ''; ?>">
    <div class="flex items-center w-full sm:w-auto">
        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="w-12 h-12 rounded-lg object-cover mr-4 flex-shrink-0" alt="Image of <?php echo htmlspecialchars($item['name'] ?? 'Item'); ?>">
        <div class="flex-grow">
            <a href="<?php echo htmlspecialchars($item['url'] ?? ('/items/view/' . $item['id'])); ?>" class="text-text-base font-semibold hover:underline"><?php echo htmlspecialchars($item['name']); ?></a>
            <p class="text-sm text-text-muted"><?php echo htmlspecialchars($item['status']); ?></p>
        </div>
    </div>
    <div class="flex items-center space-x-2 self-end sm:self-center flex-shrink-0">
        <span class="badge <?php echo $item['badge_class']; ?> hidden md:inline-block max-w-xs md:max-w-sm lg:max-w-md overflow-hidden text-ellipsis whitespace-nowrap" title="<?php echo htmlspecialchars($item['category']); ?>"><?php echo htmlspecialchars($item['category']); ?></span>
        <a href="/items/<?php echo $item['id']; ?>/edit" class="btn btn-subtle btn-sm" aria-label="Edit <?php echo htmlspecialchars($item['name']); ?>">Edit</a>
        <?php if (isset($item['expired']) && $item['expired']) : ?>
            <a href="/items/renew/<?php echo $item['id']; ?>" class="btn btn-subtle btn-sm" aria-label="Renew <?php echo htmlspecialchars($item['name']); ?>">Renew</a>
        <?php endif; ?>
        <form action="/items/<?php echo $item['id']; ?>/delete" method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete &quot;<?php echo htmlspecialchars($item['name']); ?>&quot;? This action cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            <button type="submit" class="btn btn-danger btn-sm" aria-label="Delete <?php echo htmlspecialchars($item['name']); ?>">Delete</button>
        </form>

    </div>
</li>
