<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fna_records', function (Blueprint $table): void {
            $table->boolean('is_client_portal')->default(false)->after('dime_completed');
        });

        Schema::create('fna_client_invites', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('token', 64)->unique();
            $table->string('security_code_hash');
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->ulid('prospect_id')->nullable();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone', 60)->nullable();
            $table->string('access_credential_hash')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('personal_message')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['sender_user_id', 'status']);
            $table->index(['fna_record_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fna_client_invites');

        Schema::table('fna_records', function (Blueprint $table): void {
            $table->dropColumn('is_client_portal');
        });
    }
};
