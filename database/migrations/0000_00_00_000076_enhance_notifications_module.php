<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_types', function (Blueprint $table) {
            $table->string('group', 50)->nullable()->after('color');
            $table->boolean('user_configurable')->default(true)->after('group');
            $table->boolean('digest_eligible')->default(true)->after('user_configurable');
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('in_app_title')->nullable()->after('body');
            $table->string('in_app_message', 500)->nullable()->after('in_app_title');
            $table->string('sms_body', 160)->nullable()->after('in_app_message');
            $table->string('push_title')->nullable()->after('sms_body');
            $table->string('push_body', 200)->nullable()->after('push_title');
            $table->string('action_label', 80)->default('View')->after('push_body');
            $table->string('action_url_template')->nullable()->after('action_label');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('priority', 20)->default('info')->after('action_link');
            $table->string('module', 50)->nullable()->after('priority');
            $table->nullableMorphs('related');
            $table->foreignId('related_user_id')->nullable()->after('related_id')->constrained('users')->nullOnDelete();
            $table->timestamp('snoozed_until')->nullable()->after('read_at');
            $table->timestamp('archived_at')->nullable()->after('snoozed_until');
            $table->json('metadata')->nullable()->after('archived_at');

            $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'archived_at'], 'notifications_inbox_index');
            $table->index(['priority', 'created_at']);
        });

        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_user_selectable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_type_id')->constrained('notification_types')->cascadeOnDelete();
            $table->foreignId('notification_channel_id')->constrained('notification_channels')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('frequency', 30)->default('immediate');
            $table->timestamps();

            $table->unique(
                ['user_id', 'notification_type_id', 'notification_channel_id'],
                'notification_preferences_unique'
            );
        });

        Schema::create('notification_preference_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->foreignId('notification_type_id')->constrained('notification_types')->cascadeOnDelete();
            $table->foreignId('notification_channel_id')->constrained('notification_channels')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('frequency', 30)->default('immediate');
            $table->timestamps();

            $table->unique(
                ['role', 'notification_type_id', 'notification_channel_id'],
                'notification_preference_defaults_unique'
            );
        });

        Schema::create('notification_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger_code')->nullable();
            $table->string('channel', 30);
            $table->string('status', 30);
            $table->text('failure_reason')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('notification_id')->references('id')->on('notifications')->nullOnDelete();
            $table->index(['channel', 'status', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_logs');
        Schema::dropIfExists('notification_preference_defaults');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_channels');

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['related_user_id']);
            $table->dropIndex('notifications_inbox_index');
            $table->dropIndex(['priority', 'created_at']);
            $table->dropMorphs('related');
            $table->dropColumn([
                'priority',
                'module',
                'related_user_id',
                'snoozed_until',
                'archived_at',
                'metadata',
            ]);
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropColumn([
                'in_app_title',
                'in_app_message',
                'sms_body',
                'push_title',
                'push_body',
                'action_label',
                'action_url_template',
            ]);
        });

        Schema::table('notification_types', function (Blueprint $table) {
            $table->dropColumn(['group', 'user_configurable', 'digest_eligible']);
        });
    }
};
