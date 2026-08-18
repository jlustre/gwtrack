<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfm_mentor_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('certification_status', 40)->default('certified');
            $table->string('hierarchy_access', 40)->default('my_hierarchy');
            $table->unsignedTinyInteger('max_apprentices')->default(6);
            $table->boolean('manual_unavailable')->default(false);
            $table->decimal('fap_completion_rate', 5, 2)->default(0);
            $table->unsignedTinyInteger('calendar_busyness_percent')->default(0);
            $table->unsignedTinyInteger('avg_apprentice_progress')->default(0);
            $table->unsignedSmallInteger('recommendation_score')->default(0);
            $table->json('languages')->nullable();
            $table->json('specialties')->nullable();
            $table->text('mentor_bio')->nullable();
            $table->timestamp('last_mentor_activity_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cfm_recommendation_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->string('recommendation_type', 40);
            $table->string('label');
            $table->string('cfm_name')->nullable();
            $table->unsignedSmallInteger('fit_score')->nullable();
            $table->string('status_label', 40)->nullable();
            $table->text('detail')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfm_recommendation_suggestions');
        Schema::dropIfExists('cfm_mentor_profiles');
    }
};
