<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_campaigns')) {
            Schema::create('announcement_campaigns', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type', 30);
                $table->text('description')->nullable();
                $table->longText('rules')->nullable();
                $table->json('prizes')->nullable();
                $table->timestamp('starts_at')->nullable()->index();
                $table->timestamp('ends_at')->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->string('leaderboard_metric', 40)->default('production');
                $table->json('leaderboard_config')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('announcement_campaign_participants')) {
            Schema::create('announcement_campaign_participants', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('announcement_campaigns')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('progress_value', 14, 2)->default(0);
                $table->json('progress_meta')->nullable();
                $table->timestamp('joined_at');
                $table->timestamps();

                $table->unique(['campaign_id', 'user_id'], 'campaign_participant_uq');
            });
        }

        if (Schema::hasTable('message_center_announcements')) {
            Schema::table('message_center_announcements', function (Blueprint $table): void {
                if (! Schema::hasColumn('message_center_announcements', 'campaign_id')) {
                    $table->foreignId('campaign_id')->nullable()->after('metadata')->constrained('announcement_campaigns')->nullOnDelete();
                }
                if (! Schema::hasColumn('message_center_announcements', 'calendar_event_id')) {
                    $table->foreignId('calendar_event_id')->nullable()->after('campaign_id')->constrained('calendar_events')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('message_center_announcements')) {
            Schema::table('message_center_announcements', function (Blueprint $table): void {
                foreach (['campaign_id', 'calendar_event_id'] as $column) {
                    if (Schema::hasColumn('message_center_announcements', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
            });
        }

        Schema::dropIfExists('announcement_campaign_participants');
        Schema::dropIfExists('announcement_campaigns');
    }
};
