<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('prospect_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('prospect_interests', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('prospect_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'slug']);
        });

        Schema::create('pipeline_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'slug']);
        });

        Schema::create('communication_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('appointment_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('followup_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('prospect_share_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_add_notes')->default(false);
            $table->boolean('can_add_communications')->default(false);
            $table->boolean('can_schedule_followups')->default(false);
            $table->boolean('can_schedule_appointments')->default(false);
            $table->boolean('can_edit_limited_fields')->default(false);
            $table->boolean('can_collaborate_fully')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('prospects', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prospect_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('country')->nullable();
            $table->string('timezone')->nullable();
            $table->string('preferred_language')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer_business')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('interest_level')->default('warm')->index();
            $table->string('priority')->default('medium')->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamp('last_contacted_at')->nullable()->index();
            $table->timestamp('appointment_at')->nullable()->index();
            $table->timestamp('conversion_at')->nullable()->index();
            $table->string('converted_to')->nullable()->index();
            $table->text('lost_reason')->nullable();
            $table->text('notes_summary')->nullable();
            $table->boolean('is_client')->default(false);
            $table->boolean('is_archived')->default(false)->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['owner_id', 'status']);
            $table->index(['owner_id', 'pipeline_stage_id']);
            $table->index(['owner_id', 'next_follow_up_at']);
        });

        Schema::create('prospect_type_prospect', function (Blueprint $table): void {
            $table->ulid('prospect_id');
            $table->foreignId('prospect_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['prospect_id', 'prospect_type_id']);
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
        });

        Schema::create('prospect_interest_prospect', function (Blueprint $table): void {
            $table->ulid('prospect_id');
            $table->foreignId('prospect_interest_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['prospect_id', 'prospect_interest_id']);
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
        });

        Schema::create('prospect_tag_pivot', function (Blueprint $table): void {
            $table->ulid('prospect_id');
            $table->foreignId('prospect_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['prospect_id', 'prospect_tag_id']);
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
        });

        Schema::create('prospect_notes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('prospect_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->boolean('is_private')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
            $table->index(['prospect_id', 'created_at']);
        });

        Schema::create('prospect_communications', function (Blueprint $table): void {
            $table->id();
            $table->ulid('prospect_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('communication_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction')->default('outbound');
            $table->timestamp('contacted_at')->index();
            $table->string('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
            $table->index(['prospect_id', 'contacted_at']);
        });

        Schema::create('prospect_appointments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('prospect_id');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_helper_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('appointment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('scheduled_at')->index();
            $table->string('timezone')->nullable();
            $table->string('location_or_link')->nullable();
            $table->string('purpose')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->string('reminder_status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
            $table->index(['owner_id', 'scheduled_at']);
        });

        Schema::create('prospect_followups', function (Blueprint $table): void {
            $table->id();
            $table->ulid('prospect_id');
            $table->foreignId('assigned_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('due_at')->index();
            $table->string('followup_type')->nullable();
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
            $table->index(['assigned_user_id', 'status', 'due_at']);
        });

        Schema::create('prospect_files', function (Blueprint $table): void {
            $table->id();
            $table->ulid('prospect_id');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
        });

        Schema::create('prospect_shares', function (Blueprint $table): void {
            $table->id();
            $table->ulid('prospect_id');
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prospect_share_permission_id')->nullable()->constrained()->nullOnDelete();
            $table->string('permission_level', 60)->default('view_only');
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->string('status', 40)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
            $table->unique(['prospect_id', 'shared_with', 'permission_level', 'status'], 'prospect_share_unique_active');
            $table->index(['shared_with', 'status']);
        });

        Schema::create('prospect_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->ulid('prospect_id')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['prospect_id', 'action']);
        });

        Schema::create('prospect_conversions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('prospect_id');
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('conversion_type')->index();
            $table->timestamp('converted_at')->index();
            $table->string('policy_reference')->nullable();
            $table->string('application_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('prospect_id')->references('id')->on('prospects')->cascadeOnDelete();
        });

        Schema::create('prospect_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_name')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->json('preview_payload')->nullable();
            $table->json('duplicate_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_imports');
        Schema::dropIfExists('prospect_conversions');
        Schema::dropIfExists('prospect_access_logs');
        Schema::dropIfExists('prospect_shares');
        Schema::dropIfExists('prospect_files');
        Schema::dropIfExists('prospect_followups');
        Schema::dropIfExists('prospect_appointments');
        Schema::dropIfExists('prospect_communications');
        Schema::dropIfExists('prospect_notes');
        Schema::dropIfExists('prospect_tag_pivot');
        Schema::dropIfExists('prospect_interest_prospect');
        Schema::dropIfExists('prospect_type_prospect');
        Schema::dropIfExists('prospects');
        Schema::dropIfExists('prospect_share_permissions');
        Schema::dropIfExists('followup_statuses');
        Schema::dropIfExists('appointment_types');
        Schema::dropIfExists('communication_types');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('prospect_tags');
        Schema::dropIfExists('prospect_interests');
        Schema::dropIfExists('prospect_types');
        Schema::dropIfExists('prospect_sources');
    }
};
