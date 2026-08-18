<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table): void {
            $table->id();
            $table->string('backup_type', 40)->index();
            $table->string('filename');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->json('included_tables')->nullable();
            $table->json('excluded_tables')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('compressed')->default(true);
            $table->boolean('encrypted')->default(false);
            $table->string('disk')->default('backups');
            $table->timestamps();
        });

        Schema::create('backup_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('backup_id')->constrained('backups')->cascadeOnDelete();
            $table->string('table_name')->index();
            $table->unsignedBigInteger('record_count')->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->unsignedBigInteger('exported_bytes')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('restore_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('backup_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->foreignId('pre_restore_backup_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->string('restore_type', 40)->index();
            $table->string('restore_mode', 40)->index();
            $table->string('status', 40)->default('pending')->index();
            $table->foreignId('restored_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('restored_tables')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source_filename')->nullable();
            $table->string('source_file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('restore_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restore_log_id')->constrained('restore_logs')->cascadeOnDelete();
            $table->string('table_name')->index();
            $table->unsignedBigInteger('records_before')->default(0);
            $table->unsignedBigInteger('records_restored')->default(0);
            $table->unsignedBigInteger('records_skipped')->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('backup_type', 40);
            $table->string('frequency', 40);
            $table->time('run_at')->nullable();
            $table->json('days_of_week')->nullable();
            $table->json('included_tables')->nullable();
            $table->json('excluded_tables')->nullable();
            $table->boolean('compressed')->default(true);
            $table->boolean('encrypted')->default(false);
            $table->unsignedInteger('retention_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('backup_schedules');
        Schema::dropIfExists('restore_items');
        Schema::dropIfExists('restore_logs');
        Schema::dropIfExists('backup_items');
        Schema::dropIfExists('backups');
    }
};
