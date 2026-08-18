<?php

namespace App\Support;

use Carbon\CarbonInterface;

class ChecklistDueDisplay
{
    public static function isOverdue(?CarbonInterface $dueAt, string $status = 'not_started'): bool
    {
        if (! $dueAt || $status === 'completed') {
            return false;
        }

        return $dueAt->copy()->startOfDay()->lt(now()->startOfDay());
    }

    public static function textClass(bool $isOverdue, bool $hasDueDate = true): string
    {
        if (! $hasDueDate) {
            return 'text-slate-500';
        }

        return $isOverdue ? 'font-semibold text-red-600' : 'text-slate-700';
    }

    public static function badgeClass(bool $isOverdue): string
    {
        return $isOverdue
            ? 'rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold tabular-nums text-red-700'
            : 'rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-600';
    }
}
