<?php

namespace App\Services\Prospects;

use App\Models\ScheduleActivityOption;
use App\Models\User;
use App\Services\Prospects\ScheduleActivityOptionService;
use Illuminate\Support\Collection;

class ScheduleActivityOptionService
{
    /**
     * @return array<string, string>
     */
    public function optionsFor(User $user, string $category): array
    {
        $defaults = (array) config("prospect-scheduled-activity-form.default_{$category}_options", []);

        $stored = ScheduleActivityOption::query()
            ->where('category', $category)
            ->when(
                filled($user->team_id),
                fn ($query) => $query->where('team_id', $user->team_id),
                fn ($query) => $query->whereNull('team_id')->where('created_by', $user->id),
            )
            ->orderBy('sort_order')
            ->orderBy('value')
            ->pluck('value')
            ->all();

        return $this->mergeUniqueOptions($defaults, $stored);
    }

    public function remember(User $user, string $category, string $value): void
    {
        $value = trim($value);

        if ($value === '') {
            return;
        }

        ScheduleActivityOption::query()->firstOrCreate(
            [
                'category' => $category,
                'value' => $value,
                'team_id' => $user->team_id,
            ],
            [
                'created_by' => $user->id,
            ],
        );
    }

    /**
     * @param  list<string>  $sets
     * @return array<string, string>
     */
    private function mergeUniqueOptions(array ...$sets): array
    {
        return collect($sets)
            ->flatten()
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique(fn (string $value): string => mb_strtolower($value))
            ->values()
            ->mapWithKeys(fn (string $value): array => [$value => $value])
            ->all();
    }
}
