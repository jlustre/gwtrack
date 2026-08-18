<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_types', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_complete_days')->nullable()->after('sort_order');
        });

        $this->backfillFromChecklistNthDays();
    }

    public function down(): void
    {
        Schema::table('checklist_types', function (Blueprint $table): void {
            $table->dropColumn('max_complete_days');
        });
    }

    private function backfillFromChecklistNthDays(): void
    {
        if (! Schema::hasTable('checklists') || ! Schema::hasColumn('checklists', 'nth_day')) {
            return;
        }

        $maxByType = DB::table('checklists')
            ->whereNull('deleted_at')
            ->whereNotNull('nth_day')
            ->groupBy('checklist_type_id')
            ->selectRaw('checklist_type_id, MAX(nth_day) as max_nth_day')
            ->pluck('max_nth_day', 'checklist_type_id');

        foreach ($maxByType as $typeId => $maxNthDay) {
            DB::table('checklist_types')
                ->where('id', $typeId)
                ->whereNull('max_complete_days')
                ->update(['max_complete_days' => $maxNthDay]);
        }
    }
};
