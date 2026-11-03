<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_assignments', function (Blueprint $table): void {
            $table->index('mentor_id');
        });

        Schema::table('mentor_assignments', function (Blueprint $table): void {
            $table->dropUnique(['mentor_id', 'apprentice_id', 'status']);
        });

        Schema::table('mentor_assignments', function (Blueprint $table): void {
            $table->index(['mentor_id', 'apprentice_id', 'status']);
        });

        Schema::table('cfm_certification_requests', function (Blueprint $table): void {
            $table->string('source', 30)->default('self_service')->after('user_id');
            $table->foreignId('nominated_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });

        Schema::create('mentor_assignment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mentor_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['mentor_assignment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_assignment_events');

        Schema::table('cfm_certification_requests', function (Blueprint $table): void {
            $table->dropForeign(['nominated_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['source', 'nominated_by']);
        });

        Schema::table('mentor_assignments', function (Blueprint $table): void {
            $table->dropIndex(['mentor_id', 'apprentice_id', 'status']);
        });

        Schema::table('mentor_assignments', function (Blueprint $table): void {
            $table->unique(['mentor_id', 'apprentice_id', 'status']);
        });

        Schema::table('mentor_assignments', function (Blueprint $table): void {
            $table->dropIndex(['mentor_id']);
        });
    }
};
