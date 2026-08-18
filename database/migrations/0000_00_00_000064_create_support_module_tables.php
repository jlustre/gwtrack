<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 40)->unique();
            $table->string('color_hex', 7)->default('#C8A24A');
            $table->boolean('is_system_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('support_sla_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('urgency', 20)->unique();
            $table->unsignedSmallInteger('response_time_hours');
            $table->timestamps();
        });

        Schema::create('support_saved_replies', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->string('module', 40)->index();
            $table->string('category', 40)->index();
            $table->string('user_intent_action', 40)->nullable();
            $table->string('user_reported_outcome', 40)->nullable();
            $table->string('subject');
            $table->longText('description');
            $table->string('urgency', 20)->default('medium')->index();
            $table->string('impact', 20)->default('self')->index();
            $table->string('frequency', 20)->default('unknown');
            $table->string('device', 20)->default('unknown');
            $table->string('browser', 30)->default('unknown');
            $table->string('related_url', 2048)->nullable();
            $table->foreignId('status_id')->constrained('support_ticket_statuses')->restrictOnDelete();
            $table->integer('priority_score')->default(0)->index();
            $table->string('sla_status', 20)->default('on_track')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status_id']);
            $table->index(['assigned_to', 'status_id']);
            $table->index(['urgency', 'sla_status']);
        });

        Schema::create('support_ticket_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('body');
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('support_ticket_internal_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('body');
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('support_ticket_attachments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('attachable');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();
        });

        Schema::create('support_ticket_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('old_status_id')->nullable()->constrained('support_ticket_statuses')->nullOnDelete();
            $table->foreignId('new_status_id')->constrained('support_ticket_statuses')->restrictOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('support_ticket_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('support_wishlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('module', 40)->index();
            $table->text('problem_solved');
            $table->longText('suggested_description');
            $table->string('example_link', 2048)->nullable();
            $table->json('business_value')->nullable();
            $table->string('user_priority', 20)->default('medium')->index();
            $table->integer('admin_priority_score')->default(0)->index();
            $table->string('development_complexity', 20)->nullable();
            $table->unsignedSmallInteger('estimated_effort_hours')->nullable();
            $table->date('target_release_date')->nullable();
            $table->string('status', 30)->default('submitted')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });

        Schema::create('support_wishlist_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wishlist_item_id')->constrained('support_wishlist_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['wishlist_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_wishlist_votes');
        Schema::dropIfExists('support_wishlist_items');
        Schema::dropIfExists('support_ticket_assignments');
        Schema::dropIfExists('support_ticket_status_histories');
        Schema::dropIfExists('support_ticket_attachments');
        Schema::dropIfExists('support_ticket_internal_notes');
        Schema::dropIfExists('support_ticket_comments');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('support_saved_replies');
        Schema::dropIfExists('support_sla_policies');
        Schema::dropIfExists('support_ticket_statuses');
    }
};
