<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfm_review_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('trigger_type', 50);
            $table->unsignedSmallInteger('days_after_assignment')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cfm_feedback_questions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('question');
            $table->string('category', 50)->default('mentorship');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cfm_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_assignment_id')->nullable()->constrained('mentor_assignments')->nullOnDelete();
            $table->foreignId('review_cycle_id')->nullable()->constrained('cfm_review_cycles')->nullOnDelete();
            $table->string('trigger_type', 50);
            $table->string('status', 30)->default('pending');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('average_rating', 4, 2)->nullable();
            $table->text('helped_most')->nullable();
            $table->text('improvements')->nullable();
            $table->text('comments')->nullable();
            $table->text('suggestions')->nullable();
            $table->json('analysis_summary')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cfm_id', 'status']);
            $table->index(['trainee_id', 'status']);
        });

        Schema::create('cfm_feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_review_id')->constrained('cfm_reviews')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('cfm_feedback_questions')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['cfm_review_id', 'question_id']);
        });

        Schema::create('cfm_ao_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 30)->default('draft');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->json('category_scores')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvement_areas')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('promotion_potential', 30)->nullable();
            $table->string('leadership_potential', 30)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['cfm_id', 'period_start']);
        });

        Schema::create('cfm_scorecards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ao_evaluation_id')->nullable()->constrained('cfm_ao_evaluations')->nullOnDelete();
            $table->string('period_type', 20)->default('quarterly');
            $table->date('period_start');
            $table->date('period_end');
            $table->json('categories');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['cfm_id', 'period_start']);
        });

        Schema::create('cfm_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->string('metric_key', 60);
            $table->decimal('value', 12, 4)->default(0);
            $table->decimal('score', 5, 2)->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['cfm_id', 'metric_key', 'period_start', 'period_end'], 'cfm_perf_metric_period_unique');
        });

        Schema::create('cfm_effectiveness_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->string('period_type', 20)->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('objective_score', 5, 2)->default(0);
            $table->decimal('feedback_score', 5, 2)->default(0);
            $table->decimal('ao_score', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->json('weights')->nullable();
            $table->json('metrics_snapshot')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['cfm_id', 'period_type', 'period_start'], 'cfm_eff_score_period_unique');
        });

        Schema::create('cfm_effectiveness_action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->string('improvement_area');
            $table->text('target_outcome')->nullable();
            $table->json('action_steps')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('status', 30)->default('active');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['cfm_id', 'status']);
        });

        Schema::create('cfm_strengths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->string('label');
            $table->string('source', 30)->default('feedback');
            $table->unsignedInteger('mention_count')->default(1);
            $table->timestamp('last_identified_at')->nullable();
            $table->timestamps();

            $table->unique(['cfm_id', 'label', 'source']);
        });

        Schema::create('cfm_improvement_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->string('label');
            $table->string('source', 30)->default('feedback');
            $table->unsignedInteger('mention_count')->default(1);
            $table->timestamp('last_identified_at')->nullable();
            $table->timestamps();

            $table->unique(['cfm_id', 'label', 'source']);
        });

        Schema::create('cfm_recognition_badges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('criteria_key', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cfm_recognition_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('cfm_recognition_badges')->cascadeOnDelete();
            $table->date('awarded_for_period')->nullable();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['cfm_id', 'badge_id', 'awarded_for_period'], 'cfm_badge_period_unique');
        });

        Schema::create('cfm_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key', 60);
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('rank_position')->default(0);
            $table->decimal('score', 8, 2)->default(0);
            $table->timestamps();

            $table->index(['metric_key', 'period_start', 'rank_position']);
        });

        Schema::create('cfm_review_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->string('review_type', 50);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('comments')->nullable();
            $table->string('status', 30)->default('completed');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('reviewable');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['cfm_id', 'review_type']);
        });

        Schema::create('cfm_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key', 60);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('agency_average', 12, 4)->default(0);
            $table->decimal('top_cfm_average', 12, 4)->default(0);
            $table->decimal('value', 12, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['metric_key', 'period_start', 'period_end'], 'cfm_analytics_period_unique');
        });

        Schema::create('cfm_effectiveness_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->string('risk_type', 60);
            $table->string('severity', 20)->default('medium');
            $table->text('message');
            $table->json('meta')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('ao_notified')->default(false);
            $table->timestamps();

            $table->index(['cfm_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfm_effectiveness_risks');
        Schema::dropIfExists('cfm_analytics');
        Schema::dropIfExists('cfm_review_histories');
        Schema::dropIfExists('cfm_leaderboards');
        Schema::dropIfExists('cfm_recognition_awards');
        Schema::dropIfExists('cfm_recognition_badges');
        Schema::dropIfExists('cfm_improvement_areas');
        Schema::dropIfExists('cfm_strengths');
        Schema::dropIfExists('cfm_effectiveness_action_plans');
        Schema::dropIfExists('cfm_effectiveness_scores');
        Schema::dropIfExists('cfm_performance_metrics');
        Schema::dropIfExists('cfm_scorecards');
        Schema::dropIfExists('cfm_ao_evaluations');
        Schema::dropIfExists('cfm_feedback_responses');
        Schema::dropIfExists('cfm_reviews');
        Schema::dropIfExists('cfm_feedback_questions');
        Schema::dropIfExists('cfm_review_cycles');
    }
};
