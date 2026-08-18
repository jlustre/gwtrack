<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('checklist_type_prerequisites')) {
            Schema::create('checklist_type_prerequisites', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('checklist_type_id');
                $table->unsignedBigInteger('prerequisite_checklist_type_id');
                $table->timestamps();

                $table->unique(
                    ['checklist_type_id', 'prerequisite_checklist_type_id'],
                    'checklist_type_prerequisites_uq',
                );

                $table->foreign('checklist_type_id', 'chk_type_prereq_type_fk')
                    ->references('id')
                    ->on('checklist_types')
                    ->cascadeOnDelete();

                $table->foreign('prerequisite_checklist_type_id', 'chk_type_prereq_prereq_fk')
                    ->references('id')
                    ->on('checklist_types')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('checklist_types', 'prerequisite_checklist_type_id')) {
            $legacy = DB::table('checklist_types')
                ->whereNotNull('prerequisite_checklist_type_id')
                ->get(['id', 'prerequisite_checklist_type_id']);

            foreach ($legacy as $row) {
                DB::table('checklist_type_prerequisites')->updateOrInsert(
                    [
                        'checklist_type_id' => $row->id,
                        'prerequisite_checklist_type_id' => $row->prerequisite_checklist_type_id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }

            $this->dropPrerequisiteForeignKeyIfExists();

            Schema::table('checklist_types', function (Blueprint $table): void {
                $table->dropColumn('prerequisite_checklist_type_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('checklist_type_prerequisites')) {
            return;
        }

        if (! Schema::hasColumn('checklist_types', 'prerequisite_checklist_type_id')) {
            Schema::table('checklist_types', function (Blueprint $table): void {
                $table->foreignId('prerequisite_checklist_type_id')
                    ->nullable()
                    ->after('max_complete_days')
                    ->constrained('checklist_types')
                    ->nullOnDelete();
            });
        }

        $rows = DB::table('checklist_type_prerequisites')
            ->orderBy('id')
            ->get(['checklist_type_id', 'prerequisite_checklist_type_id']);

        foreach ($rows->groupBy('checklist_type_id') as $typeId => $prerequisites) {
            DB::table('checklist_types')
                ->where('id', $typeId)
                ->update(['prerequisite_checklist_type_id' => $prerequisites->first()->prerequisite_checklist_type_id]);
        }

        Schema::dropIfExists('checklist_type_prerequisites');
    }

    private function dropPrerequisiteForeignKeyIfExists(): void
    {
        if (! Schema::hasTable('checklist_types') || ! Schema::hasColumn('checklist_types', 'prerequisite_checklist_type_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('checklist_types', function (Blueprint $table): void {
                $table->dropForeign(['prerequisite_checklist_type_id']);
            });

            return;
        }

        foreach (Schema::getForeignKeys('checklist_types') as $foreignKey) {
            if (in_array('prerequisite_checklist_type_id', $foreignKey['columns'], true)) {
                Schema::table('checklist_types', function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });

                return;
            }
        }
    }
};
