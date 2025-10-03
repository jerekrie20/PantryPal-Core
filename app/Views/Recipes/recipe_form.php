<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
$input = $input ?? $_POST ?? [];
ob_start();
?>
<section class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between">
  <div>
    <h1 class="text-3xl font-bold text-text-heading"><?php echo htmlspecialchars($title ?? 'Add Recipe'); ?></h1>
    <p class="text-text-muted mt-1">Share your own recipe with the community.</p>
  </div>
  <div class="mt-3 sm:mt-0">
    <a href="/recipes" class="btn btn-subtle">Back to Recipes</a>
  </div>
</section>

<div class="card p-4 md:p-6">
  <?php if (!empty($error)): ?>
    <div class="mb-4 text-red-600"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <form method="post" action="/recipes" class="space-y-4">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

    <div>
      <label class="block text-sm font-medium mb-1">Title <span class="text-red-600">*</span></label>
      <input class="input w-full" type="text" name="title" required placeholder="e.g., Easy Lasagna"
             value="<?php echo htmlspecialchars($input['title'] ?? ''); ?>">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Description</label>
      <textarea class="input w-full" name="description" rows="3" placeholder="Short description"><?php echo htmlspecialchars($input['description'] ?? ''); ?></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Image URL</label>
        <input class="input w-full" type="url" name="image_url" placeholder="https://..."
               value="<?php echo htmlspecialchars($input['image_url'] ?? ''); ?>">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Source URL <span class="text-xs text-text-muted">(optional)</span></label>
        <input class="input w-full" type="url" name="source_url" placeholder="https://..."
               value="<?php echo htmlspecialchars($input['source_url'] ?? ''); ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Ingredients</label>
        <textarea class="input w-full" name="ingredients_list" rows="6" placeholder="One per line, or separate with commas
Example:
12 lasagna noodles
2 cups mozzarella
1 jar marinara"><?php echo htmlspecialchars($input['ingredients_list'] ?? ''); ?></textarea>
        <p class="text-xs text-text-muted mt-1">You can also paste a JSON array if you have one.</p>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Instructions</label>
        <textarea class="input w-full" name="instructions_list" rows="6" placeholder="One step per line
Example:
Boil noodles until al dente
Layer noodles, sauce, and cheese
Bake at 375°F for 25 minutes"><?php echo htmlspecialchars($input['instructions_list'] ?? ''); ?></textarea>
        <p class="text-xs text-text-muted mt-1">JSON array is also accepted.</p>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Nutrition per serving</label>
      <textarea class="input w-full" name="nutrition_per_serving" rows="4" placeholder="One per line or comma-separated
Example:
Calories: 350 kcal
Protein 18 g
Carbohydrates 45 g
Fat 12 g"><?php echo htmlspecialchars($input['nutrition_per_serving'] ?? ''); ?></textarea>
      <p class="text-xs text-text-muted mt-1">Or paste a JSON object like {\"Calories\": {\"amount\": 350, \"unit\": \"kcal\"}, ...}</p>
    </div>

    <div class="pt-2 flex gap-2">
      <button type="submit" class="btn btn-cta">Publish Recipe</button>
      <a href="/recipes" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
