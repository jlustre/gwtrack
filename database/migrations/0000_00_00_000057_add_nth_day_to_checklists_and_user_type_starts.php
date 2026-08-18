<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklists', function (Blueprint $table): void {
            $table->unsignedSmallInteger('nth_day')->nullable()->after('sort_order');
        });

        Schema::create('user_checklist_type_starts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_type_id')->constrained()->cascadeOnDelete();
            $table->date('started_at');
            $table->timestamps();

            $table->unique(['user_id', 'checklist_type_id'], 'user_checklist_type_starts_uq');
        });

        $this->backfillNthDays();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_checklist_type_starts');

        Schema::table('checklists', function (Blueprint $table): void {
            $table->dropColumn('nth_day');
        });
    }

    private function backfillNthDays(): void
    {
        if (! Schema::hasTable('checklists') || ! Schema::hasColumn('checklists', 'nth_day')) {
            return;
        }

        $typeIds = DB::table('checklists')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('checklist_type_id');

        foreach ($typeIds as $typeId) {
            $items = DB::table('checklists')
                ->where('checklist_type_id', $typeId)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id');

            foreach ($items as $index => $id) {
                DB::table('checklists')
                    ->where('id', $id)
                    ->whereNull('nth_day')
                    ->update(['nth_day' => $index + 1]);
            }
        }
    }
};
