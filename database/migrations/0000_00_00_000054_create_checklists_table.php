<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checklist_type_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->string('responsible_parties')->nullable();
            $table->string('notified_parties')->nullable();
            $table->string('country')->nullable();
            $table->string('group_label')->nullable();
            $table->unsignedTinyInteger('phase_number')->nullable();
            $table->string('phase_title')->nullable();
            $table->string('phase_target')->nullable();
            $table->string('section_title')->nullable();
            $table->string('slug')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['checklist_type_id', 'is_active', 'sort_order'], 'checklists_type_active_sort_idx');
            $table->unique(['checklist_type_id', 'slug'], 'checklists_type_slug_uq');
        });

        Schema::create('checklist_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checklist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_assignment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('not_started');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'checklist_id'], 'checklist_progress_user_idx');
            $table->index(['mentor_assignment_id', 'checklist_id'], 'checklist_progress_assignment_idx');
            $table->index(['checklist_id', 'status'], 'checklist_progress_status_idx');
        });

        $this->wireBookingChecklistForeignKeys();
    }

    public function down(): void
    {
        $this->dropBookingChecklistForeignKeys();

        Schema::dropIfExists('checklist_progress');
        Schema::dropIfExists('checklists');
    }

    private function wireBookingChecklistForeignKeys(): void
    {
        if (Schema::hasTable('booking_event_types') && Schema::hasColumn('booking_event_types', 'linked_checklist_id')) {
            Schema::table('booking_event_types', function (Blueprint $table): void {
                $table->foreign('linked_checklist_id')->references('id')->on('checklists')->nullOnDelete();
            });
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'related_checklist_id')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->foreign('related_checklist_id')->references('id')->on('checklists')->nullOnDelete();
            });
        }
    }

    private function dropBookingChecklistForeignKeys(): void
    {
        if (Schema::hasTable('booking_event_types') && Schema::hasColumn('booking_event_types', 'linked_checklist_id')) {
            Schema::table('booking_event_types', function (Blueprint $table): void {
                $table->dropForeign(['linked_checklist_id']);
            });
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'related_checklist_id')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropForeign(['related_checklist_id']);
            });
        }
    }
};
