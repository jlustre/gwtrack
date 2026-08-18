<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsor_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sponsor_id', 'member_id', 'status'], 'sponsor_relationship_unique_active');
            $table->index(['member_id', 'status']);
        });

        Schema::create('user_hierarchy_paths', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ancestor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->timestamps();

            $table->unique(['ancestor_id', 'descendant_id']);
            $table->index(['ancestor_id', 'depth']);
            $table->index(['descendant_id', 'depth']);
        });

        Schema::create('team_visibility_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('viewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visible_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visibility_level')->default('profile');
            $table->boolean('can_view_sensitive_data')->default(false);
            $table->boolean('can_export')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['viewer_id', 'visible_user_id', 'visibility_level'], 'team_visibility_unique');
            $table->index(['viewer_id', 'visibility_level'], 'team_vis_viewer_level_idx');
            $table->index(['visible_user_id', 'visibility_level'], 'team_vis_visible_level_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_visibility_permissions');
        Schema::dropIfExists('user_hierarchy_paths');
        Schema::dropIfExists('sponsor_relationships');
    }
};
