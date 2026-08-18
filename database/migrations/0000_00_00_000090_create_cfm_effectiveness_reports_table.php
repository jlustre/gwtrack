<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfm_effectiveness_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfm_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_type', 60);
            $table->string('audience', 30)->default('cfm');
            $table->string('period_type', 20)->default('quarterly');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('payload');
            $table->string('export_format', 20)->default('pdf');
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['cfm_id', 'report_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfm_effectiveness_reports');
    }
};
