<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$csrf = $_SESSION['csrf_token'] ?? '';
$input = $input ?? $_POST ?? [];
ob_start();
require VIEW_PATH . '/Admin/partials/nav.php';
?>
<?php $isEdit = isset($updateId) && $updateId; $formAction = $isEdit ? ('/admin/updates/' . (int)$updateId) : '/admin/updates'; ?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold"><?php echo $isEdit ? 'Edit Update' : 'Post Update'; ?></h1>
</div>
<?php if (!empty($error)): ?><div class="mb-3 text-red-600"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post" action="<?php echo htmlspecialchars($formAction); ?>" class="space-y-4">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
    <div>
        <label class="block text-sm font-medium mb-1">Title</label>
        <input class="input w-full" type="text" name="title" placeholder="e.g., New features released" value="<?php echo htmlspecialchars($input['title'] ?? ''); ?>" required>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Message</label>
        <textarea class="input w-full" name="message" rows="6" placeholder="Write your announcement..." required><?php echo htmlspecialchars($input['message'] ?? ''); ?></textarea>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Target User ID
              <span class="text-xs text-text-muted font-normal">(optional)</span>
            </label>
            <input class="input w-full" type="number" name="target_user_id" value="<?php echo htmlspecialchars($input['target_user_id'] ?? ''); ?>" placeholder="Leave empty for all users">
        </div>
        <div class="flex items-center">
            <label class="inline-flex items-center space-x-2">
                <input type="checkbox" name="is_active" value="1" <?php echo !isset($input['is_active']) || $input['is_active'] ? 'checked' : ''; ?>>
                <span>Active</span>
            </label>
        </div>
    </div>
    <div class="pt-2">
      <button type="submit" class="btn btn-cta">Publish</button>
    </div>
</form>
<?php
$content = ob_get_clean();
require VIEW_PATH . '/Layouts/Users/layout.php';
