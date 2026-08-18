<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_users') || ! Schema::hasColumn('task_users', 'user_id')) {
            return;
        }

        if (! Schema::hasColumn('task_users', 'assignee_id')) {
            Schema::table('task_users', function (Blueprint $table): void {
                $table->foreignId('assignee_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assignor_id')->nullable()->after('assignee_id')->constrained('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('task_users', 'user_id')) {
            DB::table('task_users')
                ->orderBy('id')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('task_users')->where('id', $row->id)->update([
                            'assignee_id' => $row->user_id,
                            'assignor_id' => $row->created_by_user_id,
                        ]);
                    }
                });
        }

        if (Schema::hasColumn('task_users', 'user_id')) {
            Schema::disableForeignKeyConstraints();

            Schema::table('task_users', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['created_by_user_id']);
                $table->dropColumn(['user_id', 'created_by_user_id']);
            });

            Schema::enableForeignKeyConstraints();
        }

        if (Schema::hasColumn('task_users', 'assignee_id')) {
            Schema::table('task_users', function (Blueprint $table): void {
                $table->index(['assignee_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_users') || ! Schema::hasColumn('task_users', 'assignee_id')) {
            return;
        }

        Schema::table('task_users', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->after('task_category_id')->constrained('users')->cascadeOnDelete();
        });

        DB::table('task_users')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('task_users')->where('id', $row->id)->update([
                        'user_id' => $row->assignee_id,
                        'created_by_user_id' => $row->assignor_id,
                    ]);
                }
            });

        Schema::disableForeignKeyConstraints();

        Schema::table('task_users', function (Blueprint $table): void {
            $table->dropForeign(['assignee_id']);
            $table->dropForeign(['assignor_id']);
            $table->dropIndex(['assignee_id', 'status']);
            $table->dropColumn(['assignee_id', 'assignor_id']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
