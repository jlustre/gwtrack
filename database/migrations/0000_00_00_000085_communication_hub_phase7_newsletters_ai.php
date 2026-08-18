<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_templates')) {
            Schema::create('announcement_templates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('category_id')->nullable()->constrained('announcement_categories')->nullOnDelete();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('template_type', 30)->default('announcement');
                $table->text('prompt_hint')->nullable();
                $table->string('title_template');
                $table->string('summary_template', 500)->nullable();
                $table->text('body_template');
                $table->string('default_priority', 20)->default('informational');
                $table->string('default_audience_type', 30)->default('all');
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('announcement_newsletters')) {
            Schema::create('announcement_newsletters', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('period_type', 20);
                $table->timestamp('period_starts_at');
                $table->timestamp('period_ends_at');
                $table->string('status', 20)->default('draft');
                $table->string('subject');
                $table->longText('html_body');
                $table->longText('text_body')->nullable();
                $table->json('compiled_sections')->nullable();
                $table->json('announcement_ids')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->unsignedInteger('sent_count')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['period_type', 'period_starts_at']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_newsletters');
        Schema::dropIfExists('announcement_templates');
    }
};
