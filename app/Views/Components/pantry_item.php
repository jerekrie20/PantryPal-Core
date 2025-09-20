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
            <a href="/items/view/<?php echo $item['id']; ?>" class="text-text-base font-semibold hover:underline"><?php echo htmlspecialchars($item['name']); ?></a>
            <p class="text-sm text-text-muted"><?php echo htmlspecialchars($item['status']); ?></p>
        </div>
    </div>
    <div class="flex items-center space-x-2 self-end sm:self-center flex-shrink-0">
        <span class="badge <?php echo $item['badge_class']; ?> hidden md:inline-block"><?php echo htmlspecialchars($item['category']); ?></span>
        <a href="/items/edit/<?php echo $item['id']; ?>" class="btn btn-subtle btn-sm" aria-label="Edit <?php echo htmlspecialchars($item['name']); ?>">Edit</a>
        <button class="btn btn-danger btn-sm" aria-label="Delete <?php echo htmlspecialchars($item['name']); ?>">Delete</button>
    </div>
</li>
