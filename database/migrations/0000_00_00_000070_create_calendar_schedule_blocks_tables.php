<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_schedule_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('block_type')->default('work');
            $table->string('label')->nullable();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_shared')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'weekday', 'is_active'], 'cal_schedule_block_user_day_idx');
        });

        Schema::create('calendar_schedule_block_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('block_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('block_type')->default('other');
            $table->string('label')->nullable();
            $table->string('reason')->nullable();
            $table->boolean('is_blocked')->default(true);
            $table->boolean('is_shared')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'block_date'], 'cal_schedule_override_date_idx');
        });

        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->boolean('share_schedule_blocks_with_mentor')->default(true)->after('show_weekends');
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_preferences', function (Blueprint $table): void {
            $table->dropColumn('share_schedule_blocks_with_mentor');
        });

        Schema::dropIfExists('calendar_schedule_block_overrides');
        Schema::dropIfExists('calendar_schedule_blocks');
    }
};
