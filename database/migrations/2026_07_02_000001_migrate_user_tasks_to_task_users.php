<?php

use App\Models\TaskCategory;
use App\Support\TaskLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_tasks') || ! Schema::hasTable('task_users')) {
            return;
        }

        if (DB::table('task_users')->exists()) {
            return;
        }

        $categoryIdsByName = TaskCategory::query()->pluck('id', 'name');

        DB::table('user_tasks')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($categoryIdsByName): void {
                $inserts = [];

                foreach ($rows as $row) {
                    $categoryId = $categoryIdsByName[$row->category] ?? TaskCategory::query()->where('slug', 'admin')->value('id');

                    if (! $categoryId) {
                        continue;
                    }

                    $categoryName = (string) ($row->category ?? 'Admin');

                    $libraryTask = TaskLibrary::findOrCreate(
                        (string) $row->title,
                        $categoryName,
                        $row->description,
                        $row->priority,
                    );

                    $inserts[] = [
                        'assignee_id' => $row->assigned_to_user_id,
                        'task_id' => $libraryTask->id,
                        'task_category_id' => $categoryId,
                        'assignor_id' => $row->created_by_user_id,
                        'priority' => $row->priority,
                        'status' => $row->status,
                        'related_module' => $row->related_module,
                        'related_person' => $row->related_person,
                        'related_prospect_id' => $row->related_prospect_id ?? null,
                        'related_fna_id' => $row->related_fna_id ?? null,
                        'due_date' => $row->due_date,
                        'progress' => $row->progress,
                        'reminder' => $row->reminder,
                        'completed_at' => $row->completed_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($inserts !== []) {
                    DB::table('task_users')->insert($inserts);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('task_users')) {
            DB::table('task_users')->truncate();
        }
    }
};
