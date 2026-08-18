<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<int, int>> */
    private array $checklistIdMap = [];

    public function up(): void
    {
        if (! Schema::hasTable('checklists')) {
            return;
        }

        $this->migrateStepDefinitions();
        $this->migrateProgressRecords();
        $this->rewireBookingForeignKeys();
        $this->dropLegacyTables();
    }

    public function down(): void
    {
        // Irreversible consolidation migration.
    }

    private function typeId(string $code): ?int
    {
        static $cache = [];

        if (! array_key_exists($code, $cache)) {
            $cache[$code] = DB::table('checklist_types')->where('code', $code)->value('id');
        }

        return $cache[$code];
    }

    private function migrateStepDefinitions(): void
    {
        if (Schema::hasTable('onboarding_steps')) {
            $typeId = $this->typeId('onboarding');

            if ($typeId) {
                foreach (DB::table('onboarding_steps')->orderBy('id')->get() as $step) {
                    $newId = $this->insertChecklist($typeId, [
                        'title' => $step->title,
                        'description' => $step->description,
                        'sort_order' => $step->sort_order,
                        'is_required' => $step->is_required,
                        'responsible_parties' => $step->responsible_parties ?? null,
                        'notified_parties' => $step->notified_parties ?? null,
                        'country' => $step->country ?? null,
                        'is_active' => $step->is_active ?? true,
                        'created_at' => $step->created_at,
                        'updated_at' => $step->updated_at,
                        'deleted_at' => $step->deleted_at ?? null,
                    ]);

                    $this->checklistIdMap['onboarding_steps'][(int) $step->id] = $newId;
                }
            }
        }

        if (Schema::hasTable('licensing_steps')) {
            $typeId = $this->typeId('licensing');

            if ($typeId) {
                foreach (DB::table('licensing_steps')->orderBy('id')->get() as $step) {
                    $newId = $this->insertChecklist($typeId, [
                        'title' => $step->title,
                        'description' => $step->description,
                        'sort_order' => $step->sort_order,
                        'is_required' => $step->is_required,
                        'responsible_parties' => $step->responsible_parties ?? null,
                        'notified_parties' => $step->notified_parties ?? null,
                        'is_active' => $step->is_active ?? true,
                        'created_at' => $step->created_at,
                        'updated_at' => $step->updated_at,
                        'deleted_at' => $step->deleted_at ?? null,
                    ]);

                    $this->checklistIdMap['licensing_steps'][(int) $step->id] = $newId;
                }
            }
        }

        if (Schema::hasTable('apprenticeship_steps')) {
            $typeId = $this->typeId('fap');

            if ($typeId) {
                foreach (DB::table('apprenticeship_steps')->orderBy('id')->get() as $step) {
                    $programName = null;

                    if (Schema::hasTable('apprenticeship_programs')) {
                        $programName = DB::table('apprenticeship_programs')
                            ->where('id', $step->apprenticeship_program_id)
                            ->value('name');
                    }

                    $newId = $this->insertChecklist($typeId, [
                        'title' => $step->title,
                        'description' => $step->description,
                        'sort_order' => $step->sort_order,
                        'is_required' => true,
                        'responsible_parties' => $step->responsible_parties ?? null,
                        'notified_parties' => $step->notified_parties ?? null,
                        'group_label' => $programName,
                        'is_active' => $step->is_active ?? true,
                        'created_at' => $step->created_at,
                        'updated_at' => $step->updated_at,
                        'deleted_at' => $step->deleted_at ?? null,
                    ]);

                    $this->checklistIdMap['apprenticeship_steps'][(int) $step->id] = $newId;
                }
            }
        }

        if (Schema::hasTable('cfm_training_modules')) {
            $typeId = $this->typeId('cfm-training');

            if ($typeId) {
                foreach (DB::table('cfm_training_modules')->orderBy('id')->get() as $step) {
                    $newId = $this->insertChecklist($typeId, [
                        'title' => $step->title,
                        'description' => $step->description,
                        'sort_order' => $step->sort_order,
                        'is_required' => $step->is_required,
                        'responsible_parties' => $step->responsible_parties ?? null,
                        'notified_parties' => $step->notified_parties ?? null,
                        'is_active' => $step->is_active ?? true,
                        'created_at' => $step->created_at,
                        'updated_at' => $step->updated_at,
                        'deleted_at' => $step->deleted_at ?? null,
                    ]);

                    $this->checklistIdMap['cfm_training_modules'][(int) $step->id] = $newId;
                }
            }
        }

        if (Schema::hasTable('cfm_trainee_checklist_items')) {
            $typeId = $this->typeId('cfm-mentoring');

            if ($typeId) {
                foreach (DB::table('cfm_trainee_checklist_items')->orderBy('id')->get() as $item) {
                    $newId = $this->insertChecklist($typeId, [
                        'title' => $item->title,
                        'sort_order' => $item->sort_order,
                        'is_required' => $item->is_required,
                        'phase_number' => $item->phase_number,
                        'phase_title' => $item->phase_title,
                        'phase_target' => $item->phase_target,
                        'section_title' => $item->section_title,
                        'slug' => $item->slug,
                        'is_active' => $item->is_active ?? true,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                        'deleted_at' => $item->deleted_at ?? null,
                    ]);

                    $this->checklistIdMap['cfm_trainee_checklist_items'][(int) $item->id] = $newId;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function insertChecklist(int $typeId, array $data): int
    {
        return (int) DB::table('checklists')->insertGetId(array_merge([
            'checklist_type_id' => $typeId,
            'title' => '',
            'description' => null,
            'sort_order' => 0,
            'is_required' => true,
            'responsible_parties' => null,
            'notified_parties' => null,
            'country' => null,
            'group_label' => null,
            'phase_number' => null,
            'phase_title' => null,
            'phase_target' => null,
            'section_title' => null,
            'slug' => null,
            'action_url' => null,
            'action_label' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $data));
    }

    private function migrateProgressRecords(): void
    {
        if (Schema::hasTable('user_onboarding_progress')) {
            foreach (DB::table('user_onboarding_progress')->orderBy('id')->get() as $row) {
                $checklistId = $this->checklistIdMap['onboarding_steps'][(int) $row->onboarding_step_id] ?? null;

                if (! $checklistId) {
                    continue;
                }

                $this->insertUserProgress($checklistId, $row);
            }
        }

        if (Schema::hasTable('user_licensing_progress')) {
            foreach (DB::table('user_licensing_progress')->orderBy('id')->get() as $row) {
                $checklistId = $this->checklistIdMap['licensing_steps'][(int) $row->licensing_step_id] ?? null;

                if (! $checklistId) {
                    continue;
                }

                $this->insertUserProgress($checklistId, $row);
            }
        }

        if (Schema::hasTable('user_apprenticeship_progress')) {
            foreach (DB::table('user_apprenticeship_progress')->orderBy('id')->get() as $row) {
                $checklistId = $this->checklistIdMap['apprenticeship_steps'][(int) $row->apprenticeship_step_id] ?? null;

                if (! $checklistId) {
                    continue;
                }

                DB::table('checklist_progress')->insert([
                    'checklist_id' => $checklistId,
                    'user_id' => $row->user_id,
                    'mentor_assignment_id' => null,
                    'status' => $row->status,
                    'submitted_at' => $row->submitted_at ?? null,
                    'completed_at' => $row->completed_at,
                    'approved_by' => $row->approved_by ?? null,
                    'approved_at' => $row->approved_at ?? null,
                    'notes' => $row->notes ?? null,
                    'reviewed_by' => $row->reviewed_by ?? null,
                    'reviewed_at' => $row->reviewed_at ?? null,
                    'review_comments' => $row->review_comments ?? null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        if (Schema::hasTable('cfm_training_progress')) {
            foreach (DB::table('cfm_training_progress')->orderBy('id')->get() as $row) {
                $checklistId = $this->checklistIdMap['cfm_training_modules'][(int) $row->cfm_training_module_id] ?? null;

                if (! $checklistId) {
                    continue;
                }

                $this->insertUserProgress($checklistId, $row);
            }
        }

        if (Schema::hasTable('cfm_trainee_checklist_progress')) {
            foreach (DB::table('cfm_trainee_checklist_progress')->orderBy('id')->get() as $row) {
                $checklistId = $this->checklistIdMap['cfm_trainee_checklist_items'][(int) $row->cfm_trainee_checklist_item_id] ?? null;

                if (! $checklistId) {
                    continue;
                }

                DB::table('checklist_progress')->insert([
                    'checklist_id' => $checklistId,
                    'user_id' => null,
                    'mentor_assignment_id' => $row->mentor_assignment_id,
                    'status' => $row->status,
                    'completed_at' => $row->completed_at,
                    'completed_by' => $row->completed_by ?? null,
                    'notes' => $row->notes ?? null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }
    }

    private function insertUserProgress(int $checklistId, object $row): void
    {
        DB::table('checklist_progress')->insert([
            'checklist_id' => $checklistId,
            'user_id' => $row->user_id,
            'mentor_assignment_id' => null,
            'status' => $row->status,
            'submitted_at' => $row->submitted_at ?? null,
            'completed_at' => $row->completed_at,
            'notes' => $row->notes ?? null,
            'reviewed_by' => $row->reviewed_by ?? null,
            'reviewed_at' => $row->reviewed_at ?? null,
            'review_comments' => $row->review_comments ?? null,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ]);
    }

    private function rewireBookingForeignKeys(): void
    {
        if (! Schema::hasTable('booking_event_types') || ! Schema::hasColumn('booking_event_types', 'linked_apprenticeship_step_id')) {
            return;
        }

        Schema::table('booking_event_types', function (Blueprint $table): void {
            $table->dropForeign(['linked_apprenticeship_step_id']);
        });

        Schema::table('booking_event_types', function (Blueprint $table): void {
            $table->unsignedBigInteger('linked_checklist_id')->nullable()->after('calendar_category_id');
        });

        foreach (DB::table('booking_event_types')->whereNotNull('linked_apprenticeship_step_id')->get() as $row) {
            $checklistId = $this->checklistIdMap['apprenticeship_steps'][(int) $row->linked_apprenticeship_step_id] ?? null;

            if ($checklistId) {
                DB::table('booking_event_types')->where('id', $row->id)->update([
                    'linked_checklist_id' => $checklistId,
                ]);
            }
        }

        Schema::table('booking_event_types', function (Blueprint $table): void {
            $table->dropColumn('linked_apprenticeship_step_id');
            $table->foreign('linked_checklist_id')->references('id')->on('checklists')->nullOnDelete();
        });

        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'related_apprenticeship_step_id')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropForeign(['related_apprenticeship_step_id']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedBigInteger('related_checklist_id')->nullable()->after('booking_event_type_id');
        });

        foreach (DB::table('bookings')->whereNotNull('related_apprenticeship_step_id')->get() as $row) {
            $checklistId = $this->checklistIdMap['apprenticeship_steps'][(int) $row->related_apprenticeship_step_id] ?? null;

            if ($checklistId) {
                DB::table('bookings')->where('id', $row->id)->update([
                    'related_checklist_id' => $checklistId,
                ]);
            }
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('related_apprenticeship_step_id');
            $table->foreign('related_checklist_id')->references('id')->on('checklists')->nullOnDelete();
        });
    }

    private function dropLegacyTables(): void
    {
        Schema::dropIfExists('cfm_trainee_checklist_progress');
        Schema::dropIfExists('cfm_trainee_checklist_items');
        Schema::dropIfExists('cfm_training_progress');
        Schema::dropIfExists('cfm_training_modules');
        Schema::dropIfExists('user_apprenticeship_progress');
        Schema::dropIfExists('apprenticeship_steps');
        Schema::dropIfExists('apprenticeship_programs');
        Schema::dropIfExists('user_licensing_progress');
        Schema::dropIfExists('licensing_steps');
        Schema::dropIfExists('user_onboarding_progress');
        Schema::dropIfExists('onboarding_steps');
    }
};
