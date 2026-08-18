<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->dropColumn([
                'schedule_block_share_audiences',
                'schedule_block_share_member_ids',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->json('schedule_block_share_audiences')->nullable()->after('show_weekends');
            $table->json('schedule_block_share_member_ids')->nullable()->after('schedule_block_share_audiences');
        });
    }
};
