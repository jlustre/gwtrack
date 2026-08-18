<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_modules', function (Blueprint $table): void {
            $table->string('course_type')->default('video')->after('description');
            $table->string('difficulty')->default('beginner')->after('course_type');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('difficulty');
            $table->foreignId('instructor_id')->nullable()->after('duration_minutes')->constrained('users')->nullOnDelete();
            $table->string('thumbnail_path')->nullable()->after('instructor_id');
            $table->json('tags')->nullable()->after('thumbnail_path');
            $table->string('status')->default('draft')->after('tags');
            $table->boolean('is_featured')->default(false)->after('status');
            $table->boolean('sequential_required')->default(true)->after('is_featured');
            $table->boolean('drip_enabled')->default(false)->after('sequential_required');
        });

        Schema::table('training_lessons', function (Blueprint $table): void {
            $table->string('lesson_type')->default('video')->after('title');
            $table->boolean('is_required')->default(true)->after('lesson_type');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('is_required');
            $table->string('resource_path')->nullable()->after('video_url');
            $table->string('external_url')->nullable()->after('resource_path');
        });

        Schema::table('training_progress', function (Blueprint $table): void {
            $table->unsignedInteger('time_spent_seconds')->default(0)->after('status');
            $table->timestamp('started_at')->nullable()->after('time_spent_seconds');
        });

        Schema::create('training_paths', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('audience')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_path_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_module_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->unique(['training_path_id', 'training_module_id'], 'training_path_modules_uq');
        });

        Schema::create('user_training_path_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('in_progress');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'training_path_id'], 'user_training_path_enrollments_uq');
        });

        Schema::create('training_certifications', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('training_module_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('required_score')->default(80);
            $table->boolean('mentor_approval_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_training_certifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_certification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('certificate_number')->nullable()->unique();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'training_certification_id'], 'user_training_certifications_uq');
        });

        Schema::create('training_badges', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('level')->default('bronze');
            $table->string('icon')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_training_badges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('earned_at');
            $table->timestamps();
            $table->unique(['user_id', 'training_badge_id'], 'user_training_badges_uq');
        });

        Schema::create('training_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('assigned');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('mentor_training_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_module_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('training_assignment_id')->nullable()->constrained('training_assignments')->nullOnDelete();
            $table->string('review_type')->default('coaching');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamps();
        });

        Schema::create('training_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('session_type')->default('live');
            $table->foreignId('training_module_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained('calendar_events')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_session_attendance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('registered');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
            $table->unique(['training_session_id', 'user_id'], 'training_session_attendance_uq');
        });

        Schema::create('training_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_module_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('training_path_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason_code');
            $table->text('message');
            $table->unsignedTinyInteger('priority')->default(50);
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_recommendations');
        Schema::dropIfExists('training_session_attendance');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('mentor_training_reviews');
        Schema::dropIfExists('training_assignments');
        Schema::dropIfExists('user_training_badges');
        Schema::dropIfExists('training_badges');
        Schema::dropIfExists('user_training_certifications');
        Schema::dropIfExists('training_certifications');
        Schema::dropIfExists('user_training_path_enrollments');
        Schema::dropIfExists('training_path_modules');
        Schema::dropIfExists('training_paths');

        Schema::table('training_progress', function (Blueprint $table): void {
            $table->dropColumn(['time_spent_seconds', 'started_at']);
        });

        Schema::table('training_lessons', function (Blueprint $table): void {
            $table->dropColumn(['lesson_type', 'is_required', 'duration_minutes', 'resource_path', 'external_url']);
        });

        Schema::table('training_modules', function (Blueprint $table): void {
            $table->dropForeign(['instructor_id']);
            $table->dropColumn([
                'course_type',
                'difficulty',
                'duration_minutes',
                'instructor_id',
                'thumbnail_path',
                'tags',
                'status',
                'is_featured',
                'sequential_required',
                'drip_enabled',
            ]);
        });
    }
};
