<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\ChecklistType;
use App\Models\User;
use App\Models\UserChecklistTypeStart;
use App\Services\ChecklistService;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\StartsChecklistTypes;
use Tests\TestCase;

class ChecklistNthDayTest extends TestCase
{
    use RefreshDatabase;
    use StartsChecklistTypes;

    public function test_seeder_backfills_nth_day_for_checklist_items(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $onboardingTypeId = ChecklistType::query()->where('code', 'onboarding')->value('id');

        $this->assertGreaterThan(
            0,
            Checklist::query()
                ->where('checklist_type_id', $onboardingTypeId)
                ->whereNotNull('nth_day')
                ->count(),
        );

        $firstItem = Checklist::query()
            ->where('checklist_type_id', $onboardingTypeId)
            ->orderBy('sort_order')
            ->first();

        $this->assertSame(1, $firstItem->nth_day);
    }

    public function test_expected_due_date_is_based_on_explicit_checklist_type_start_date(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $user = User::factory()->create([
            'joined_at' => '2026-01-10 09:00:00',
        ]);

        $this->startChecklistType($user, 'onboarding', startedAt: '2026-03-01');

        $service = app(ChecklistService::class);
        $startDate = $service->typeStartDate($user, 'onboarding');

        $this->assertSame('2026-03-01', $startDate->toDateString());
        $this->assertDatabaseHas('user_checklist_type_starts', [
            'user_id' => $user->id,
            'checklist_type_id' => ChecklistType::query()->where('code', 'onboarding')->value('id'),
        ]);

        $dueDate = $service->expectedDueDate(7, $startDate);

        $this->assertSame('2026-03-07', $dueDate?->toDateString());
    }

    public function test_type_start_date_is_not_inferred_from_joined_at(): void
    {
        $this->seed(ChecklistTypeSeeder::class);

        $user = User::factory()->create([
            'joined_at' => '2026-06-01 09:00:00',
        ]);

        $service = app(ChecklistService::class);

        $this->assertFalse($service->hasTypeStarted($user, 'onboarding'));
        $this->assertNull($service->typeStartDate($user, 'onboarding'));
    }

    public function test_existing_type_start_date_is_preserved(): void
    {
        $this->seed(ChecklistTypeSeeder::class);

        $user = User::factory()->create([
            'joined_at' => '2026-06-01 09:00:00',
        ]);

        $typeId = ChecklistType::query()->where('code', 'licensing')->value('id');

        UserChecklistTypeStart::query()->create([
            'user_id' => $user->id,
            'checklist_type_id' => $typeId,
            'started_at' => '2026-03-15',
            'started_by' => $user->id,
        ]);

        $service = app(ChecklistService::class);
        $startDate = $service->typeStartDate($user, 'licensing');

        $this->assertSame('2026-03-15', $startDate->toDateString());
    }

    public function test_max_complete_days_sets_type_completion_deadline(): void
    {
        $this->seed(ChecklistTypeSeeder::class);

        ChecklistType::query()
            ->where('code', 'onboarding')
            ->update(['max_complete_days' => 30]);

        $user = User::factory()->create([
            'joined_at' => '2026-01-01 09:00:00',
        ]);

        $this->startChecklistType($user, 'onboarding', startedAt: '2026-01-01');

        $service = app(ChecklistService::class);
        $startDate = $service->typeStartDate($user, 'onboarding');
        $completionDue = $service->typeCompletionDueDate($startDate, 'onboarding');

        $this->assertSame(30, $service->maxCompleteDaysForType('onboarding'));
        $this->assertSame('2026-01-30', $completionDue?->toDateString());
    }
}
