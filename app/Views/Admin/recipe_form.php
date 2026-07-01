<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
$input = $input ?? $_POST ?? [];
$rid = isset($recipe_id) ? (int)$recipe_id : 0;
$action = $rid > 0 ? '/admin/recipes/' . $rid : '/admin/recipes';
$heading = $rid > 0 ? 'Edit Recipe' : 'Add Recipe';
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($heading); ?></h1>
</div>
<?php if (!empty($error)): ?><div class="mb-3 text-red-600"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post" action="<?php echo htmlspecialchars($action); ?>" class="space-y-4">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
    <div>
        <label class="block text-sm font-medium mb-1">Title</label>
        <input class="w-full" type="text" name="title" placeholder="e.g., Easy Chickpea Salad" value="<?php echo htmlspecialchars($input['title'] ?? ''); ?>" required>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Description
          <span class="text-xs text-text-muted font-normal">(optional)</span>
        </label>
        <textarea class="w-full" name="description" rows="4" placeholder="Short summary or notes..."><?php echo htmlspecialchars($input['description'] ?? ''); ?></textarea>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">API Source
              <span class="text-xs text-text-muted font-normal">(optional)</span>
            </label>
            <?php $src = $input['api_source'] ?? ''; ?>
            <select class="w-full" name="api_source">
                <option value="" <?php echo $src === '' ? 'selected' : ''; ?>>— none —</option>
                <?php foreach (['manual','fdc','off','fatsecret'] as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $src === $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucwords(str_replace('_',' ', $opt))); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">API ID
              <span class="text-xs text-text-muted font-normal">(optional)</span>
            </label>
            <input class="w-full" type="text" name="api_id" placeholder="Provider-specific ID" value="<?php echo htmlspecialchars($input['api_id'] ?? ''); ?>">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Owner User ID
              <span class="text-xs text-text-muted font-normal">(optional)</span>
            </label>
            <input class="w-full" type="number" name="user_id" placeholder="Leave empty for none" value="<?php echo htmlspecialchars((string)($input['user_id'] ?? '')); ?>">
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Image URL
              <span class="text-xs text-text-muted font-normal">(optional)</span>
            </label>
            <input class="w-full" type="url" name="image_url" placeholder="https://..." value="<?php echo htmlspecialchars($input['image_url'] ?? ''); ?>">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Source URL
              <span class="text-xs text-text-muted font-normal">(optional)</span>
            </label>
            <input class="w-full" type="url" name="source_url" placeholder="https://..." value="<?php echo htmlspecialchars($input['source_url'] ?? ''); ?>">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Ingredients
          <span class="text-xs text-text-muted font-normal">One per line or comma-separated. JSON also accepted.</span>
        </label>
        <textarea class="w-full font-mono text-xs" name="ingredients_list" rows="5" placeholder="1 cup flour, 2 eggs or one per line"><?php echo htmlspecialchars(is_array($input['ingredients_list'] ?? null) ? implode("\n", array_map('strval', $input['ingredients_list'])) : (string)($input['ingredients_list'] ?? '')); ?></textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Instructions
          <span class="text-xs text-text-muted font-normal">One step per line. JSON also accepted.</span>
        </label>
        <textarea class="w-full font-mono text-xs" name="instructions_list" rows="5" placeholder="Preheat oven to 350°F\nMix ingredients\nBake 20 min"><?php echo htmlspecialchars(is_array($input['instructions_list'] ?? null) ? implode("\n", array_map('strval', $input['instructions_list'])) : (string)($input['instructions_list'] ?? '')); ?></textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Nutrition per serving
          <span class="text-xs text-text-muted font-normal">One per line (Label: amount unit). Comma-separated also works. Example: Calories: 250 kcal</span>
        </label>
        <textarea class="w-full font-mono text-xs" name="nutrition_per_serving" rows="6" placeholder="Calories: 250 kcal\nProtein: 12 g\nCarbs: 20 g"><?php
            $nutVal = '';
            $nut = $input['nutrition_per_serving'] ?? null;
            if (is_array($nut)) {
                $lines = [];
                foreach ($nut as $label => $v) {
                    if (is_array($v)) {
                        $amt = isset($v['amount']) ? (float)$v['amount'] : null;
                        $unit = isset($v['unit']) ? (string)$v['unit'] : '';
                        if ($amt !== null) {
                            $line = (string)$label . ': ' . rtrim($amt . ' ' . trim($unit));
                            $lines[] = trim($line);
                        }
                    }
                }
                $nutVal = implode("\n", $lines);
            } else {
                $nutVal = (string)($input['nutrition_per_serving'] ?? '');
            }
            echo htmlspecialchars($nutVal);
        ?></textarea>
    </div>
    <div class="pt-2">
      <button type="submit" class="btn btn-cta"><?php echo $rid > 0 ? 'Update' : 'Save'; ?></button>
    </div>
</form>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
