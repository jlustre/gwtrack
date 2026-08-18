<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_analytics_daily')) {
            Schema::create('announcement_analytics_daily', function (Blueprint $table): void {
                $table->id();
                $table->date('stat_date')->index();
                $table->foreignId('announcement_id')->nullable()->constrained('message_center_announcements')->cascadeOnDelete();
                $table->unsignedInteger('views')->default(0);
                $table->unsignedInteger('reads')->default(0);
                $table->unsignedInteger('acknowledgements')->default(0);
                $table->unsignedInteger('reactions')->default(0);
                $table->unsignedInteger('comments')->default(0);
                $table->unsignedInteger('bookmarks')->default(0);
                $table->unsignedInteger('reach')->default(0);
                $table->timestamps();

                $table->unique(['stat_date', 'announcement_id'], 'announcement_analytics_daily_uq');
            });
        }

        if (Schema::hasTable('broadcast_messages')) {
            Schema::table('broadcast_messages', function (Blueprint $table): void {
                if (! Schema::hasColumn('broadcast_messages', 'status')) {
                    $table->string('status', 20)->default('sent')->after('body');
                }
                if (! Schema::hasColumn('broadcast_messages', 'recipient_count')) {
                    $table->unsignedInteger('recipient_count')->default(0)->after('sent_at');
                }
                if (! Schema::hasColumn('broadcast_messages', 'priority')) {
                    $table->string('priority', 20)->default('important')->after('recipient_count');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_analytics_daily');

        if (Schema::hasTable('broadcast_messages')) {
            Schema::table('broadcast_messages', function (Blueprint $table): void {
                foreach (['status', 'recipient_count', 'priority'] as $column) {
                    if (Schema::hasColumn('broadcast_messages', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
