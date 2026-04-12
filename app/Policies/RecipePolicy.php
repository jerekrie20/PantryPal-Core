<?php

namespace Policies;

/**
 * Authorization rules for recipe actions.
 * All methods read directly from $_SESSION — call only within authenticated requests.
 *
 * Rules:
 *   - Admins (is_admin = 1) have no restrictions.
 *   - Users may edit/delete only their own recipes.
 *   - Only 'manual' source recipes can be edited via the user-facing UI
 *     (API-sourced recipes are read-only; admins use the admin panel for those).
 */
class RecipePolicy
{
    /**
     * Can the current user edit this recipe?
     */
    public static function canEdit(array $recipe): bool
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        // Only user-created recipes are editable through the UI
        if (($recipe['api_source'] ?? null) !== 'manual') {
            return false;
        }
        if (!empty($_SESSION['is_admin'])) {
            return true;
        }
        return (int)($recipe['user_id'] ?? 0) === (int)$_SESSION['user_id'];
    }

    /**
     * Can the current user delete this recipe?
     * Admins can delete any recipe; regular users only their own.
     */
    public static function canDelete(array $recipe): bool
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        if (!empty($_SESSION['is_admin'])) {
            return true;
        }
        return (int)($recipe['user_id'] ?? 0) === (int)$_SESSION['user_id'];
    }
}
