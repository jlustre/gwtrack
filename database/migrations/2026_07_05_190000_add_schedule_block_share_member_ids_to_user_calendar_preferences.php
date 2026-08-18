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
            $table->json('schedule_block_share_member_ids')->nullable()->after('schedule_block_share_audiences');
        });

        DB::table('user_calendar_preferences')->orderBy('id')->each(function (object $row): void {
            $audiences = json_decode((string) ($row->schedule_block_share_audiences ?? '[]'), true);
            $audiences = is_array($audiences) ? $audiences : [];
            $audiences = array_values(array_filter(
                $audiences,
                fn (mixed $audience): bool => $audience !== 'anybody'
            ));

            DB::table('user_calendar_preferences')
                ->where('id', $row->id)
                ->update([
                    'schedule_block_share_audiences' => json_encode($audiences),
                    'schedule_block_share_member_ids' => json_encode([]),
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->dropColumn('schedule_block_share_member_ids');
        });
    }
};
