<?php
// This component expects the following variables:
// $title (string), $value (string), $description (string)
// Optional: $accent_color (string), $link_href (string), $link_text (string)
?>
<div class="card p-6 flex flex-col justify-between">
    <div>
        <h2 class="text-lg font-semibold text-text-heading"><?php echo htmlspecialchars($title); ?></h2>
        <p class="text-4xl font-bold mt-2 <?php echo isset($accent_color) ? htmlspecialchars($accent_color) : 'text-text-base'; ?>"><?php echo htmlspecialchars($value); ?></p>
        <p class="text-sm text-text-muted mt-1"><?php echo htmlspecialchars($description); ?></p>
    </div>
    <?php if (isset($link_href) && isset($link_text)): ?>
        <a href="<?php echo htmlspecialchars($link_href); ?>" class="btn btn-secondary btn-sm mt-4 self-start"><?php echo htmlspecialchars($link_text); ?></a>
    <?php endif; ?>
</div>
