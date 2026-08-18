<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('task_category_id')->constrained('task_categories')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('slug')->nullable()->unique();
                $table->string('default_priority', 20)->default('medium');
                $table->string('related_module', 60)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['task_category_id', 'is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('task_users')) {
            Schema::create('task_users', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('assignee_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->text('additional_notes')->nullable();
                $table->foreignId('task_category_id')->constrained('task_categories')->cascadeOnDelete();
                $table->foreignId('assignor_id')->constrained('users')->cascadeOnDelete();
                $table->string('priority', 20)->default('medium');
                $table->string('status', 30)->default('to_do');
                $table->string('related_module', 60)->nullable();
                $table->string('related_person', 120)->nullable();
                $table->ulid('related_prospect_id')->nullable();
                $table->foreignUlid('related_fna_id')->nullable();
                $table->date('due_date')->nullable();
                $table->unsignedTinyInteger('progress')->default(0);
                $table->string('reminder', 40)->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('related_prospect_id')->references('id')->on('prospects')->nullOnDelete();
                $table->foreign('related_fna_id')->references('id')->on('fna_records')->nullOnDelete();

                $table->index(['assignee_id', 'status']);
                $table->index(['task_category_id', 'status']);
                $table->index('due_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_users');
        Schema::dropIfExists('tasks');
    }
};
