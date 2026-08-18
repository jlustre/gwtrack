<?php

namespace App\Support;

class ChecklistProgressDisplay
{
    public static function label(array|int $entry): string
    {
        if (is_array($entry)) {
            return ($entry['started'] ?? false)
                ? ($entry['percent'] ?? 0).'%'
                : 'Not started';
        }

        return (int) $entry.'%';
    }

    public static function percent(array|int $entry): int
    {
        if (is_array($entry)) {
            return ($entry['started'] ?? false) ? (int) ($entry['percent'] ?? 0) : 0;
        }

        return (int) $entry;
    }
}
