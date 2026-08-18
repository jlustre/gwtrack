<?php

use App\Models\ChecklistType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $cfmTraining = ChecklistType::query()->where('code', 'cfm-training')->first();

        if (! $cfmTraining) {
            return;
        }

        $prerequisiteIds = ChecklistType::query()
            ->whereIn('code', ['onboarding', 'licensing', 'fap'])
            ->pluck('id')
            ->all();

        $cfmTraining->prerequisites()->sync($prerequisiteIds);
    }

    public function down(): void
    {
        $cfmTraining = ChecklistType::query()->where('code', 'cfm-training')->first();

        if (! $cfmTraining) {
            return;
        }

        $prerequisiteIds = ChecklistType::query()
            ->whereIn('code', ['licensing', 'fap'])
            ->pluck('id')
            ->all();

        $cfmTraining->prerequisites()->sync($prerequisiteIds);
    }
};
