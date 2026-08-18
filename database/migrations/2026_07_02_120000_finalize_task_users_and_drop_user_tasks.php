<?php

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
        if (! Schema::hasTable('task_user_checklist_items')) {
            Schema::create('task_user_checklist_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('task_user_id')->constrained('task_users')->cascadeOnDelete();
                $table->string('text');
                $table->boolean('is_done')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('task_user_comments')) {
            Schema::create('task_user_comments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('task_user_id')->constrained('task_users')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_tasks')) {
            return;
        }

        $categoryIdsByName = TaskCategory::query()->pluck('id', 'name');
        $taskUserMap = [];

        foreach (DB::table('user_tasks')->orderBy('id')->get() as $userTask) {
            $categoryName = (string) ($userTask->category ?? 'Admin');
            $libraryTask = TaskLibrary::findOrCreate(
                (string) $userTask->title,
                $categoryName,
                $userTask->description,
                $userTask->priority,
            );

            $existingTaskUserId = DB::table('task_users')
                ->where('assignee_id', $userTask->assigned_to_user_id)
                ->where('task_id', $libraryTask->id)
                ->where('created_at', $userTask->created_at)
                ->value('id');

            if ($existingTaskUserId) {
                $taskUserMap[$userTask->id] = (int) $existingTaskUserId;

                continue;
            }

            $categoryId = $categoryIdsByName[$userTask->category] ?? TaskCategory::query()->where('slug', 'admin')->value('id');

            if (! $categoryId) {
                continue;
            }

            $taskUserMap[$userTask->id] = (int) DB::table('task_users')->insertGetId([
                'assignee_id' => $userTask->assigned_to_user_id,
                'task_id' => $libraryTask->id,
                'task_category_id' => $categoryId,
                'assignor_id' => $userTask->created_by_user_id,
                'priority' => $userTask->priority,
                'status' => $userTask->status,
                'related_module' => $userTask->related_module,
                'related_person' => $userTask->related_person,
                'related_prospect_id' => $userTask->related_prospect_id ?? null,
                'related_fna_id' => $userTask->related_fna_id ?? null,
                'due_date' => $userTask->due_date,
                'progress' => $userTask->progress,
                'reminder' => $userTask->reminder,
                'completed_at' => $userTask->completed_at,
                'created_at' => $userTask->created_at,
                'updated_at' => $userTask->updated_at,
                'deleted_at' => null,
            ]);
        }

        if (Schema::hasTable('user_task_checklist_items') && DB::table('task_user_checklist_items')->count() === 0) {
            foreach (DB::table('user_task_checklist_items')->orderBy('id')->get() as $item) {
                $taskUserId = $taskUserMap[$item->user_task_id] ?? null;

                if (! $taskUserId) {
                    continue;
                }

                DB::table('task_user_checklist_items')->insert([
                    'task_user_id' => $taskUserId,
                    'text' => $item->text,
                    'is_done' => $item->is_done,
                    'sort_order' => $item->sort_order,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
            }
        }

        if (Schema::hasTable('user_task_comments') && DB::table('task_user_comments')->count() === 0) {
            foreach (DB::table('user_task_comments')->orderBy('id')->get() as $comment) {
                $taskUserId = $taskUserMap[$comment->user_task_id] ?? null;

                if (! $taskUserId) {
                    continue;
                }

                DB::table('task_user_comments')->insert([
                    'task_user_id' => $taskUserId,
                    'user_id' => $comment->user_id,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ]);
            }
        }

        if (Schema::hasTable('message_tasks') && Schema::hasColumn('message_tasks', 'user_task_id')) {
            if (! Schema::hasColumn('message_tasks', 'task_user_id')) {
                Schema::table('message_tasks', function (Blueprint $table): void {
                    if (Schema::getConnection()->getDriverName() === 'sqlite') {
                        $table->unsignedBigInteger('task_user_id')->nullable();
                    } else {
                        $table->unsignedBigInteger('task_user_id')->nullable()->after('message_id');
                    }
                });
            }

            foreach (DB::table('message_tasks')->get() as $messageTask) {
                $taskUserId = $taskUserMap[$messageTask->user_task_id] ?? null;

                if ($taskUserId) {
                    DB::table('message_tasks')->where('id', $messageTask->id)->update([
                        'task_user_id' => $taskUserId,
                    ]);
                }
            }

            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                Schema::disableForeignKeyConstraints();
                Schema::drop('message_tasks');
                Schema::create('message_tasks', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('message_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('task_user_id')->nullable()->constrained('task_users')->cascadeOnDelete();
                    $table->timestamps();
                    $table->unique(['message_id', 'task_user_id'], 'message_task_uq');
                });
                Schema::enableForeignKeyConstraints();
            } else {
                $foreignKeys = DB::select(
                    "SELECT CONSTRAINT_NAME
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'message_tasks'
                    AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
                );

                foreach ($foreignKeys as $foreignKey) {
                    DB::statement('ALTER TABLE message_tasks DROP FOREIGN KEY `'.$foreignKey->CONSTRAINT_NAME.'`');
                }

                Schema::table('message_tasks', function (Blueprint $table): void {
                    $table->dropUnique('message_task_uq');
                    $table->dropColumn('user_task_id');
                });

                Schema::table('message_tasks', function (Blueprint $table): void {
                    $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
                    $table->foreign('task_user_id')->references('id')->on('task_users')->cascadeOnDelete();
                    $table->unique(['message_id', 'task_user_id'], 'message_task_uq');
                });
            }
        }

        Schema::dropIfExists('user_task_comments');
        Schema::dropIfExists('user_task_checklist_items');
        Schema::dropIfExists('user_tasks');
    }

    public function down(): void
    {
        // Irreversible consolidation migration.
    }
};
