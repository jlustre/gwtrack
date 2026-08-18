<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->json('schedule_block_share_audiences')->nullable()->after('show_weekends');
        });

        DB::table('user_calendar_preferences')->orderBy('id')->each(function (object $row): void {
            $shareWithMentor = (bool) ($row->share_schedule_blocks_with_mentor ?? true);

            DB::table('user_calendar_preferences')
                ->where('id', $row->id)
                ->update([
                    'schedule_block_share_audiences' => json_encode($shareWithMentor ? ['cfm'] : []),
                ]);
        });

        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->dropColumn('share_schedule_blocks_with_mentor');
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->boolean('share_schedule_blocks_with_mentor')->default(true)->after('show_weekends');
        });

        DB::table('user_calendar_preferences')->orderBy('id')->each(function (object $row): void {
            $audiences = json_decode((string) ($row->schedule_block_share_audiences ?? '[]'), true);
            $audiences = is_array($audiences) ? $audiences : [];

            DB::table('user_calendar_preferences')
                ->where('id', $row->id)
                ->update([
                    'share_schedule_blocks_with_mentor' => in_array('cfm', $audiences, true),
                ]);
        });

        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->dropColumn('schedule_block_share_audiences');
        });
    }
};
