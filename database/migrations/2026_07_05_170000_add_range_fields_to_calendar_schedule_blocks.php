<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_schedule_blocks', function (Blueprint $table): void {
            $table->unsignedTinyInteger('end_weekday')->nullable()->after('weekday');
        });

        Schema::table('calendar_schedule_block_overrides', function (Blueprint $table): void {
            $table->date('block_end_date')->nullable()->after('block_date');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_schedule_block_overrides', function (Blueprint $table): void {
            $table->dropColumn('block_end_date');
        });

        Schema::table('calendar_schedule_blocks', function (Blueprint $table): void {
            $table->dropColumn('end_weekday');
        });
    }
};
