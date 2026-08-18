<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_production_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 64)->default('manual');
            $table->string('policy_reference')->nullable();
            $table->string('description')->nullable();
            $table->decimal('annual_premium', 14, 2)->default(0);
            $table->string('status', 32)->default('posted');
            $table->date('posted_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'posted_at']);
            $table->index('policy_reference');
        });

        Schema::table('goal_notes', function (Blueprint $table): void {
            $table->string('audio_path')->nullable()->after('body');
            $table->unsignedSmallInteger('audio_duration_seconds')->nullable()->after('audio_path');
        });
    }

    public function down(): void
    {
        Schema::table('goal_notes', function (Blueprint $table): void {
            $table->dropColumn(['audio_path', 'audio_duration_seconds']);
        });

        Schema::dropIfExists('member_production_entries');
    }
};
