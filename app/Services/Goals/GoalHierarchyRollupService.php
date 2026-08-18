<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\User;

class GoalHierarchyRollupService
{
    /**
     * Roll child goal progress into parent goals, bottom-up (daily → vision).
     */
    public function rollupForUser(User $user): void
    {
        $levels = array_reverse(array_keys(config('goals.hierarchy_levels', [])));

        foreach ($levels as $level) {
            Goal::query()
                ->where('user_id', $user->id)
                ->where('hierarchy_level', $level)
                ->whereHas('children')
                ->with('children')
                ->each(fn (Goal $parent) => $this->rollupParent($parent));
        }
    }

    private function rollupParent(Goal $parent): void
    {
        $children = $parent->children()
            ->whereIn('status', ['active', 'completed', 'off_track'])
            ->get();

        if ($children->isEmpty()) {
            return;
        }

        $actual = match ($parent->measurement_type) {
            'percentage', 'completion' => $children->avg(fn (Goal $child) => $child->progressPercent()),
            default => $children->sum(fn (Goal $child) => (float) $child->actual_value),
        };

        $status = $parent->status;

        if ($parent->target_value > 0 && $actual >= (float) $parent->target_value) {
            $status = 'completed';
        } elseif ($parent->status === 'active') {
            $parent->actual_value = $actual;
            $status = $parent->isOffTrack() ? 'off_track' : 'active';
        }

        $parent->forceFill([
            'actual_value' => $actual,
            'status' => $status,
            'completed_at' => $status === 'completed' ? ($parent->completed_at ?? now()) : null,
        ])->save();
    }
}
