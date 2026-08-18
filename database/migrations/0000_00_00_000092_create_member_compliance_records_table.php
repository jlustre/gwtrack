<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_compliance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('compliance_type', 40);
            $table->string('title');
            $table->string('jurisdiction_key', 120)->nullable();
            $table->string('identifier', 120)->nullable();
            $table->string('status', 30)->default('not_started');
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->unsignedSmallInteger('renewal_window_days')->nullable();
            $table->decimal('credits_required', 8, 2)->nullable();
            $table->decimal('credits_earned', 8, 2)->nullable();
            $table->string('carrier_name', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'compliance_type', 'status']);
            $table->index(['expiration_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_compliance_records');
    }
};
