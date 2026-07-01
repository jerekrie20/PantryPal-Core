<?php
require_once VIEW_PATH . '/Components/ui_elements.php';

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf     = $_SESSION['csrf_token'] ?? '';
$isEdit   = $isEdit ?? false;
$recipeId = $recipeId ?? null;
$input    = $input ?? $_POST ?? [];
$action   = $isEdit ? '/recipes/' . (int)$recipeId . '/edit' : '/recipes';

ob_start();

$actions = '';
if ($isEdit && $recipeId) {
    $actions .= '<a href="/recipes/view/' . (int)$recipeId . '" class="btn btn-ghost btn-md">View recipe</a>';
}
$actions .= '<a href="/recipes" class="btn btn-ghost btn-md">Back to recipes</a>';
?>

<?php ui_page_header(
    $isEdit ? 'Edit recipe' : 'Add a recipe',
    $isEdit ? 'Update the details below.' : 'Share your own recipe.',
    $actions,
    $isEdit ? 'Editing' : 'New recipe'
); ?>

<div class="card max-w-3xl mx-auto">
    <?php if (!empty($error)): ?>
        <div class="alert-danger text-sm mb-4"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <div>
            <label for="title" class="block text-sm font-medium mb-1">Title <span class="text-danger">*</span></label>
            <input id="title" class="w-full" type="text" name="title" required placeholder="e.g. Easy lasagna" value="<?= e($input['title'] ?? '') ?>">
        </div>

        <div>
            <label for="description" class="block text-sm font-medium mb-1">Description</label>
            <textarea id="description" class="w-full" name="description" rows="3" placeholder="Short description of the recipe"><?= e($input['description'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Recipe image</label>
            <?php $existingImg = $input['image_url'] ?? ''; ?>
            <?php if ($isEdit && !empty($existingImg)): ?>
                <div class="mb-2 flex items-center gap-3">
                    <img src="<?= e($existingImg) ?>" alt="Current image" class="h-20 w-32 object-cover rounded border border-border-default">
                    <span class="text-xs text-text-muted">Current image. Upload a new file to replace it.</span>
                </div>
            <?php endif; ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="image_file" class="block text-xs text-text-muted mb-1">Upload a file <span>(JPEG, PNG or WebP, max 5 MB)</span></label>
                    <input id="image_file" class="w-full" type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
                </div>
                <div>
                    <label for="image_url" class="block text-xs text-text-muted mb-1">— or paste an image URL</label>
                    <input id="image_url" class="w-full" type="url" name="image_url" placeholder="https://…" value="<?= e($existingImg) ?>">
                </div>
            </div>
        </div>

        <div>
            <label for="source_url" class="block text-sm font-medium mb-1">Source URL <span class="text-xs text-text-muted font-normal">(optional)</span></label>
            <input id="source_url" class="w-full" type="url" name="source_url" placeholder="https://…" value="<?= e($input['source_url'] ?? '') ?>">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="ingredients_list" class="block text-sm font-medium mb-1">Ingredients</label>
                <textarea id="ingredients_list" class="w-full font-mono text-sm" name="ingredients_list" rows="8"
                          placeholder="One per line&#10;12 lasagna noodles&#10;2 cups mozzarella&#10;1 jar marinara"><?= e($input['ingredients_list'] ?? '') ?></textarea>
                <p class="text-xs text-text-muted mt-1">One per line, comma-separated, or a JSON array.</p>
            </div>
            <div>
                <label for="instructions_list" class="block text-sm font-medium mb-1">Instructions</label>
                <textarea id="instructions_list" class="w-full font-mono text-sm" name="instructions_list" rows="8"
                          placeholder="One step per line&#10;Boil noodles until al dente&#10;Layer noodles, sauce, and cheese&#10;Bake at 375°F for 25 minutes"><?= e($input['instructions_list'] ?? '') ?></textarea>
                <p class="text-xs text-text-muted mt-1">One step per line or a JSON array.</p>
            </div>
        </div>

        <div>
            <label for="nutrition_per_serving" class="block text-sm font-medium mb-1">Nutrition per serving <span class="text-xs text-text-muted font-normal">(optional)</span></label>
            <textarea id="nutrition_per_serving" class="w-full font-mono text-sm" name="nutrition_per_serving" rows="5"
                      placeholder="Calories: 350 kcal&#10;Protein: 18 g&#10;Carbohydrates: 45 g&#10;Fat: 12 g"><?= e($input['nutrition_per_serving'] ?? '') ?></textarea>
            <p class="text-xs text-text-muted mt-1">One nutrient per line (<code class="font-mono text-xs">Label: amount unit</code>), or paste a JSON object.</p>
        </div>

        <div class="pt-4 border-t border-border-default flex flex-col-reverse sm:flex-row gap-2 sm:justify-between">
            <?php if ($isEdit && $recipeId): ?>
                <form method="post" action="/recipes/<?= (int)$recipeId ?>/delete" onsubmit="return confirm('Permanently delete this recipe?');" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn btn-danger btn-md w-full sm:w-auto">Delete recipe</button>
                </form>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <div class="flex flex-col-reverse sm:flex-row gap-2">
                <a href="<?= $isEdit && $recipeId ? '/recipes/view/' . (int)$recipeId : '/recipes' ?>" class="btn btn-ghost btn-md w-full sm:w-auto">Cancel</a>
                <button type="submit" class="btn btn-cta btn-md w-full sm:w-auto"><?= $isEdit ? 'Save changes' : 'Publish recipe' ?></button>
            </div>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
