<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rank_requirements', function (Blueprint $table) {
            $table->string('category', 40)->default('general')->after('description');
            $table->boolean('is_required')->default(true)->after('category');
        });

        Schema::table('user_rank_progress', function (Blueprint $table) {
            $table->text('member_notes')->nullable()->after('status');
            $table->text('reviewer_notes')->nullable()->after('member_notes');
            $table->timestamp('submitted_at')->nullable()->after('completed_at');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('user_rank_progress', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['member_notes', 'reviewer_notes', 'submitted_at', 'reviewed_at']);
        });

        Schema::table('rank_requirements', function (Blueprint $table) {
            $table->dropColumn(['category', 'is_required']);
        });
    }
};
