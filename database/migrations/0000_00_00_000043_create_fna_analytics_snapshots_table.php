<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fna_analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->string('metric_key', 60);
            $table->decimal('value', 14, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'snapshot_date', 'metric_key'], 'fna_analytics_snapshots_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fna_analytics_snapshots');
    }
};
