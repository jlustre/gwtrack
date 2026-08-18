<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('checklist_types', 'prerequisite_checklist_type_id')) {
            return;
        }

        Schema::table('checklist_types', function (Blueprint $table): void {
            $table->foreignId('prerequisite_checklist_type_id')
                ->nullable()
                ->after('max_complete_days')
                ->constrained('checklist_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('checklist_types', 'prerequisite_checklist_type_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('checklist_types', function (Blueprint $table): void {
                $table->dropForeign(['prerequisite_checklist_type_id']);
            });
        } else {
            foreach (Schema::getForeignKeys('checklist_types') as $foreignKey) {
                if (in_array('prerequisite_checklist_type_id', $foreignKey['columns'], true)) {
                    Schema::table('checklist_types', function (Blueprint $table) use ($foreignKey): void {
                        $table->dropForeign($foreignKey['name']);
                    });

                    break;
                }
            }
        }

        Schema::table('checklist_types', function (Blueprint $table): void {
            $table->dropColumn('prerequisite_checklist_type_id');
        });
    }
};
