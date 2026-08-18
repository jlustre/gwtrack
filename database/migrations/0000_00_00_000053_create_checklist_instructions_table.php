<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checklist_instructions')) {
            return;
        }

        Schema::create('checklist_instructions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checklist_type_id')
                ->constrained('checklist_types')
                ->cascadeOnDelete();
            $table->longText('instructions')->nullable();
            $table->string('doc_link', 500)->nullable();
            $table->string('other_link', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['checklist_type_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_instructions');
    }
};
