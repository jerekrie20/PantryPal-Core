<?php
/**
 * Confirm Item Selection View
 * @var array $choices The list of potential matches from the API.
 * @var array $original_input The user's original form submission.
 */
ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-text-heading">Confirm Your Item</h1>
        <p class="text-text-muted mt-2">We found a few matches for "<?php echo htmlspecialchars($original_input['name']); ?>". Please select the correct one.</p>
    </div>

    <div class="space-y-4">
        <?php foreach ($choices as $choice): ?>
            <div class="card p-4">
                <form action="/items/confirm" method="POST">
                    <!-- Pass original user input through hidden fields -->
                    <?php foreach ($original_input as $key => $value): ?>
                        <input type="hidden" name="original_input[<?php echo htmlspecialchars($key); ?>]" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endforeach; ?>

                    <!-- Pass the selected API ID -->
                    <input type="hidden" name="api_id" value="<?php echo htmlspecialchars($choice['api_id']); ?>">
                    <input type="hidden" name="api_kind" value="<?php echo htmlspecialchars($choice['type'] ?? 'ingredient'); ?>">

                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <img src="<?php echo htmlspecialchars($choice['image_url']); ?>" alt="" class="w-16 h-16 rounded-lg object-cover bg-bg-subtle flex-shrink-0">
                            <p class="font-semibold text-text-base"><?php echo htmlspecialchars($choice['name']); ?></p>
                        </div>
                        <button type="submit" class="btn btn-cta btn-md flex-shrink-0">Select</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-8">
        <a href="/items/create" class="text-sm font-medium">None of these look right? Go back.</a>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once VIEW_PATH . '/layouts/Users/layout.php';
?>
