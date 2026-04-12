<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf     = $_SESSION['csrf_token'] ?? '';
$isEdit   = $isEdit ?? false;
$recipeId = $recipeId ?? null;
$input    = $input ?? $_POST ?? [];
$recipe   = $recipe ?? null;
$action   = $isEdit ? '/recipes/' . (int)$recipeId . '/edit' : '/recipes';
ob_start();
if (!function_exists('e')) { function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); } }
?>
<section class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between">
  <div>
    <h1 class="text-3xl font-bold text-text-heading"><?php echo e($title ?? ($isEdit ? 'Edit Recipe' : 'Add Recipe')); ?></h1>
    <p class="text-text-muted mt-1"><?php echo $isEdit ? 'Update your recipe details below.' : 'Share your own recipe with the community.'; ?></p>
  </div>
  <div class="mt-3 sm:mt-0 flex gap-2">
    <?php if ($isEdit && $recipeId): ?>
      <a href="/recipes/view/<?php echo (int)$recipeId; ?>" class="btn btn-subtle">View Recipe</a>
    <?php endif; ?>
    <a href="/recipes" class="btn btn-subtle">Back to Recipes</a>
  </div>
</section>

<div class="card p-4 md:p-6">
  <?php if (!empty($error)): ?>
    <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm"><?php echo e($error); ?></div>
  <?php endif; ?>

  <form method="post" action="<?php echo e($action); ?>" enctype="multipart/form-data" class="space-y-5">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">

    <!-- Title -->
    <div>
      <label class="block text-sm font-medium mb-1">Title <span class="text-red-500">*</span></label>
      <input class="input w-full" type="text" name="title" required placeholder="e.g., Easy Lasagna"
             value="<?php echo e($input['title'] ?? ''); ?>">
    </div>

    <!-- Description -->
    <div>
      <label class="block text-sm font-medium mb-1">Description</label>
      <textarea class="input w-full" name="description" rows="3"
                placeholder="Short description of the recipe"><?php echo e($input['description'] ?? ''); ?></textarea>
    </div>

    <!-- Image -->
    <div>
      <label class="block text-sm font-medium mb-1">Recipe Image</label>
      <?php $existingImg = $input['image_url'] ?? ''; ?>
      <?php if ($isEdit && !empty($existingImg)): ?>
        <div class="mb-2 flex items-center gap-3">
          <img src="<?php echo e($existingImg); ?>" alt="Current image"
               class="h-20 w-32 object-cover rounded border border-border-default">
          <span class="text-xs text-text-muted">Current image — upload a new file below to replace it.</span>
        </div>
      <?php endif; ?>
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <label class="block text-xs text-text-muted mb-1">Upload a file <span class="text-text-muted">(JPEG, PNG or WebP, max 5 MB)</span></label>
          <input class="input w-full" type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="flex-1">
          <label class="block text-xs text-text-muted mb-1">— or paste an image URL</label>
          <input class="input w-full" type="url" name="image_url" placeholder="https://..."
                 value="<?php echo e($existingImg); ?>">
        </div>
      </div>
    </div>

    <!-- Source URL -->
    <div>
      <label class="block text-sm font-medium mb-1">Source URL <span class="text-xs text-text-muted">(optional)</span></label>
      <input class="input w-full" type="url" name="source_url" placeholder="https://..."
             value="<?php echo e($input['source_url'] ?? ''); ?>">
    </div>

    <!-- Ingredients & Instructions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Ingredients</label>
        <textarea class="input w-full font-mono text-sm" name="ingredients_list" rows="8"
                  placeholder="One per line&#10;12 lasagna noodles&#10;2 cups mozzarella&#10;1 jar marinara"><?php echo e($input['ingredients_list'] ?? ''); ?></textarea>
        <p class="text-xs text-text-muted mt-1">One per line, comma-separated, or a JSON array.</p>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Instructions</label>
        <textarea class="input w-full font-mono text-sm" name="instructions_list" rows="8"
                  placeholder="One step per line&#10;Boil noodles until al dente&#10;Layer noodles, sauce, and cheese&#10;Bake at 375°F for 25 minutes"><?php echo e($input['instructions_list'] ?? ''); ?></textarea>
        <p class="text-xs text-text-muted mt-1">One step per line or a JSON array.</p>
      </div>
    </div>

    <!-- Nutrition -->
    <div>
      <label class="block text-sm font-medium mb-1">Nutrition per serving <span class="text-xs text-text-muted">(optional)</span></label>
      <textarea class="input w-full font-mono text-sm" name="nutrition_per_serving" rows="5"
                placeholder="Calories: 350 kcal&#10;Protein: 18 g&#10;Carbohydrates: 45 g&#10;Fat: 12 g"><?php echo e($input['nutrition_per_serving'] ?? ''); ?></textarea>
      <p class="text-xs text-text-muted mt-1">One nutrient per line (<code>Label: amount unit</code>), or paste a JSON object.</p>
    </div>

    <!-- Actions -->
    <div class="pt-2 flex gap-2 flex-wrap">
      <button type="submit" class="btn btn-cta"><?php echo $isEdit ? 'Save Changes' : 'Publish Recipe'; ?></button>
      <?php if ($isEdit && $recipeId): ?>
        <a href="/recipes/view/<?php echo (int)$recipeId; ?>" class="btn btn-subtle">Cancel</a>
        <form method="post" action="/recipes/<?php echo (int)$recipeId; ?>/delete"
              onsubmit="return confirm('Permanently delete this recipe?');" class="inline">
          <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
          <button type="submit" class="btn btn-danger">Delete Recipe</button>
        </form>
      <?php else: ?>
        <a href="/recipes" class="btn btn-subtle">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
