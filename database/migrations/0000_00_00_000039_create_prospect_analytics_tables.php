<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period_type', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('metric_key', 60);
            $table->unsignedInteger('target_value');
            $table->unsignedInteger('actual_value')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'period_type', 'period_start', 'metric_key'], 'pg_period_unique');
            $table->index(['user_id', 'period_start'], 'pg_user_period_idx');
        });

        Schema::create('prospect_goal_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->string('metric_key', 60);
            $table->unsignedInteger('value')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'snapshot_date', 'metric_key'], 'pgs_user_date_metric_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_goal_snapshots');
        Schema::dropIfExists('prospect_goals');
    }
};
