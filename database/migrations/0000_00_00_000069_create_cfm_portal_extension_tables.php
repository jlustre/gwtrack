<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfm_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->string('category')->default('general');
            $table->text('body');
            $table->json('tags')->nullable();
            $table->boolean('is_private')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['cfm_id', 'trainee_id']);
        });

        Schema::create('cfm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('category')->default('coaching');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['cfm_id', 'trainee_id', 'status']);
        });

        Schema::create('cfm_task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_task_id')->constrained('cfm_tasks')->cascadeOnDelete();
            $table->string('action');
            $table->text('details')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cfm_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('type')->default('coaching');
            $table->string('title');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->index(['cfm_id', 'trainee_id', 'starts_at']);
        });

        Schema::create('cfm_meeting_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_meeting_id')->constrained('cfm_meetings')->cascadeOnDelete();
            $table->text('summary')->nullable();
            $table->json('action_items')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cfm_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('scope')->default('mentor');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cfm_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_checklist_id')->constrained('cfm_checklists')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cfm_progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->string('report_type');
            $table->string('audience')->default('cfm');
            $table->json('payload');
            $table->string('export_format')->nullable();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cfm_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('level')->default('low');
            $table->json('flags')->nullable();
            $table->json('recommended_actions')->nullable();
            $table->timestamp('assessed_at');
            $table->timestamps();

            $table->index(['cfm_id', 'trainee_id', 'assessed_at']);
        });

        Schema::create('cfm_action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('steps')->nullable();
            $table->string('status')->default('active');
            $table->date('target_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cfm_coaching_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->string('focus_area')->nullable();
            $table->text('notes')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamp('session_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cfm_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('template')->nullable();
            $table->string('channel')->default('in_app');
            $table->string('subject');
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cfm_trainee_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->string('evaluation_type')->default('performance');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('summary')->nullable();
            $table->json('criteria')->nullable();
            $table->foreignId('evaluated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cfm_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->foreignId('target_rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->unsignedTinyInteger('readiness_percent')->default(0);
            $table->json('requirements_met')->nullable();
            $table->json('requirements_remaining')->nullable();
            $table->string('status')->default('tracking');
            $table->timestamps();

            $table->unique(['cfm_id', 'trainee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfm_promotions');
        Schema::dropIfExists('cfm_trainee_evaluations');
        Schema::dropIfExists('cfm_notifications');
        Schema::dropIfExists('cfm_coaching_sessions');
        Schema::dropIfExists('cfm_action_plans');
        Schema::dropIfExists('cfm_risk_scores');
        Schema::dropIfExists('cfm_progress_reports');
        Schema::dropIfExists('cfm_checklist_items');
        Schema::dropIfExists('cfm_checklists');
        Schema::dropIfExists('cfm_meeting_notes');
        Schema::dropIfExists('cfm_meetings');
        Schema::dropIfExists('cfm_task_logs');
        Schema::dropIfExists('cfm_tasks');
        Schema::dropIfExists('cfm_notes');
    }
};
