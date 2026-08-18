<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('accent_class')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('goal_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_category_id')->constrained('goal_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('hierarchy_level', 32)->default('monthly');
            $table->string('measurement_type', 32)->default('number');
            $table->string('metric_key')->nullable();
            $table->decimal('default_target', 14, 2)->nullable();
            $table->json('suggested_milestones')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('goal_badges', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('level', 32)->default('bronze');
            $table->json('criteria')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_category_id')->constrained('goal_categories');
            $table->foreignId('parent_goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->foreignId('goal_template_id')->nullable()->constrained('goal_templates')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hierarchy_level', 32)->default('monthly');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('measurement_type', 32)->default('number');
            $table->string('metric_key')->nullable();
            $table->decimal('target_value', 14, 2)->default(0);
            $table->decimal('actual_value', 14, 2)->default(0);
            $table->string('currency_code', 3)->nullable();
            $table->string('status', 32)->default('active');
            $table->unsignedTinyInteger('smart_score')->default(0);
            $table->json('smart_feedback')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('deadline_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('accountability_partner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('notification_settings')->nullable();
            $table->unsignedSmallInteger('streak_days')->default(0);
            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'hierarchy_level']);
            $table->index(['deadline_at', 'status']);
        });

        Schema::create('goal_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('target_value', 14, 2)->nullable();
            $table->decimal('actual_value', 14, 2)->default(0);
            $table->date('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('goal_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->decimal('value', 14, 2);
            $table->string('source', 32)->default('manual');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['goal_id', 'recorded_at']);
        });

        Schema::create('goal_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('note_type', 32)->default('coach');
            $table->text('body');
            $table->boolean('is_private')->default(false);
            $table->timestamps();
        });

        Schema::create('goal_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('goal_comments')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('goal_coaches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('coach_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32)->default('mentor');
            $table->boolean('can_edit')->default(false);
            $table->boolean('receives_alerts')->default(true);
            $table->timestamps();

            $table->unique(['goal_id', 'coach_user_id', 'role']);
        });

        Schema::create('goal_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('review_type', 32);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('summary')->nullable();
            $table->json('action_items')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('goal_achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_badge_id')->constrained('goal_badges')->cascadeOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->timestamp('earned_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'goal_badge_id']);
        });

        Schema::create('goal_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('remind_at');
            $table->string('channel', 32)->default('in_app');
            $table->string('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('goal_forecasts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->date('forecast_date');
            $table->decimal('projected_value', 14, 2);
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('goal_scorecards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period_type', 32);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('scores')->nullable();
            $table->unsignedTinyInteger('overall_score')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_scorecards');
        Schema::dropIfExists('goal_forecasts');
        Schema::dropIfExists('goal_reminders');
        Schema::dropIfExists('goal_achievements');
        Schema::dropIfExists('goal_reviews');
        Schema::dropIfExists('goal_coaches');
        Schema::dropIfExists('goal_comments');
        Schema::dropIfExists('goal_notes');
        Schema::dropIfExists('goal_progress');
        Schema::dropIfExists('goal_milestones');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('goal_badges');
        Schema::dropIfExists('goal_templates');
        Schema::dropIfExists('goal_categories');
    }
};
