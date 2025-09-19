<?php
// This component expects an $item (array) variable.
?>
<li class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 <?php echo isset($item['expired']) && $item['expired'] ? 'opacity-60' : ''; ?>">
    <div class="flex items-center w-full sm:w-auto">
        <img src="https://placehold.co/80x80/E8F5E9/36454F?text=<?php echo urlencode($item['image']); ?>" class="w-12 h-12 rounded-lg object-cover mr-4 flex-shrink-0" alt="Image of <?php echo htmlspecialchars($item['name']); ?>">
        <div class="flex-grow">
            <p class="font-semibold text-text-base"><?php echo htmlspecialchars($item['name']); ?></p>
            <p class="text-sm text-text-muted"><?php echo htmlspecialchars($item['status']); ?></p>
        </div>
    </div>
    <div class="flex items-center space-x-2 self-end sm:self-center flex-shrink-0">
        <span class="badge <?php echo $item['badge_class']; ?> hidden md:inline-block"><?php echo htmlspecialchars($item['category']); ?></span>
        <a href="/items/edit/<?php echo $item['id']; ?>" class="btn btn-subtle btn-sm" aria-label="Edit <?php echo htmlspecialchars($item['name']); ?>">Edit</a>
        <button class="btn btn-danger btn-sm" aria-label="Delete <?php echo htmlspecialchars($item['name']); ?>">Delete</button>
    </div>
</li>
