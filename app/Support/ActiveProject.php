<?php

namespace App\Support;

/**
 * The "active server" used to persist across the whole panel (main nav workspace tabs,
 * every project-scoped page's own default) instead of only living in the current page's
 * own `?project=` query string — switching it once (via the nav dropdown, or any page's
 * own project picker) should stick everywhere until changed again.
 */
final class ActiveProject
{
    private const SESSION_KEY = 'dz.active_project_id';

    /**
     * @param  int[]  $availableIds  the ids the current user is actually allowed to see, already
     *                                fetched by the caller (avoids this class owning its own query)
     */
    public static function resolve(?int $requested, array $availableIds): ?int
    {
        if ($requested !== null && in_array($requested, $availableIds, true)) {
            self::set($requested);

            return $requested;
        }

        $stored = session(self::SESSION_KEY);
        if ($stored !== null && in_array($stored, $availableIds, true)) {
            return $stored;
        }

        return $availableIds[0] ?? null;
    }

    public static function set(?int $id): void
    {
        if ($id === null) {
            session()->forget(self::SESSION_KEY);

            return;
        }

        session([self::SESSION_KEY => $id]);
    }

    public static function id(): ?int
    {
        return session(self::SESSION_KEY);
    }
}
