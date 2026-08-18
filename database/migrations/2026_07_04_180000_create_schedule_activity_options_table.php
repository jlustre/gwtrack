<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_activity_options', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 32);
            $table->string('value');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'team_id']);
            $table->unique(['category', 'value', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_activity_options');
    }
};
