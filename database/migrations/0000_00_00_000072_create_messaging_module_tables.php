<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->default('direct')->index();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->string('avatar_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'last_message_at'], 'conversations_type_last_msg_idx');
        });

        Schema::create('conversation_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('member_role')->default('member');
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id'], 'conversation_member_uq');
            $table->index(['user_id', 'is_archived'], 'conversation_member_user_arch_idx');
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('message_type')->default('text')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('pinned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at'], 'messages_conversation_created_idx');
            $table->index(['conversation_id', 'parent_id'], 'messages_thread_idx');
        });

        Schema::create('message_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['message_id', 'user_id'], 'message_read_uq');
        });

        Schema::create('message_deletes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('delete_scope')->default('me');
            $table->timestamps();

            $table->unique(['message_id', 'user_id'], 'message_delete_uq');
        });

        Schema::create('message_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reaction', 32);
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'reaction'], 'message_reaction_uq');
        });

        Schema::create('message_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('attachment_type')->default('file');
            $table->foreignId('portal_resource_id')->nullable()->constrained('resources')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('message_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'user_id'], 'message_flag_uq');
        });

        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('general');
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('message_center_announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->string('audience_type')->default('organization');
            $table->json('audience_config')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('message_center_announcement_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')->constrained('message_center_announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id'], 'msg_announcement_read_uq');
        });

        Schema::create('broadcast_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('audience_type')->default('agency');
            $table->json('audience_config')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('message_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_task_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['message_id', 'user_task_id'], 'message_task_uq');
        });

        Schema::create('message_calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['message_id', 'calendar_event_id'], 'message_calendar_uq');
        });

        Schema::create('conversation_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('tag');
            $table->timestamps();

            $table->unique(['conversation_id', 'tag'], 'conversation_tag_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_tags');
        Schema::dropIfExists('message_calendar_events');
        Schema::dropIfExists('message_tasks');
        Schema::dropIfExists('broadcast_messages');
        Schema::dropIfExists('message_center_announcement_reads');
        Schema::dropIfExists('message_center_announcements');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('message_flags');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('message_deletes');
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_members');
        Schema::dropIfExists('conversations');
    }
};
