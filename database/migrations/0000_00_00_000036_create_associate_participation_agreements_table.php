<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('associate_participation_agreements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('associate_id')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('country')->nullable();
            $table->string('sponsor_name')->nullable();
            $table->boolean('acknowledgment_accepted')->default(false);
            $table->string('associate_signature')->nullable();
            $table->date('associate_signed_at')->nullable();
            $table->string('status')->default('draft');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('associate_participation_agreements');
    }
};
