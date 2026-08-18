<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_escalation_rules')) {
            Schema::create('notification_escalation_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 80)->unique();
                $table->string('name');
                $table->string('module', 50)->nullable();
                $table->string('condition_type', 80);
                $table->json('condition_config')->nullable();
                $table->json('escalation_steps');
                $table->unsignedSmallInteger('cooldown_hours')->default(24);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notification_escalation_logs')) {
            Schema::create('notification_escalation_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('escalation_rule_id');
                $table->string('subject_type');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedSmallInteger('step_index')->default(0);
                $table->json('notified_user_ids')->nullable();
                $table->string('trigger_code')->nullable();
                $table->timestamp('fired_at');
                $table->timestamps();

                $table->foreign('escalation_rule_id', 'notif_escalation_logs_rule_fk')
                    ->references('id')
                    ->on('notification_escalation_rules')
                    ->cascadeOnDelete();

                $table->index(['subject_type', 'subject_id'], 'notif_escalation_subject_idx');
                $table->index(
                    ['escalation_rule_id', 'subject_type', 'subject_id', 'step_index'],
                    'notif_escalation_step_idx',
                );
            });
        }

        if (! Schema::hasTable('notification_digest_settings')) {
            Schema::create('notification_digest_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('digest_type', 20);
                $table->time('send_at')->default('07:00:00');
                $table->unsignedTinyInteger('send_day')->nullable();
                $table->foreignId('timezone_id')->nullable()->constrained('timezones')->nullOnDelete();
                $table->boolean('enabled')->default(true);
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'digest_type'], 'notif_digest_settings_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_digest_settings');
        Schema::dropIfExists('notification_escalation_logs');
        Schema::dropIfExists('notification_escalation_rules');
    }
};
