<?php

namespace Services\Pantry;

/**
 * Collapses a category value — which may be a plain string, JSON-encoded string,
 * or nested array (e.g. Open Food Facts categoryPath) — into a display string.
 */
class CategoryFormatter
{
    public static function stringify(mixed $cat): ?string
    {
        if ($cat === null || $cat === '') return null;

        if (is_string($cat)) {
            $trim = ltrim($cat);
            if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                $decoded = json_decode($cat, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return self::stringify($decoded);
                }
            }
            return $cat;
        }

        if (is_array($cat)) {
            if (isset($cat['categoryPath']) && is_array($cat['categoryPath'])) {
                return implode(' › ', array_filter($cat['categoryPath'], 'is_string'));
            }
            // FDC Foundation/SR Legacy objects: {id, code, description}
            if (isset($cat['description']) && is_string($cat['description']) && trim($cat['description']) !== '') {
                return trim($cat['description']);
            }
            $vals = [];
            foreach ($cat as $v) {
                if (is_string($v)) $vals[] = $v;
            }
            return $vals ? implode(' › ', $vals) : null;
        }

        return null;
    }
}
