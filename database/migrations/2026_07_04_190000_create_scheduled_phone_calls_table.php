<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('scheduled_phone_calls');

        Schema::create('scheduled_phone_calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('person_type', 20);
            $table->foreignUlid('prospect_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contact_name');
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email')->nullable();
            $table->dateTime('scheduled_at');
            $table->string('timezone', 64)->default('America/Vancouver');
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->string('result', 20)->nullable();
            $table->text('result_notes')->nullable();
            $table->dateTime('rescheduled_to')->nullable();
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('scheduled_phone_calls')->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prospect_activity_id')->nullable()->constrained('prospect_activities')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_phone_calls');
    }
};
