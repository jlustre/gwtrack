<?php

namespace Tests\Concerns;

use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait StartsChecklistTypes
{
    protected function startChecklistType(
        User $user,
        string $typeCode,
        ?User $starter = null,
        ?string $startedAt = null,
    ): void {
        $starter ??= $user;

        DB::table('user_checklist_type_starts')->insert([
            'user_id' => $user->id,
            'checklist_type_id' => DB::table('checklist_types')->where('code', $typeCode)->value('id'),
            'started_at' => $startedAt ?? now()->toDateString(),
            'started_by' => $starter->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function completeChecklistType(User $user, string $typeCode): void
    {
        $typeId = DB::table('checklist_types')->where('code', $typeCode)->value('id');

        Checklist::query()
            ->where('checklist_type_id', $typeId)
            ->where('is_required', true)
            ->pluck('id')
            ->each(function (int $checklistId) use ($user): void {
                ChecklistProgress::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'checklist_id' => $checklistId,
                        'mentor_assignment_id' => null,
                    ],
                    [
                        'status' => 'completed',
                        'completed_at' => now(),
                    ],
                );
            });
    }

    protected function startMemberChecklistTypes(User $user, ?User $starter = null): void
    {
        foreach (['onboarding', 'licensing', 'fap', 'cfm-training'] as $typeCode) {
            $this->startChecklistType($user, $typeCode, $starter);
        }
    }
}
