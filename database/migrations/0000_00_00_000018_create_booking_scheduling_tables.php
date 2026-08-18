<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_event_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('calendar_category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('linked_checklist_id')->nullable();
            $table->foreignId('linked_training_module_id')->nullable()->constrained('training_modules')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('event_category')->default('mentor_session');
            $table->string('location_type')->default('zoom');
            $table->string('location_details')->nullable();
            $table->string('meeting_link')->nullable();
            $table->boolean('approval_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('visibility')->default('assigned_apprentices');
            $table->string('color')->default('#C8A24A');
            $table->unsignedSmallInteger('buffer_before_minutes')->default(0);
            $table->unsignedSmallInteger('buffer_after_minutes')->default(0);
            $table->unsignedInteger('minimum_notice_minutes')->default(720);
            $table->unsignedSmallInteger('maximum_booking_days_ahead')->default(30);
            $table->unsignedSmallInteger('daily_booking_limit')->nullable();
            $table->unsignedSmallInteger('weekly_booking_limit')->nullable();
            $table->string('allowed_attendee_type')->default('assigned_apprentices');
            $table->boolean('custom_questions_enabled')->default(false);
            $table->text('confirmation_message')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['owner_id', 'slug'], 'booking_event_owner_slug_uq');
            $table->index(['owner_id', 'is_active'], 'booking_event_owner_active_idx');
        });

        Schema::create('availability_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('timezone')->default('America/Vancouver');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('working_hours')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default'], 'availability_user_default_idx');
            $table->index(['user_id', 'is_active'], 'availability_user_active_idx');
        });

        Schema::create('availability_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('availability_schedule_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['availability_schedule_id', 'weekday'], 'availability_rule_day_idx');
        });

        Schema::create('availability_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('availability_schedule_id')->constrained()->cascadeOnDelete();
            $table->date('override_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['availability_schedule_id', 'override_date'], 'availability_override_date_idx');
        });

        Schema::create('blackout_dates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('reason')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'starts_on'], 'blackout_user_start_idx');
        });

        Schema::create('booking_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_event_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('availability_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apprentice_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('token')->unique();
            $table->string('link_type')->default('event_type');
            $table->string('visibility')->default('private');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_one_time')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'link_type'], 'booking_link_owner_type_idx');
            $table->index(['token', 'is_active'], 'booking_link_token_active_idx');
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('booking_event_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_link_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('availability_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ulid('related_prospect_id')->nullable();
            $table->unsignedBigInteger('related_checklist_id')->nullable();
            $table->string('status')->default('pending_approval')->index();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->string('timezone')->default('America/Vancouver');
            $table->string('location_type')->default('zoom');
            $table->string('location_details')->nullable();
            $table->string('meeting_link')->nullable();
            $table->longText('reason')->nullable();
            $table->longText('topics')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('no_show_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('related_prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['cfm_id', 'starts_at'], 'booking_cfm_start_idx');
            $table->index(['trainee_id', 'starts_at'], 'booking_trainee_start_idx');
            $table->index(['cfm_id', 'status'], 'booking_cfm_status_idx');
        });

        Schema::create('booking_attendees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('attendee_type')->default('trainee');
            $table->string('rsvp_status')->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['booking_id', 'attendee_type'], 'booking_attendee_type_idx');
        });

        Schema::create('booking_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_event_type_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->string('type')->default('short_answer');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_question_id')->constrained()->cascadeOnDelete();
            $table->json('answer')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['booking_id', 'booking_question_id'], 'booking_answer_unique');
        });

        Schema::create('booking_reschedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('old_starts_at');
            $table->timestamp('old_ends_at');
            $table->timestamp('new_starts_at');
            $table->timestamp('new_ends_at');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_cancellations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancelled_by_type')->default('user');
            $table->text('reason')->nullable();
            $table->boolean('request_new_time')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_activity_logs');
        Schema::dropIfExists('booking_cancellations');
        Schema::dropIfExists('booking_reschedules');
        Schema::dropIfExists('booking_answers');
        Schema::dropIfExists('booking_questions');
        Schema::dropIfExists('booking_attendees');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('booking_links');
        Schema::dropIfExists('blackout_dates');
        Schema::dropIfExists('availability_overrides');
        Schema::dropIfExists('availability_rules');
        Schema::dropIfExists('availability_schedules');
        Schema::dropIfExists('booking_event_types');
    }
};
