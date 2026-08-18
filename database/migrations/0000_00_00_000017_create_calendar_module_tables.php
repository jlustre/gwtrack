<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color')->default('#C8A24A');
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('calendar_event_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color')->default('#0B1F3A');
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calendar_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->string('timezone')->default('America/Vancouver');
            $table->boolean('is_all_day')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('visibility')->default('private')->index();
            $table->string('status')->default('scheduled')->index();
            $table->string('color')->default('#C8A24A');
            $table->ulid('related_prospect_id')->nullable();
            $table->foreignId('related_apprentice_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('related_training_module_id')->nullable()->constrained('training_modules')->nullOnDelete();
            $table->foreignId('related_rank_requirement_id')->nullable()->constrained('rank_requirements')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('related_prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['organizer_id', 'starts_at'], 'cal_events_organizer_start_idx');
            $table->index(['visibility', 'starts_at'], 'cal_events_visibility_start_idx');
            $table->index(['status', 'starts_at'], 'cal_events_status_start_idx');
        });

        Schema::create('calendar_event_attendees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->ulid('prospect_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('attendee_type')->default('user');
            $table->string('rsvp_status')->default('pending')->index();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['calendar_event_id', 'rsvp_status'], 'cal_attendees_event_rsvp_idx');
            $table->index(['user_id', 'rsvp_status'], 'cal_attendees_user_rsvp_idx');
        });

        Schema::create('calendar_event_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('minutes_before')->default(15);
            $table->string('channel')->default('in_app');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('calendar_event_recurrences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('frequency')->default('weekly');
            $table->unsignedSmallInteger('interval')->default(1);
            $table->json('weekdays')->nullable();
            $table->unsignedInteger('ends_after_occurrences')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('calendar_event_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('calendar_event_visibility_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->string('visibility_type')->default('user');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['visibility_type', 'user_id'], 'cal_vis_type_user_idx');
            $table->index(['visibility_type', 'team_id'], 'cal_vis_type_team_idx');
        });

        Schema::create('calendar_event_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('note');
            $table->boolean('is_private')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('calendar_event_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('user_calendar_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('default_view')->default('month');
            $table->string('timezone')->default('America/Vancouver');
            $table->json('visible_calendar_categories')->nullable();
            $table->boolean('show_weekends')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_calendar_preferences');
        Schema::dropIfExists('calendar_event_activity_logs');
        Schema::dropIfExists('calendar_event_notes');
        Schema::dropIfExists('calendar_event_visibility_rules');
        Schema::dropIfExists('calendar_event_attachments');
        Schema::dropIfExists('calendar_event_recurrences');
        Schema::dropIfExists('calendar_event_reminders');
        Schema::dropIfExists('calendar_event_attendees');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('calendar_event_types');
        Schema::dropIfExists('calendar_categories');
    }
};
