<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_users') || Schema::hasColumn('task_users', 'delegation_tracker_archived_at')) {
            return;
        }

        Schema::table('task_users', function (Blueprint $table): void {
            $table->timestamp('delegation_tracker_archived_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_users') || ! Schema::hasColumn('task_users', 'delegation_tracker_archived_at')) {
            return;
        }

        Schema::table('task_users', function (Blueprint $table): void {
            $table->dropColumn('delegation_tracker_archived_at');
        });
    }
};
