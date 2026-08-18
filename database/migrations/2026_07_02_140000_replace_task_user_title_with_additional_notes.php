<?php

use App\Models\Task;
use App\Models\TaskCategory;
use App\Support\TaskLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_users')) {
            return;
        }

        if (! Schema::hasColumn('task_users', 'additional_notes')) {
            Schema::table('task_users', function (Blueprint $table): void {
                $table->text('additional_notes')->nullable()->after('task_id');
            });
        }

        if (Schema::hasColumn('task_users', 'title')) {
            DB::table('task_users')
                ->orderBy('id')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        $categoryName = TaskCategory::query()->where('id', $row->task_category_id)->value('name') ?? 'Admin';

                        $task = TaskLibrary::findOrCreate(
                            (string) $row->title,
                            $categoryName,
                            $row->description,
                            $row->priority,
                        );

                        DB::table('task_users')->where('id', $row->id)->update([
                            'task_id' => $task->id,
                        ]);
                    }
                });

            Schema::table('task_users', function (Blueprint $table): void {
                $table->dropColumn(['title', 'description']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_users') || Schema::hasColumn('task_users', 'title')) {
            return;
        }

        Schema::table('task_users', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('task_id');
            $table->text('description')->nullable()->after('title');
        });

        DB::table('task_users')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $task = $row->task_id ? Task::query()->find($row->task_id) : null;

                    DB::table('task_users')->where('id', $row->id)->update([
                        'title' => $task?->title,
                        'description' => $row->additional_notes ?? $task?->description,
                    ]);
                }
            });

        Schema::table('task_users', function (Blueprint $table): void {
            $table->dropColumn('additional_notes');
        });
    }
};
