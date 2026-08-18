<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('message_center_announcement_reads')) {
            Schema::table('message_center_announcement_reads', function (Blueprint $table): void {
                if (! Schema::hasColumn('message_center_announcement_reads', 'first_viewed_at')) {
                    $table->timestamp('first_viewed_at')->nullable()->after('read_at');
                }
                if (! Schema::hasColumn('message_center_announcement_reads', 'opened_full')) {
                    $table->boolean('opened_full')->default(false)->after('first_viewed_at');
                }
            });
        }

        if (Schema::hasTable('message_center_announcements')) {
            Schema::table('message_center_announcements', function (Blueprint $table): void {
                if (! Schema::hasColumn('message_center_announcements', 'featured_sort')) {
                    $table->unsignedInteger('featured_sort')->default(0)->after('is_featured');
                }
            });
        }

        if (! Schema::hasTable('announcement_bookmarks')) {
            Schema::create('announcement_bookmarks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('announcement_id')->constrained('message_center_announcements')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['announcement_id', 'user_id'], 'announcement_bookmark_user_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_bookmarks');

        if (Schema::hasTable('message_center_announcement_reads')) {
            Schema::table('message_center_announcement_reads', function (Blueprint $table): void {
                foreach (['first_viewed_at', 'opened_full'] as $column) {
                    if (Schema::hasColumn('message_center_announcement_reads', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('message_center_announcements')) {
            Schema::table('message_center_announcements', function (Blueprint $table): void {
                if (Schema::hasColumn('message_center_announcements', 'featured_sort')) {
                    $table->dropColumn('featured_sort');
                }
            });
        }
    }
};
