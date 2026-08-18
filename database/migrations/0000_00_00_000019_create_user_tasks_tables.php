<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assigned_to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->string('status', 30)->default('to_do');
            $table->string('category', 60);
            $table->string('related_module', 60)->nullable();
            $table->string('related_person', 120)->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('reminder', 40)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['assigned_to_user_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('user_task_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_task_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->boolean('is_done')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_task_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('task_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->string('icon', 10)->default('📌');
            $table->string('text');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_suggestions');
        Schema::dropIfExists('user_task_comments');
        Schema::dropIfExists('user_task_checklist_items');
        Schema::dropIfExists('user_tasks');
    }
};
