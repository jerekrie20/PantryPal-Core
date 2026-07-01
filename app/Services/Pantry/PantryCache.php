<?php

namespace Services\Pantry;

use Helpers\Cache;

/**
 * Single source for the pantry/dashboard cache keys.
 *
 * These key strings were previously hardcoded at 14 call sites across five
 * controllers; a typo in any one of them meant silent stale data.
 */
class PantryCache
{
    public static function recentItemsKey(int $userId): string
    {
        return 'pp:user:' . $userId . ':items:recent:v1';
    }

    public static function dashboardStatsKey(int $userId): string
    {
        return 'pp:user:' . $userId . ':dashboard:stats:v1';
    }

    /** Invalidate everything the dashboard derives from the user's items. */
    public static function bustForUser(int $userId): void
    {
        try {
            Cache::del(self::recentItemsKey($userId));
            Cache::del(self::dashboardStatsKey($userId));
        } catch (\Throwable $e) {
            // Cache being down must never break a write path.
        }
    }
}
