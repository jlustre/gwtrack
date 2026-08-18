<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_pipelines', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#0B1F3A');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_pipeline_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_pipeline_id')->constrained('crm_pipelines')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 20)->default('#64748B');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['crm_pipeline_id', 'slug'], 'crm_stages_pipeline_slug_unique');
            $table->index(['crm_pipeline_id', 'sort_order'], 'crm_stages_pipeline_sort_idx');
        });

        Schema::create('crm_funnel_participations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_pipeline_id')->constrained('crm_pipelines')->cascadeOnDelete();
            $table->foreignId('crm_pipeline_stage_id')->constrained('crm_pipeline_stages')->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('active');
            $table->string('state', 40)->default('active');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedTinyInteger('priority')->default(3);
            $table->decimal('probability', 5, 2)->nullable();
            $table->date('entered_at')->nullable();
            $table->date('stage_entered_at')->nullable();
            $table->date('expected_completion_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->string('outcome', 80)->nullable();
            $table->string('inactive_reason')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->string('next_action')->nullable();
            $table->date('next_action_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'crm_pipeline_id'], 'crm_part_user_pipeline_uq');
            $table->index(['crm_pipeline_id', 'crm_pipeline_stage_id'], 'crm_part_pipeline_stage_idx');
            $table->index(['owner_id', 'state'], 'crm_part_owner_state_idx');
        });

        Schema::create('crm_funnel_stage_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_funnel_participation_id')->constrained('crm_funnel_participations')->cascadeOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('crm_pipeline_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->constrained('crm_pipeline_stages')->restrictOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_funnel_stage_histories');
        Schema::dropIfExists('crm_funnel_participations');
        Schema::dropIfExists('crm_pipeline_stages');
        Schema::dropIfExists('crm_pipelines');
    }
};
